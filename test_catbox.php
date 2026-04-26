<?php
$storageDir = __DIR__ . '/vto_temp/';
if (!is_dir($storageDir)) @mkdir($storageDir, 0755, true);

$dummyPath = $storageDir . 'dummy.jpg';
if (!file_exists($dummyPath)) {
    // create a 1x1 black pixel image
    file_put_contents($dummyPath, base64_decode('R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs='));
}

$cfile = new CURLFile($dummyPath);
$ch = curl_init('https://catbox.moe/user/api.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'reqtype' => 'fileupload',
    'fileToUpload' => $cfile
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
echo "Catbox response: " . $response . "\n";
