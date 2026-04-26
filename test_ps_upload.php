<?php
// Quick test: can PHP shell_exec PowerShell to upload to imgbb?
$testFile = __DIR__ . '/vto_temp/ps_test.jpg';
if (!is_dir(__DIR__ . '/vto_temp')) mkdir(__DIR__ . '/vto_temp', 0755, true);

// Create tiny test image
$img = imagecreatetruecolor(50, 50);
$c   = imagecolorallocate($img, 255, 100, 0);
imagefill($img, 0, 0, $c);
imagejpeg($img, $testFile);
imagedestroy($img);

$key     = '2ed7d0e7f38f3e24f8b79cc97e63b5d1';
$winPath = str_replace('/', '\\', $testFile);

$ps = implode('; ', [
    "\$bytes = [System.IO.File]::ReadAllBytes('" . addslashes($winPath) . "')",
    "\$b64   = [Convert]::ToBase64String(\$bytes)",
    "\$body  = 'key=" . $key . "&image=' + [System.Uri]::EscapeDataString(\$b64)",
    "\$r     = Invoke-WebRequest -Uri 'https://api.imgbb.com/1/upload' -Method POST -Body \$body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 20",
    "Write-Output \$r.Content",
]);

$cmd    = 'powershell -NoProfile -NonInteractive -Command "' . str_replace('"', '\\"', $ps) . '"';
$result = shell_exec($cmd);

echo "shell_exec result:\n" . $result . "\n";
$json = json_decode(trim($result ?? ''), true);
if (isset($json['data']['url'])) {
    echo "\n✅ SUCCESS: " . $json['data']['url'] . "\n";
} else {
    echo "\n❌ FAILED\n";
    echo "CMD: " . $cmd . "\n";
}
?>
