<?php
// Quick token test - delete this file after testing
$appKey    = "532966";
$appSecret = "7AD6C8dWaQzf2GnjxTpm4eOB0bHe3yNT";
$token     = "WwPCxLaIZ8MnCtOUrsCgI8yHI84O8qAQ";

$sysParams = [
    "method"      => "aliexpress.ds.text.search",
    "app_key"     => $appKey,
    "timestamp"   => date("Y-m-d H:i:s"),
    "format"      => "json",
    "v"           => "2.0",
    "sign_method" => "md5",
    "session"     => $token
];
$apiParams = [
    "keyWord"     => "shoes",
    "countryCode" => "MA",
    "currency"    => "MAD",
    "local"       => "en",
    "page_size"   => "5",
    "page_no"     => "1"
];
$allParams = array_merge($sysParams, $apiParams);
ksort($allParams);
$sign = $appSecret;
foreach ($allParams as $k => $v) {
    if (is_string($v) && "@" != substr($v, 0, 1)) $sign .= "$k$v";
}
$sign .= $appSecret;
$allParams["sign"] = strtoupper(md5($sign));

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-sg.aliexpress.com/sync");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($allParams));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
if (isset($data['error_response'])) {
    echo "❌ ERROR: " . $data['error_response']['msg'] . " (code: " . $data['error_response']['code'] . ")\n";
} elseif (isset($data['aliexpress_ds_text_search_response']['data']['products'])) {
    $prods = reset($data['aliexpress_ds_text_search_response']['data']['products']);
    echo "✅ SUCCESS! Got " . count($prods) . " products.\n";
    echo "First product: " . ($prods[0]['title'] ?? 'N/A') . "\n";
} else {
    echo "⚠️ Unexpected response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
