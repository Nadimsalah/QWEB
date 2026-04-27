<?php
define('FROM_UI', true);
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=7200'); // browser caches 2 hrs
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'No product_id provided']);
    exit;
}

$productId  = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['product_id']); // sanitize
$cacheDir   = __DIR__ . '/cache/products/';
$cacheFile  = $cacheDir . $productId . '.json';
$cacheTTL   = 2 * 60 * 60; // 2 hours

// ── Serve from cache if fresh ──────────────────────────────────────────────
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    header('X-Cache: HIT');
    echo file_get_contents($cacheFile);
    exit;
}

// ── Cache miss — call AliExpress API ──────────────────────────────────────
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

$appKey    = "532966";
$appSecret = "OuzUIdMqmJ9qsnkid6w9RWLB7eNmwDjB";
$token     = $_SESSION['ali_access_token'] ?? "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

$api         = new AliExpressAPI($appKey, $appSecret, $token);
$productData = $api->getProductDetails($productId);


if (isset($productData['error_response'])) {
    echo json_encode(['error' => $productData['error_response']['msg'] ?? 'API Error']);
    exit;
}

$resp = $productData['aliexpress_ds_product_get_response']['result'] ?? null;
if (!$resp) {
    echo json_encode(['error' => 'Invalid product structure', 'raw' => $productData]);
    exit;
}

// Clean up product data for the frontend
$images = $resp['ae_multimedia_info_dto']['image_urls'] ?? "";
$imageArray = array_filter(array_map('trim', explode(';', $images)));
// Limit to 20 images max — beyond that, nobody scrolls
$imageArray = array_slice($imageArray, 0, 20);

$mainImage = !empty($imageArray) ? $imageArray[0] : "";
$rawPrice = $resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'][0]['offer_sale_price'] ?? 0;
$title = $resp['ae_item_base_info_dto']['subject'] ?? "Unknown Product";
$desc1 = $resp['ae_item_base_info_dto']['detail'] ?? "";
$desc2 = $resp['ae_item_base_info_dto']['mobile_detail'] ?? "";
// Prefer mobile_detail (shorter); cap at 120KB to avoid massive JSON
$desc = strlen($desc2) > 200 ? $desc2 : $desc1;
if (strlen($desc) > 120000) $desc = substr($desc, 0, 120000) . '<!-- trimmed -->';

// Add 30% margin for QOON display
$qoonPrice = round(floatval($rawPrice) * 1.30, 2);

// Extract variants
$variantsData = [];
$skus = $resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
foreach ($skus as $sku) {
    $props = $sku['ae_sku_property_dtos']['ae_sku_property_d_t_o'] ?? [];
    foreach ($props as $prop) {
        $groupName = $prop['sku_property_name'] ?? 'Variant';
        $valName = $prop['property_value_definition_name'] ?? $prop['sku_property_value'] ?? 'Unknown';
        $img = $prop['sku_image'] ?? null;
        
        if (!isset($variantsData[$groupName])) {
            $variantsData[$groupName] = [];
        }
        
        $exists = false;
        foreach ($variantsData[$groupName] as $v) {
            if ($v['value'] === $valName) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $variantsData[$groupName][] = [
                'value' => $valName,
                'image' => $img
            ];
        }
    }
}

$formattedVariants = [];
foreach ($variantsData as $name => $options) {
    $formattedVariants[] = [
        'name' => $name,
        'options' => $options
    ];
}

// Extract real shipping info
$logisticsInfo = $resp['logistics_info_dto'] ?? [];
$storeInfo     = $resp['ae_store_info'] ?? [];
$packageInfo   = $resp['package_info_dto'] ?? [];

$countryMap = [
    'CN' => 'China', 'US' => 'United States', 'TR' => 'Turkey',
    'DE' => 'Germany', 'FR' => 'France', 'GB' => 'United Kingdom',
    'IT' => 'Italy', 'ES' => 'Spain', 'JP' => 'Japan', 'KR' => 'South Korea',
    'MA' => 'Morocco', 'AE' => 'UAE', 'SA' => 'Saudi Arabia',
];
$shipFromCode = $storeInfo['store_country_code'] ?? 'CN';
$shipFrom = $countryMap[$shipFromCode] ?? $shipFromCode;
$deliveryDays = isset($logisticsInfo['delivery_time']) ? intval($logisticsInfo['delivery_time']) * 7 : null;
$deliveryText = $deliveryDays ? "Estimated delivery: {$deliveryDays}–" . ($deliveryDays + 5) . " days" : 'Standard Shipping';
$storeName = $storeInfo['store_name'] ?? 'AliExpress Store';
$storeRating = $storeInfo['item_as_described_rating'] ?? null;
$shippingRating = $storeInfo['shipping_speed_rating'] ?? null;
$weightKg = !empty($packageInfo['gross_weight']) ? floatval($packageInfo['gross_weight']) . ' kg' : null;

// Map SKUs to specific prices
$skuPrices = [];
foreach ($skus as $sku) {
    $price = round(floatval($sku['offer_sale_price'] ?? 0) * 1.30, 2);
    $keyParts = [];
    $props = $sku['ae_sku_property_dtos']['ae_sku_property_d_t_o'] ?? [];
    foreach ($props as $prop) {
        $valName = $prop['property_value_definition_name'] ?? $prop['sku_property_value'] ?? 'Unknown';
        $keyParts[] = $valName;
    }
    // Sort key parts alphabetically so we can match them regardless of order
    sort($keyParts);
    $key = implode('||', $keyParts);
    $skuPrices[$key] = $price;
}

// ── Build response, save to cache, and output ─────────────────────────────
$jsonOutput = json_encode([
    'id'         => $productId,
    'title'      => $title,
    'price'      => $qoonPrice,
    'oldPrice'   => round($qoonPrice * 1.2, 2),
    'images'     => array_values($imageArray),
    'main_image' => $mainImage,
    'desc'       => $desc,
    'variants'   => $formattedVariants,
    'skuPrices'  => $skuPrices,
    'has_variants' => count($skus) > 1,
    'shipping'   => [
        'from'           => $shipFrom,
        'deliveryText'   => $deliveryText,
        'storeName'      => $storeName,
        'storeRating'    => $storeRating,
        'shippingRating' => $shippingRating,
        'weight'         => $weightKg,
    ],
]);

header('X-Cache: MISS');
file_put_contents($cacheFile, $jsonOutput); // Write cache
echo $jsonOutput;
