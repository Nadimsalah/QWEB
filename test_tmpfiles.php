<?php
$dummyPath = __DIR__ . '/dummy.jpg';
if (!file_exists($dummyPath)) {
    file_put_contents($dummyPath, base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs='));
}

$cfile = new CURLFile($dummyPath);
$ch = curl_init('https://tmpfiles.org/api/v1/upload');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['file' => $cfile]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$response = curl_exec($ch);
echo "Response: " . $response . "\n";
