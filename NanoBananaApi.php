<?php
// Buffer ALL output so no stray whitespace/errors leak before our JSON response
ob_start();

// Re-enable errors so our handler can catch them
error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once 'conn.php';

// Discard anything conn.php may have output (whitespace, warnings etc)
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Always return JSON even on fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['error' => "PHP Error [$errno]: $errstr in $errfile line $errline"]);
    exit;
});
register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        http_response_code(500);
        echo json_encode(['error' => 'PHP Fatal: ' . $e['message']]);
    }
    ob_end_flush();
});

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? '';
// NanoBanana VTO API Key (dedicated Virtual Try-On engine)
$apiKey = "0f42b65364c4334e76c294a44a82e6dc";
$nanoBananaBase = "https://api.nanobananaapi.ai/api/v1/nanobanana";
// Imgur Client ID — anonymous upload, no account needed
$imgurClientId = "546c25a59c58ad7";

$storageDir = __DIR__ . '/vto_temp/';
if (!is_dir($storageDir)) @mkdir($storageDir, 0755, true);

/**
 * Resize image to max 1024px on longest side (reduces upload size dramatically).
 * Falls back to raw data if GD not available.
 */
function resizeForUpload($rawData, $maxPx = 1024) {
    if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
        return $rawData;
    }
    $im = @imagecreatefromstring($rawData);
    if (!$im) return $rawData;
    $w = imagesx($im); $h = imagesy($im);
    // Only resize if larger than maxPx
    if ($w <= $maxPx && $h <= $maxPx) {
        imagedestroy($im);
        return $rawData;
    }
    $ratio = $w > $h ? $maxPx / $w : $maxPx / $h;
    $nw = (int)($w * $ratio); $nh = (int)($h * $ratio);
    $bg = imagecreatetruecolor($nw, $nh);
    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
    imagecopyresampled($bg, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
    ob_start();
    imagejpeg($bg, null, 85);
    $jpeg = ob_get_clean();
    imagedestroy($im); imagedestroy($bg);
    error_log("[VTO] Resized {$w}x{$h} → {$nw}x{$nh}, " . strlen($rawData) . "B → " . strlen($jpeg) . "B");
    return $jpeg ?: $rawData;
}

/**
 * Upload image to multiple public hosts with fallback.
 * Returns the public direct image URL, or false if all fail.
 */
function uploadToImgur($rawData, $imgurClientId) {
    // Resize first to keep uploads fast
    $rawData = resizeForUpload($rawData, 1024);

    // --- Method 1: Imgur (base64, 8s timeout) ---
    $base64 = base64_encode($rawData);
    $ch = curl_init("https://api.imgur.com/3/image");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['image' => $base64, 'type' => 'base64'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => ["Authorization: Client-ID $imgurClientId"],
    ]);
    $res = curl_exec($ch); $err = curl_error($ch);
    curl_close($ch);
    if (!$err) {
        $data = json_decode($res, true);
        if ($data['success'] ?? false) {
            error_log("[VTO] Imgur OK: " . ($data['data']['link'] ?? ''));
            return $data['data']['link'] ?? false;
        }
        error_log("[VTO] Imgur failed: " . substr($res, 0, 150));
    } else {
        error_log("[VTO] Imgur error: $err");
    }

    // --- Method 2: 0x0.st (8s timeout) ---
    $tmpPath = tempnam(sys_get_temp_dir(), 'vto_');
    file_put_contents($tmpPath, $rawData);
    $cfile = new CURLFile($tmpPath, 'image/jpeg', 'image.jpg');
    $ch2 = curl_init("https://0x0.st");
    curl_setopt_array($ch2, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cfile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $res2 = curl_exec($ch2); $err2 = curl_error($ch2);
    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2); @unlink($tmpPath);
    if (!$err2 && $code2 == 200 && strpos(trim($res2), 'https://') === 0) {
        error_log("[VTO] 0x0.st OK: " . trim($res2));
        return trim($res2);
    }
    error_log("[VTO] 0x0.st failed (code=$code2 err=$err2): " . substr($res2 ?? '', 0, 100));

    // --- Method 3: tmpfiles.org (8s timeout) ---
    $tmpPath3 = tempnam(sys_get_temp_dir(), 'vto3_');
    file_put_contents($tmpPath3, $rawData);
    $cfile3 = new CURLFile($tmpPath3, 'image/jpeg', 'image.jpg');
    $ch3 = curl_init("https://tmpfiles.org/api/v1/upload");
    curl_setopt_array($ch3, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => $cfile3],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $res3 = curl_exec($ch3); $err3 = curl_error($ch3);
    curl_close($ch3); @unlink($tmpPath3);
    if (!$err3 && $res3) {
        $d3  = json_decode($res3, true);
        $rawUrl = $d3['data']['url'] ?? '';
        if ($rawUrl) {
            $directUrl = str_replace('tmpfiles.org/', 'tmpfiles.org/dl/', $rawUrl);
            error_log("[VTO] tmpfiles.org OK: $directUrl");
            return $directUrl;
        }
    }
    error_log("[VTO] tmpfiles.org failed: " . substr($res3 ?? '', 0, 100));

    return false;
}

