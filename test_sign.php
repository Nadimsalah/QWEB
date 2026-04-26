<?php
$app_key = '532966';
$secret = 'OuzUIdMqmJ9qsnkid6w9RWLB7eNmwDjB';
$code = 'EXPIRED_CODE_123';
$timestamp = time() * 1000;

// Test 1: MD5 with secret wrapper and method prepend
$params = [
    'app_key' => $app_key,
    'sign_method' => 'md5',
    'timestamp' => $timestamp,
    'code' => $code,
    'grant_type' => 'authorization_code'
];
ksort($params);
$sign_str = $secret . '/auth/token/create';
foreach ($params as $k => $v) {
    $sign_str .= $k . $v;
}
$sign_str .= $secret;
$params['sign'] = strtoupper(md5($sign_str));

$ch = curl_init('https://api-sg.aliexpress.com/rest/auth/token/create');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
echo 'Test 1 (MD5): ' . curl_exec($ch) . "<br>";

// Test 2: HMAC_SHA256
$params2 = [
    'app_key' => $app_key,
    'sign_method' => 'hmac_sha256',
    'timestamp' => $timestamp,
    'code' => $code,
    'grant_type' => 'authorization_code'
];
ksort($params2);
$sign_str2 = '/auth/token/create';
foreach ($params2 as $k => $v) {
    $sign_str2 .= $k . $v;
}
$params2['sign'] = strtoupper(hash_hmac('sha256', $sign_str2, $secret));

$ch2 = curl_init('https://api-sg.aliexpress.com/rest/auth/token/create');
curl_setopt($ch2, CURLOPT_POST, 1);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($params2));
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
echo 'Test 2 (HMAC_SHA256): ' . curl_exec($ch2) . "<br>";

// Test 3: Top API legacy
$params3 = [
    'method' => 'aliexpress.auth.token.create',
    'app_key' => $app_key,
    'sign_method' => 'md5',
    'timestamp' => date('Y-m-d H:i:s'),
    'code' => $code,
    'grant_type' => 'authorization_code'
];
ksort($params3);
$sign_str3 = $secret;
foreach ($params3 as $k => $v) {
    $sign_str3 .= $k . $v;
}
$sign_str3 .= $secret;
$params3['sign'] = strtoupper(md5($sign_str3));

$ch3 = curl_init('https://api-sg.aliexpress.com/sync');
curl_setopt($ch3, CURLOPT_POST, 1);
curl_setopt($ch3, CURLOPT_POSTFIELDS, http_build_query($params3));
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
echo 'Test 3 (Legacy TOP): ' . curl_exec($ch3) . "<br>";
