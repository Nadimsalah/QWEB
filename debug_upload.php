<?php
// Debug script — open in browser: http://localhost/userDriver/UserDriverApi/debug_upload.php
header('Content-Type: text/plain; charset=utf-8');

echo "=== XAMPP PHP Upload Debug ===\n\n";

// 1. Download a test image
echo "1. Downloading test image from picsum.photos...\n";
$ch = curl_init("https://picsum.photos/100/150.jpg");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$imgData = curl_exec($ch);
$err     = curl_error($ch);
$code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP: $code | Error: " . ($err ?: 'none') . " | Size: " . strlen($imgData) . " bytes\n\n";

if (!$imgData || strlen($imgData) < 100) {
    echo "ERROR: Cannot even download from picsum.photos. Your server has no outbound internet access!\n";
    echo "This means Imgur, 0x0.st, and all other upload services will also fail.\n";
    echo "\nSOLUTION: You need to either:\n";
    echo "  a) Deploy to a real server (qoon.app) instead of localhost\n";
    echo "  b) Use a VTO API that accepts base64 images directly\n";
    exit;
}

// 2. Test Imgur
echo "2. Testing Imgur upload...\n";
$base64 = base64_encode($imgData);
$ch = curl_init("https://api.imgur.com/3/image");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['image' => $base64, 'type' => 'base64'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => ["Authorization: Client-ID 546c25a59c58ad7"],
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "   HTTP: $code | Error: " . ($err ?: 'none') . "\n";
echo "   Response: " . substr($res, 0, 200) . "\n";
$d = json_decode($res, true);
if ($d['success'] ?? false) { echo "   ✅ Imgur SUCCESS: " . $d['data']['link'] . "\n\n"; }
else { echo "   ❌ Imgur FAILED\n\n"; }

// 3. Test 0x0.st
echo "3. Testing 0x0.st upload...\n";
$tmpPath = tempnam(sys_get_temp_dir(), 'vto_');
file_put_contents($tmpPath, $imgData);
$cfile = new CURLFile($tmpPath, 'image/jpeg', 'image.jpg');
$ch = curl_init("https://0x0.st");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['file' => $cfile],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmpPath);
echo "   HTTP: $code | Error: " . ($err ?: 'none') . "\n";
echo "   Response: " . substr($res ?? '', 0, 200) . "\n";
if ($code == 200 && strpos(trim($res ?? ''), 'https://') === 0) { echo "   ✅ 0x0.st SUCCESS: " . trim($res) . "\n\n"; }
else { echo "   ❌ 0x0.st FAILED\n\n"; }

// 4. Test tmpfiles.org
echo "4. Testing tmpfiles.org upload...\n";
$tmpPath = tempnam(sys_get_temp_dir(), 'vto_');
file_put_contents($tmpPath, $imgData);
$cfile = new CURLFile($tmpPath, 'image/jpeg', 'image.jpg');
$ch = curl_init("https://tmpfiles.org/api/v1/upload");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => ['file' => $cfile],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
]);
$res  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmpPath);
echo "   HTTP: $code | Error: " . ($err ?: 'none') . "\n";
echo "   Response: " . substr($res ?? '', 0, 300) . "\n";
$d = json_decode($res, true);
if (isset($d['data']['url'])) { echo "   ✅ tmpfiles.org SUCCESS: " . $d['data']['url'] . "\n\n"; }
else { echo "   ❌ tmpfiles.org FAILED\n\n"; }

echo "=== Done ===\n";
?>