/**
 * Download a Pruna result URL and save it locally to vto_temp/.
 * Returns a localhost URL on success, or false on failure.
 */
function saveResultLocally($generationUrl, $apiKey, $storageDir, $scriptName) {
    // Try with Pruna auth header first, then without (CDN URLs may be public)
    foreach ([["apikey: $apiKey"], []] as $headers) {
        $ch = curl_init($generationUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        $imgData  = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        error_log("[VTO-Pruna] Download HTTP=$httpCode size=" . strlen($imgData ?? ''));
        if ($imgData && strlen($imgData) > 500 && $httpCode < 400) break;
        $imgData = null;
    }
    if (!$imgData || strlen($imgData) < 500) return false;

    // Detect format
    $ext   = 'jpg';
    $magic = substr($imgData, 0, 12);
    if (strpos($magic, 'PNG')  !== false) $ext = 'png';
    if (strpos($magic, 'WEBP') !== false) $ext = 'webp';

    // Save locally
    $fname    = 'vto_result_' . time() . '_' . substr(md5($generationUrl), 0, 8) . '.' . $ext;
    $savePath = rtrim($storageDir, '/\\') . '/' . $fname;
    file_put_contents($savePath, $imgData);

    // Build localhost URL from this script's web path
    $proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host      = $_SERVER['HTTP_HOST'];
    $scriptDir = rtrim(dirname($scriptName), '/\\');
    $localUrl  = "$proto://$host$scriptDir/vto_temp/$fname";
    error_log("[VTO-Pruna] Saved → $localUrl");
    return $localUrl;
}

/**
 * Upload one image URL to Pruna Files API
 */
function uploadToPrunaFiles($imgUrl, $apiKey) {
    // Download the image first
    $ch = curl_init($imgUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $imgData = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err || !$imgData || strlen($imgData) < 100) return false;

    // Save to temp file
    $tmpPath = tempnam(sys_get_temp_dir(), 'pruna_');
    file_put_contents($tmpPath, $imgData);
    $cfile = new CURLFile($tmpPath, 'image/jpeg', 'image.jpg');

    // Upload to Pruna Files API
    $ch2 = curl_init('https://api.pruna.ai/v1/files');
    curl_setopt_array($ch2, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['content' => $cfile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ["apikey: $apiKey", "Accept: application/json"],
    ]);
    $res = curl_exec($ch2);
    $uploadErr = curl_error($ch2);
    curl_close($ch2);
    @unlink($tmpPath);

    if ($uploadErr) {
        error_log("[VTO-Pruna] File upload cURL error: $uploadErr");
        return false;
    }
    $data = json_decode($res, true);
    error_log("[VTO-Pruna] File upload response: " . substr($res, 0, 300));
    return $data['urls']['get'] ?? false;
}


/**
 * Normalize image to JPEG using GD if available, otherwise return raw data.
 */
function normalizeToJpeg($rawData) {
    if (!function_exists('imagecreatefromstring')) {
        return $rawData; // GD not available — use raw
    }
    $im = @imagecreatefromstring($rawData);
    if (!$im) return $rawData;
    if (!function_exists('imagecreatetruecolor')) return $rawData;
    $bg = imagecreatetruecolor(imagesx($im), imagesy($im));
    imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
    imagecopy($bg, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
    ob_start();
    imagejpeg($bg, null, 90);
    $jpeg = ob_get_clean();
    imagedestroy($im);
    imagedestroy($bg);
    return $jpeg ?: $rawData;
}

/**
 * Download a remote image, normalize to JPEG, return binary or false.
 */
function fetchAndNormalize($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    if (!$data || strlen($data) < 100) return false;
    return normalizeToJpeg($data);
}

/* ── ACTION: upload ── */
if ($action === 'upload') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload error: ' . ($_FILES['file']['error'] ?? 'no file')]);
        exit;
    }

    $rawData = file_get_contents($_FILES['file']['tmp_name']);
    if (!$rawData || strlen($rawData) < 100) {
        echo json_encode(['error' => 'Uploaded file is empty or too small']);
        exit;
    }

    $rawData = normalizeToJpeg($rawData);

    $publicUrl = uploadToImgur($rawData, $imgurClientId);
    if (!$publicUrl) {
        http_response_code(500);
        echo json_encode(['error' => 'All image hosting methods failed (Imgur, 0x0.st, tmpfiles.org). Check XAMPP error log for details: C:/xampp/apache/logs/error.log']);
        exit;
    }

    echo json_encode(['url' => $publicUrl]);
    exit;
}

