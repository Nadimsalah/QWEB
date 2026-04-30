<?php
require_once 'conn.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if (session_status() === PHP_SESSION_NONE) session_start();

$appKey    = "532966";
$appSecret = "7AD6C8dWaQzf2GnjxTpm4eOB0bHe3yNT";
$token     = "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

$q    = isset($_GET['q'])    ? trim($_GET['q'])       : '';
$page = isset($_GET['page']) ? intval($_GET['page'])  : 1;
if ($page < 1) $page = 1;

if (strlen($q) < 1) {
    echo json_encode(['products' => []]);
    exit;
}

// ── Helper: format a raw AliExpress product array → QOON product ─────────
function formatAliProduct($p) {
    $price     = floatval($p['targetSalePrice'] ?? $p['salePrice'] ?? 0);
    $qoonPrice = round($price * 1.30, 2);
    return [
        'id'        => 'ALI_' . ($p['itemId'] ?? uniqid()),
        'name'      => $p['title'] ?? "AliExpress Product",
        'price'     => $qoonPrice,
        'oldPrice'  => round($qoonPrice * 1.2, 2),
        'img'       => $p['itemMainPic'] ?? "",
        'images'    => [$p['itemMainPic'] ?? ""],
        'desc'      => $p['title'] ?? "",
        'cat_id'    => 'ALI',
        'extra1'    => '',
        'extra2'    => '',
        'shop_logo' => 'https://ae01.alicdn.com/kf/S7a591cd6267f4010a3070bd2de984ea7S/480x480.png',
        'has_variants' => false,
        'source'    => 'aliexpress'
    ];
}

// ── 1. Try live AliExpress API ────────────────────────────────────────────
function callAliSearch($keyword, $page, $appKey, $appSecret, $accessToken) {
    $allParams = array_merge([
        "method"      => "aliexpress.ds.text.search",
        "app_key"     => $appKey,
        "timestamp"   => date("Y-m-d H:i:s"),
        "format"      => "json",
        "v"           => "2.0",
        "sign_method" => "md5",
        "session"     => $accessToken,
    ], [
        "keyWord"     => $keyword,
        "countryCode" => "MA",
        "currency"    => "MAD",
        "local"       => "en",
        "page_size"   => "20",
        "page_no"     => strval($page),
    ]);
    ksort($allParams);
    $sign = $appSecret;
    foreach ($allParams as $k => $v) {
        if (is_string($v) && "@" !== substr($v, 0, 1)) $sign .= "$k$v";
    }
    $sign .= $appSecret;
    $allParams["sign"] = strtoupper(md5($sign));

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://api-sg.aliexpress.com/sync",
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($allParams),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true) ?? [];
}

$products    = [];
$apiWorked   = false;
$searchResp  = callAliSearch($q, $page, $appKey, $appSecret, $token);

if (isset($searchResp['aliexpress_ds_text_search_response']['data']['products'])) {
    $prods = reset($searchResp['aliexpress_ds_text_search_response']['data']['products']) ?: [];
    foreach ($prods as $p) {
        $products[] = formatAliProduct($p);
    }
    $apiWorked = true;
}

// ── 2. Fallback: use cached ali_search_debug.json data ───────────────────
if (!$apiWorked || empty($products)) {
    $cacheFile = __DIR__ . '/ali_search_debug.json';
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $cachedProds = $cached['aliexpress_ds_text_search_response']['data']['products']['selection_search_product'] ?? [];

        // Filter by keyword (simple title match so results feel relevant)
        $qLower = strtolower($q);
        $filtered = array_filter($cachedProds, function($p) use ($qLower) {
            return stripos($p['title'] ?? '', $qLower) !== false;
        });

        // If no title match, return all cached products
        $toShow = !empty($filtered) ? array_values($filtered) : $cachedProds;

        // Paginate
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;
        $toShow  = array_slice($toShow, $offset, $perPage);

        foreach ($toShow as $p) {
            $products[] = formatAliProduct($p);
        }
    }
}

echo json_encode(['products' => $products]);
