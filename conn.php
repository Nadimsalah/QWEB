<?php
// Prevent PHP warnings/errors from corrupting JSON responses
error_reporting(0);
ini_set('display_errors', '0');

// Disable mysqli strict exception mode — errors are handled per-query
mysqli_report(MYSQLI_REPORT_OFF);

// API REQUEST LOGGER (Temporary for Debugging App Photos)
$uri = $_SERVER['REQUEST_URI'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'POST') {
    $reqLog = date('Y-m-d H:i:s') . " - URI: " . $uri . "\n";
    $postLog = $_POST;
    // Truncate massive strings like Base64 to keep logs readable
    foreach ($postLog as $k => $v) {
        if (is_string($v) && strlen($v) > 200) {
            $postLog[$k] = substr($v, 0, 50) . "...[" . strlen($v) . " bytes]";
        }
    }
    $reqLog .= "_POST: " . print_r($postLog, true);
    if (!empty($_FILES)) $reqLog .= "_FILES: " . print_r($_FILES, true);
    $inputBody = file_get_contents("php://input");
    if ($inputBody) $reqLog .= "php://input: " . substr($inputBody, 0, 100) . "...\n";
    $reqLog .= str_repeat("-", 40) . "\n";
    @file_put_contents(__DIR__ . '/app_requests.log', $reqLog, FILE_APPEND);
}

$dbhost = "145.223.33.118";
$dbuser = "qoon_Qoon";
$dbpass = ";)xo6b(RE}K%";
$dbname = "qoon_Qoon";

// ── Pretty error page helper ──
if (!function_exists('showDbError')) {
    function showDbError($msg) {
        // If this is a JSON-only API caller (Accept: application/json), keep JSON
        $acceptJson = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
        $isXhr      = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($acceptJson || $isXhr) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        global $errorMsg;
        $errorMsg = $msg;
        include __DIR__ . '/error_db.php';
        exit();
    }
}

try {
    if (defined('OFFLINE_MODE') && OFFLINE_MODE) {
        throw new Exception("Offline Mode Enabled (Bypassing DB)");
    }
    
    $con = mysqli_init();
    $con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 6);
    @$con->real_connect($dbhost, $dbuser, $dbpass, $dbname);
    
    if ($con->connect_error) {
        if (defined('FROM_UI')) {
            $con = false;
        } else {
            showDbError('Failed to connect to MySQL: ' . $con->connect_error);
        }
    } else {
        // Force UTF-8 on every connection so Arabic / Unicode text displays correctly
        $con->set_charset("utf8mb4");
        $con->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    }
} catch (Exception $e) {
    if (defined('FROM_UI')) {
        $con = false;
    } else {
        showDbError('Connection failed: ' . $e->getMessage());
    }
}

// Hardcoded Domain Name to ensure perfect mobile app URL resolution
$DomainNamee = "https://qoon.app/userDriver/UserDriverApi/";

// If running locally, use dynamic URL instead
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
if (strpos($httpHost, 'localhost') === 0 || strpos($httpHost, '192.168.') === 0 || strpos($httpHost, '127.0.0.1') === 0) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
    $dirPath = str_replace('\\', '/', dirname($scriptName));
    if ($dirPath !== '/') $dirPath .= '/';
    $DomainNamee = "$protocol://$httpHost" . $dirPath;
}

/**
 * Resolves a photo path to a full public URL.
 * Handles relative paths, absolute URLs, old jibler.app domain, and missing photos.
 */
if (!function_exists('resolvePhotoUrl')) {
    function resolvePhotoUrl($photoPath, $userName = 'User') {
        global $DomainNamee;
        
        if (!$photoPath || $photoPath == 'NONE' || $photoPath == '0' || trim($photoPath) == '') {
            return "https://ui-avatars.com/api/?name=" . urlencode($userName) . "&background=random&color=fff";
        }
        
        // --- DOMAIN MIGRATION: Rewrite old jibler.app URLs to qoon.app ---
        // Old format: https://jibler.app/jibler/partener/Api/photo/filename.jpg
        // New format: https://qoon.app/userDriver/UserDriverApi/photo/filename.jpg
        if (strpos($photoPath, 'jibler.app') !== false || strpos($photoPath, 'jibler') !== false) {
            // Extract just the filename from old absolute URLs
            $filename = basename(parse_url($photoPath, PHP_URL_PATH));
            if ($filename) {
                return $DomainNamee . 'photo/' . $filename;
            }
        }

        // If it's already a full URL (http/https), return as-is
        if (strpos($photoPath, 'http') === 0) {
            // Fix double slashes in existing qoon.app absolute URLs
            $photoPath = preg_replace('#(https?://)([^/]+)//+#', '$1$2/', $photoPath);
            return $photoPath;
        }
        
        // It's a relative path or just a filename — build full URL
        // Handle paths that might or might not have 'photo/' prefix
        $cleanPath = (strpos($photoPath, 'photo/') === 0) ? $photoPath : 'photo/' . $photoPath;
        
        return $DomainNamee . $cleanPath;
    }
}
