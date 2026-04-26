<?php
$dummyPath = __DIR__ . '/dummy.jpg';
if (!file_exists($dummyPath)) {
    file_put_contents($dummyPath, base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs='));
}

$cfile = new CURLFile($dummyPath);
$ch = curl_init('https://freeimage.host/api/1/upload');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'key' => '6d207e02198a847aa98d0a2a901485a5',
    'action' => 'upload',
    'source' => $cfile,
    'format' => 'json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo "Response: " . $response . "\n";