/* ── ACTION: proxy_image ── */
if ($action === 'proxy_image') {
    $imgUrl = trim($_POST['url'] ?? '');
    if (!$imgUrl) { echo json_encode(['url' => '']); exit; }

    // For public HTTPS URLs (not localhost): download, normalize, re-upload to Imgur
    if (strpos($imgUrl, 'http') === 0 && strpos($imgUrl, 'localhost') === false && strpos($imgUrl, '127.0.0.1') === false) {
        $jpeg = fetchAndNormalize($imgUrl);
        if ($jpeg && strlen($jpeg) > 100) {
            $publicUrl = uploadToImgur($jpeg, $imgurClientId);
            if ($publicUrl) {
                echo json_encode(['url' => $publicUrl]);
                exit;
            }
        }
        // Fallback: return original URL
        echo json_encode(['url' => $imgUrl]);
        exit;
    }

    // For localhost / relative URLs: try reading from disk
    $imgData = null;
    $parsed  = parse_url($imgUrl);
    $relPath = ltrim($parsed['path'] ?? '', '/');

    foreach ([
        rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/\\') . '/' . $relPath,
        __DIR__ . '/' . $relPath,
        __DIR__ . '/uploads/' . basename($relPath),
    ] as $localPath) {
        if (file_exists($localPath) && filesize($localPath) > 100) {
            $imgData = file_get_contents($localPath);
            break;
        }
    }

    if (!$imgData || strlen($imgData) < 100) {
        echo json_encode(['url' => $imgUrl]);
        exit;
    }

    $imgData   = normalizeToJpeg($imgData);
    $publicUrl = uploadToImgur($imgData, $imgurClientId);
    echo json_encode(['url' => $publicUrl ?: $imgUrl]);
    exit;
}

/* ── ACTION: submit ── */
if ($action === 'submit') {
    $userImgUrl = trim($_POST['userImg'] ?? '');
    $prodImgUrl = trim($_POST['prodImg'] ?? '');
    $prompt = trim($_POST['prompt'] ?? '') ?: "Virtual try-on fashion photography, high quality, photorealistic clothing, natural lighting.";

    if (!$userImgUrl || !$prodImgUrl) {
        http_response_code(400);
        echo json_encode(['code' => 400, 'error' => 'Missing image data']);
        exit;
    }

    error_log("[VTO-NB] Submitting: userImg=$userImgUrl | prodImg=$prodImgUrl");

    // ── Submit to NanoBanana VTO API ──────────────────────────────────────
    $nbPayload = json_encode([
        'prompt'    => $prompt,
        'type'      => 'IMAGETOIAMGE',   // NanoBanana spelling
        'numImages' => 1,
        'imageUrls' => [$userImgUrl, $prodImgUrl],
    ]);

    $ch = curl_init($nanoBananaBase . '/generate');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $nbPayload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer $apiKey",
        ],
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'error' => 'NanoBanana cURL error: ' . $err]);
        exit;
    }

    error_log("[VTO-NB] Generate response (HTTP $httpCode): " . substr($response, 0, 400));
    $data = json_decode($response, true);

    if (!$data || ($data['code'] ?? 0) !== 200) {
        http_response_code(500);
        echo json_encode([
            'code'  => 500,
            'error' => 'NanoBanana submit failed: ' . ($data['msg'] ?? substr($response, 0, 200)),
        ]);
        exit;
    }

    $taskId = $data['data']['taskId'] ?? ($data['taskId'] ?? null);
    if (!$taskId) {
        http_response_code(500);
        echo json_encode(['code' => 500, 'error' => 'No taskId in NanoBanana response', 'raw' => substr($response, 0, 300)]);
        exit;
    }

    error_log("[VTO-NB] Task ID: $taskId");
    echo json_encode(['code' => 200, 'data' => ['taskId' => $taskId]]);
    exit;
}

