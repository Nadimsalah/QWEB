<?php
// ══ AliExpress Debug — DELETE THIS FILE AFTER USE ══
// Visit: https://qoon.app/userDriver/UserDriverApi/ali_debug.php

define('FROM_UI', true);
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="background:#111;color:#0f0;padding:20px;font-size:13px;">';
echo "=== AliExpress Deployment Debug ===\n\n";

// 1. PHP + cURL info
echo "PHP version:     " . PHP_VERSION . "\n";
echo "cURL enabled:    " . (function_exists('curl_init') ? 'YES' : 'NO ❌') . "\n";
echo "Server IP:       " . ($_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname())) . "\n\n";

// 2. Cache dir writability
$cacheDir = __DIR__ . '/cache/products/';
echo "Cache dir:       $cacheDir\n";
echo "Cache writable:  " . (is_writable(dirname($cacheDir)) ? 'YES' : 'NO ❌ — run: chmod 755 cache/') . "\n\n";

// 3. Try direct API call
$appKey    = "532966";
$appSecret = "OuzUIdMqmJ9qsnkid6w9RWLB7eNmwDjB";
$token     = "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

echo "App Key:         $appKey\n";
echo "Token (first 20): " . substr($token, 0, 20) . "...\n\n";

// 4. Raw cURL test to AliExpress
echo "Testing direct cURL to api-sg.aliexpress.com...\n";
$ch = curl_init("https://api-sg.aliexpress.com/sync");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['test' => '1']);
$res = curl_exec($ch);
$err = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo "cURL ERROR ❌: $err\n";
    echo "→ Server may be blocking outbound connections\n";
} else {
    echo "HTTP response code: $httpCode ✅\n";
    echo "Response (first 200 chars): " . substr($res, 0, 200) . "\n";
}

echo "\n";

// 5. Real API test — single product
echo "Testing AliExpress API with product 1005011974577879...\n";
$api = new AliExpressAPI($appKey, $appSecret, $token);
$result = $api->getProductDetails("1005011974577879");

if (isset($result['error'])) {
    echo "API ERROR ❌: " . $result['error'] . "\n";
} elseif (isset($result['error_response'])) {
    $err = $result['error_response'];
    echo "API ERROR RESPONSE ❌:\n";
    echo "  Code:    " . ($err['code'] ?? 'n/a') . "\n";
    echo "  Message: " . ($err['msg'] ?? 'n/a') . "\n";
    echo "  Sub-msg: " . ($err['sub_msg'] ?? 'n/a') . "\n";
    echo "\n→ If code is 27 or 'invalid session': TOKEN IS EXPIRED\n";
    echo "→ If code is 15 or 'IP restricted':   ADD SERVER IP TO WHITELIST\n";
} else {
    $resp = $result['aliexpress_ds_product_get_response']['result'] ?? null;
    if ($resp) {
        echo "SUCCESS ✅ — Product found: " . ($resp['ae_item_base_info_dto']['subject'] ?? 'no title') . "\n";
    } else {
        echo "Unexpected structure:\n" . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n=== End Debug ===\n";
echo '</pre>';
?>
