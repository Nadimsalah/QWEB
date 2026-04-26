<?php
header('Content-Type: text/plain');

// Test 1: Can PHP make any outbound HTTPS call at all?
echo "=== Test 1: file_get_contents to httpbin ===\n";
$ctx = stream_context_create([
    'http' => ['timeout' => 10, 'ignore_errors' => true],
    'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
]);
$r = @file_get_contents('https://httpbin.org/get', false, $ctx);
echo $r ? "file_get_contents OK (" . strlen($r) . " bytes)\n" : "file_get_contents FAILED\n";

// Test 2: cURL to httpbin
echo "\n=== Test 2: cURL to httpbin ===\n";
$ch = curl_init('https://httpbin.org/get');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10, CURLOPT_SSL_VERIFYPEER=>false]);
$r2 = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
echo $r2 ? "cURL OK (" . strlen($r2) . " bytes)\n" : "cURL FAILED: $err\n";

// Test 3: file.io upload (no key needed)
echo "\n=== Test 3: file.io upload ===\n";
$tmpFile = tempnam(sys_get_temp_dir(), 'vto') . '.txt';
file_put_contents($tmpFile, 'test content');
$cfile = new CURLFile($tmpFile);
$ch3 = curl_init('https://file.io/?expires=1d');
curl_setopt_array($ch3, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => ['file' => $cfile],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$r3  = curl_exec($ch3);
$err3 = curl_error($ch3);
curl_close($ch3);
echo $r3 ? "file.io OK: $r3\n" : "file.io FAILED: $err3\n";
unlink($tmpFile);

// Test 4: allow_url_fopen
echo "\n=== Test 4: PHP Config ===\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? 'ON' : 'OFF') . "\n";
echo "curl enabled: "    . (function_exists('curl_init') ? 'YES' : 'NO') . "\n";
echo "PHP version: "     . PHP_VERSION . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
?>
