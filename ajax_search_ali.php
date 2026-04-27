<?php
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
$appKey = getenv('ALI_APP_KEY') ?: "532966";
$appSecret = getenv('ALI_APP_SECRET') ?: "cTzCrj5XNUjx9lXKsrD6Fo1AuUf1Th2J";
$token = "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if($page < 1) $page = 1;

if (strlen($q) < 1) {
    echo json_encode(['products' => []]);
    exit;
}

function executeAliSearchRequest($keyword, $page, $appKey, $appSecret, $accessToken) {
    $sysParams = [
        "method" => "aliexpress.ds.text.search",
        "app_key" => $appKey,
        "timestamp" => date("Y-m-d H:i:s"),
        "format" => "json",
        "v" => "2.0",
        "sign_method" => "md5",
        "session" => $accessToken
    ];
    $apiParams = [
        "keyWord" => $keyword,
        "countryCode" => "MA",
        "currency" => "MAD",
        "local" => "en",
        "page_size" => "20",
        "page_no" => strval($page)
    ];
    $allParams = array_merge($sysParams, $apiParams);
    ksort($allParams);
    $stringToBeSigned = $appSecret;
    foreach ($allParams as $k => $v) {
        if (is_string($v) && "@" != substr($v, 0, 1)) {
            $stringToBeSigned .= "$k$v";
        }
    }
    $stringToBeSigned .= $appSecret;
    $allParams["sign"] = strtoupper(md5($stringToBeSigned));
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-sg.aliexpress.com/sync");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($allParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($res, true) ?? [];
}

$searchResp = executeAliSearchRequest($q, $page, $appKey, $appSecret, $token);

$aliProducts = [];
if (isset($searchResp['aliexpress_ds_text_search_response']['data']['products'])) {
    $prods = $searchResp['aliexpress_ds_text_search_response']['data']['products'];
    $aliProducts = reset($prods) ?: [];
}

$products = [];
foreach ($aliProducts as $p) {
    $price = $p['targetSalePrice'] ?? 0;
    $qoonPrice = floatval($price) * 1.30;
    
    $products[] = [
        'id' => 'ALI_' . $p['itemId'],
        'name' => $p['title'] ?? "Unknown Product",
        'price' => round($qoonPrice, 2),
        'oldPrice' => round($qoonPrice * 1.2, 2),
        'img' => $p['itemMainPic'] ?? "",
        'images' => [$p['itemMainPic'] ?? ""], 
        'desc' => $p['title'] ?? "",
        'cat_id' => 'ALI',
        'extra1' => '',
        'extra2' => '',
        'shop_logo' => 'https://ae01.alicdn.com/kf/S7a591cd6267f4010a3070bd2de984ea7S/480x480.png',
        'has_variants' => false 
    ];
}

file_put_contents('ali_search_debug_2.txt', "Found " . count($aliProducts) . " aliProducts, mapped to " . count($products) . " products.");

echo json_encode(['products' => $products]);