/* ── ACTION: poll ── */
if ($action === 'poll') {
    $taskId = trim($_GET['taskId'] ?? '');
    if (!$taskId) {
        echo json_encode(['code' => 400, 'error' => 'Missing taskId']);
        exit;
    }

    // If the submit returned an immediate result (DONE:<url>), return it now
    if (strpos($taskId, 'DONE:') === 0) {
        $resultUrl = substr($taskId, 5);
        echo json_encode([
            'code' => 200,
            'data' => [
                'successFlag' => 1,
                'response'    => ['resultImageUrl' => $resultUrl],
            ]
        ]);
        exit;
    }

    $safeId     = preg_replace('/[^a-zA-Z0-9_-]/', '', $taskId);
    $resultFile = $storageDir . 'task_' . $safeId . '.json';

    // Return cached successful result
    if (file_exists($resultFile)) {
        echo file_get_contents($resultFile);
        exit;
    }

    // Poll NanoBanana status endpoint
    $ch = curl_init($nanoBananaBase . '/record-info?taskId=' . urlencode($taskId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer $apiKey",
            "Accept: application/json",
        ],
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || !$res) {
        echo json_encode(['code' => 500, 'error' => 'Poll cURL error: ' . $err]);
        exit;
    }

    error_log("[VTO-NB] Poll response: " . substr($res, 0, 400));
    $data = json_decode($res, true);
    if (!$data) {
        echo json_encode(['code' => 500, 'error' => 'Invalid JSON from NanoBanana: ' . substr($res, 0, 100)]);
        exit;
    }

    // NanoBanana uses successFlag: 0=processing, 1=done, 2/3=failed
    $flag = $data['data']['successFlag'] ?? ($data['successFlag'] ?? null);
    $resData = $data['data']['response'] ?? ($data['response'] ?? null);
    error_log("[VTO-NB] Poll flag=$flag");

    if ($flag == 1) {
        $resultUrl = $resData['resultImageUrl'] ?? ($resData['result_url'] ?? '');
        $mapped = [
            'code' => 200,
            'data' => [
                'successFlag' => 1,
                'response'    => ['resultImageUrl' => $resultUrl],
                '_raw'        => $data,
            ]
        ];
        file_put_contents($resultFile, json_encode($mapped));
        echo json_encode($mapped);
        exit;
    }

    if ($flag == 2 || $flag == 3) {
        $errMsg = $data['data']['errorMessage'] ?? ($data['errorMessage'] ?? 'NanoBanana generation failed');
        echo json_encode([
            'code' => 200,
            'data' => [
                'successFlag'  => 2,
                'errorMessage' => $errMsg,
                'errorCode'    => 500,
            ]
        ]);
        exit;
    }

    // Still processing
    echo json_encode([
        'code' => 200,
        'data' => ['successFlag' => 0, 'status' => 'processing'],
    ]);
    exit;
}

/* ── ACTION: fetch_result ── */
// Downloads Pruna result image server-side → saves to vto_temp/ → returns localhost URL
if ($action === 'fetch_result') {
    $resultUrl = trim($_POST['url'] ?? '');
    if (!$resultUrl) {
        echo json_encode(['error' => 'Missing url']);
        exit;
    }

    $imgData = null;

    // Try 1: download with Pruna auth header
    $ch = curl_init($resultUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER     => ["apikey: $apiKey"],
    ]);
    $imgData  = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    error_log("[VTO-fetch] Try1 HTTP=$httpCode err=$curlErr size=" . strlen($imgData ?? ''));

    // Try 2: without auth (some CDN URLs don't need it)
    if (!$imgData || strlen($imgData) < 500 || $httpCode >= 400) {
        $ch2 = curl_init($resultUrl);
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'Mozilla/5.0',
        ]);
        $imgData2  = curl_exec($ch2);
        $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
        curl_close($ch2);
        error_log("[VTO-fetch] Try2 HTTP=$httpCode2 size=" . strlen($imgData2 ?? ''));
        if ($imgData2 && strlen($imgData2) > 500 && $httpCode2 < 400) {
            $imgData = $imgData2;
        }
    }

    // Determine file extension from content
    $ext = 'jpg';
    if ($imgData && strlen($imgData) > 4) {
        $magic = substr($imgData, 0, 4);
        if (strpos($magic, 'PNG') !== false)  $ext = 'png';
        if (strpos($magic, 'GIF') !== false)  $ext = 'gif';
        if (strpos($magic, 'WEBP') !== false) $ext = 'webp';
    }

    if (!$imgData || strlen($imgData) < 500) {
        // Cannot download — return the original URL and let browser try
        error_log("[VTO-fetch] Failed to download, returning original: $resultUrl");
        echo json_encode(['url' => $resultUrl, 'warning' => 'could not proxy image']);
        exit;
    }

    // Always save locally — guaranteed accessible to browser
    $fname    = 'vto_result_' . time() . '_' . substr(md5($resultUrl), 0, 8) . '.' . $ext;
    $savePath = $storageDir . $fname;
    file_put_contents($savePath, $imgData);

    // Build localhost URL relative to THIS script's directory (not a hardcoded path)
    // e.g. if script is at localhost:8000/NanoBananaApi.php → vto_temp is at localhost:8000/vto_temp/
    $proto      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host       = $_SERVER['HTTP_HOST'];
    $scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $localUrl   = "$proto://$host$scriptDir/vto_temp/$fname";

    error_log("[VTO-fetch] Saved to $savePath → $localUrl");
    echo json_encode(['url' => $localUrl]);
    exit;
}


echo json_encode(['error' => 'Invalid action: ' . htmlspecialchars($action)]);