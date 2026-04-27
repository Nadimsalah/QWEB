<?php
define('FROM_UI', true);
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();

$country  = $_POST['country']  ?? $_GET['country']  ?? 'MA';
$currency = $_POST['currency'] ?? $_GET['currency'] ?? 'MAD';
$page     = max(1, intval($_POST['page'] ?? $_GET['page'] ?? 1));
$debug    = isset($_GET['debug']);

// ── Get image bytes — support both file upload and base64 JSON ─────────────
$imageBytes = null;

if (!empty($_FILES['image']['tmp_name'])) {
    // Preferred: multipart file upload from browser
    $imageBytes = file_get_contents($_FILES['image']['tmp_name']);
} else {
    // Fallback: JSON body with base64
    $input    = json_decode(file_get_contents('php://input'), true);
    $imageB64 = $input['image'] ?? '';
    $country  = $input['country']  ?? $country;
    $currency = $input['currency'] ?? $currency;
    $page     = max(1, intval($input['page'] ?? $page));
    if (!empty($imageB64)) {
        if (strpos($imageB64, ',') !== false) $imageB64 = explode(',', $imageB64, 2)[1];
        $imageBytes = base64_decode($imageB64, true);
    }
}

if (!$imageBytes || strlen($imageBytes) < 100) {
    echo json_encode(['error' => 'No valid image received. Size: ' . strlen($imageBytes ?? '')]); exit;
}

$appKey    = "532966";
$appSecret = "OuzUIdMqmJ9qsnkid6w9RWLB7eNmwDjB";
$token     = $_SESSION['ali_access_token'] ?? "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

// ── Build system + API params (NO image here for signing) ──────────────────
$params = [
    "method"          => "aliexpress.ds.image.search",
    "app_key"         => $appKey,
    "timestamp"       => date("Y-m-d H:i:s"),
    "format"          => "json",
    "v"               => "2.0",
    "sign_method"     => "md5",
    "session"         => $token,
    "shpt_to"         => $country,
    "target_currency" => $currency,
    "target_language" => "EN",
    "page_no"         => (string)$page,
    "page_size"       => "20",
];
ksort($params);

// ── Sign (image_file_bytes excluded from signature) ────────────────────────
$signStr = $appSecret;
foreach ($params as $k => $v) $signStr .= "$k$v";
$signStr   .= $appSecret;
$params['sign'] = strtoupper(md5($signStr));

// ── Build multipart body ───────────────────────────────────────────────────
$boundary = '----AliImgBoundary' . uniqid();
$body     = '';

// All text params
foreach ($params as $k => $v) {
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"{$k}\"\r\n\r\n";
    $body .= "{$v}\r\n";
}

// Image as binary file field
$body .= "--{$boundary}\r\n";
$body .= "Content-Disposition: form-data; name=\"image_file_bytes\"; filename=\"upload.jpg\"\r\n";
$body .= "Content-Type: image/jpeg\r\n\r\n";
$body .= $imageBytes . "\r\n";
$body .= "--{$boundary}--\r\n";

// ── POST to AliExpress ─────────────────────────────────────────────────────
$ch = curl_init("https://api-sg.aliexpress.com/sync");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ["Content-Type: multipart/form-data; boundary={$boundary}"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 30,
]);
$res = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) { echo json_encode(['error' => 'cURL: ' . $err]); exit; }

$data = json_decode($res, true);
if (!$data) { echo json_encode(['error' => 'Bad API response', 'raw' => substr($res,0,500)]); exit; }

if ($debug) { echo json_encode($data, JSON_PRETTY_PRINT); exit; }

// Error from API
if (isset($data['error_response'])) {
    echo json_encode(['error' => $data['error_response']['msg'] ?? 'API Error', 'code' => $data['error_response']['code'] ?? '']); exit;
}

// ── Parse products ─────────────────────────────────────────────────────────
$resp  = $data['aliexpress_ds_image_search_response'] ?? [];

// Correct key: traffic_image_product_d_t_o (not 'product'!)
$items = $resp['data']['products']['traffic_image_product_d_t_o']
      ?? $resp['data']['products']['product']
      ?? [];

if (!is_array($items)) $items = [];

$products = array_map(function($p) {
    $raw         = floatval($p['target_sale_price'] ?? $p['sale_price'] ?? $p['min_price'] ?? 0);
    $price       = round($raw * 1.30, 2);
    $oldPriceRaw = floatval($p['target_original_price'] ?? $p['original_price'] ?? 0);
    $oldPrice    = $oldPriceRaw > 0 ? round($oldPriceRaw * 1.30, 2) : round($price * 1.25, 2);
    return [
        'id'       => (string)($p['product_id'] ?? ''),
        'name'     => $p['product_title'] ?? 'Product',
        'img'      => $p['product_main_image_url'] ?? '',
        'price'    => $price . ' MAD',
        'oldPrice' => $oldPrice . ' MAD',
        'discount' => $p['discount'] ?? null,
        'rating'   => $p['evaluate_rate'] ?? null,
        'sold'     => $p['lastest_volume'] ?? null,
    ];
}, $items);

echo json_encode([
    'products' => $products,
    'total'    => $resp['data']['total_record_count'] ?? count($products),
    'page'     => $page,
]);
