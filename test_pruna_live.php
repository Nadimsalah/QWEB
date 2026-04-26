<?php
// Full Pruna VTO test — open: http://localhost:8000/test_pruna_live.php
set_time_limit(300);
header('Content-Type: text/plain; charset=utf-8');

$apiKey    = 'pru_AdE9f0Zx_wZMX8GJzqQjGvcB5CizoY5G';
$personUrl = 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=512&q=80'; // woman
$garmentUrl= 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=512&q=80'; // white t-shirt

function curlGet($url, $headers=[]) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return [$res, $code, $err];
}

function uploadToPrunaFiles($imageUrl, $apiKey) {
    echo "  Downloading image: $imageUrl\n";
    [$imgData, $code, $err] = curlGet($imageUrl);
    if ($err || !$imgData || strlen($imgData) < 100) {
        return [false, "Download failed: HTTP $code err=$err"];
    }
    echo "  Downloaded: " . strlen($imgData) . " bytes\n";

    $tmpPath = tempnam(sys_get_temp_dir(), 'pruna_');
    file_put_contents($tmpPath, $imgData);
    $cfile = new CURLFile($tmpPath, 'image/jpeg', 'image.jpg');

    $ch = curl_init('https://api.pruna.ai/v1/files');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['content' => $cfile],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER     => ["apikey: $apiKey", "Accept: application/json"],
    ]);
    $res  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    @unlink($tmpPath);

    echo "  Pruna upload HTTP $code | Response: " . substr($res, 0, 300) . "\n";
    if ($err) return [false, "Upload cURL error: $err"];
    $data = json_decode($res, true);
    $url  = $data['urls']['get'] ?? null;
    if (!$url) return [false, "No urls.get in response: $res"];
    return [$url, null];
}

echo "=== PRUNA VTO LIVE TEST ===\n\n";

// Step 1: Upload person image
echo "--- Step 1: Upload PERSON image ---\n";
[$personPrunaUrl, $err1] = uploadToPrunaFiles($personUrl, $apiKey);
if ($err1) { echo "FAILED: $err1\n"; exit; }
echo "  Person Pruna URL: $personPrunaUrl\n\n";

// Step 2: Upload garment image
echo "--- Step 2: Upload GARMENT image ---\n";
[$garmentPrunaUrl, $err2] = uploadToPrunaFiles($garmentUrl, $apiKey);
if ($err2) { echo "FAILED: $err2\n"; exit; }
echo "  Garment Pruna URL: $garmentPrunaUrl\n\n";

// Step 3: Submit prediction
echo "--- Step 3: Submit prediction ---\n";
$prompt = "Replace the clothing on the person in the input image with the clothing from the reference image. Keep the person's identity, face, body shape, pose, and skin tone unchanged. Apply the new clothing so it fits naturally to the body, respecting proportions, folds, and fabric behavior. Output should look like a professional fashion photo, highly realistic, 4K quality.";
$payload = json_encode([
    'input' => [
        'prompt'       => $prompt,
        'images'       => [$personPrunaUrl, $garmentPrunaUrl],
        'aspect_ratio' => 'match_input_image',
        'turbo'        => false,
    ]
]);

$ch = curl_init('https://api.pruna.ai/v1/predictions');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        "apikey: $apiKey",
        "Content-Type: application/json",
        "Accept: application/json",
        "Model: p-image-edit",
        "Try-Sync: true",
    ],
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

echo "  HTTP: $code | cURL err: " . ($err ?: 'none') . "\n";
echo "  FULL RESPONSE:\n$res\n\n";

$data = json_decode($res, true);
if (!$data) { echo "ERROR: Invalid JSON from Pruna\n"; exit; }

// Check immediate result
if (!empty($data['generation_url'])) {
    echo "=== IMMEDIATE RESULT ===\n";
    echo "generation_url: " . $data['generation_url'] . "\n";
    exit;
}

$predId = $data['id'] ?? null;
if (!$predId) { echo "ERROR: No prediction ID. Full response above.\n"; exit; }
echo "  Prediction ID: $predId\n  Status: " . ($data['status'] ?? 'unknown') . "\n\n";

// Step 4: Poll
echo "--- Step 4: Polling for result ---\n";
for ($i = 1; $i <= 20; $i++) {
    sleep(5);
    [$pollRes, $pollCode, $pollErr] = curlGet(
        "https://api.pruna.ai/v1/predictions/status/$predId",
        ["apikey: $apiKey", "Accept: application/json"]
    );
    echo "  Poll $i | HTTP $pollCode | Response: " . substr($pollRes, 0, 400) . "\n";
    $pd = json_decode($pollRes, true);
    $status = strtolower($pd['status'] ?? '');
    if ($status === 'succeeded' || $status === 'completed') {
        echo "\n=== SUCCESS ===\n";
        echo "FULL response:\n" . json_encode($pd, JSON_PRETTY_PRINT) . "\n";
        $resultUrl = $pd['generation_url'] ?? ($pd['output'][0] ?? 'NOT FOUND');
        echo "\nRESULT URL: $resultUrl\n";
        exit;
    }
    if (in_array($status, ['failed','error','canceled'])) {
        echo "\n=== FAILED ===\n" . json_encode($pd, JSON_PRETTY_PRINT) . "\n";
        exit;
    }
}
echo "Timed out after 100s\n";
