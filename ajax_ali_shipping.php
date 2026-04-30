<?php
define('FROM_UI', true);
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$productId = $_GET['product_id'] ?? '';
$country   = strtoupper($_GET['country'] ?? 'MA');

if (!$productId) {
    echo json_encode(['error' => 'No product_id']); exit;
}

// Currency map per country
$currencyMap = [
    'MA' => 'MAD', 'US' => 'USD', 'GB' => 'GBP', 'DE' => 'EUR',
    'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR', 'AE' => 'AED',
    'SA' => 'SAR', 'EG' => 'EGY', 'TR' => 'TRY', 'CN' => 'CNY',
    'JP' => 'JPY', 'KR' => 'KRW', 'CA' => 'CAD', 'AU' => 'AUD',
    'BR' => 'BRL', 'IN' => 'INR', 'RU' => 'RUB', 'NG' => 'NGN',
    'SN' => 'XOF', 'TN' => 'TND', 'DZ' => 'DZD', 'LY' => 'LYD',
];
$currency = $currencyMap[$country] ?? 'USD';

$appKey    = "532966";
$appSecret = "7AD6C8dWaQzf2GnjxTpm4eOB0bHe3yNT";
$token     = "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

$api         = new AliExpressAPI($appKey, $appSecret, $token);
$productData = $api->getProductDetails($productId, $country, $currency);

if (isset($productData['error_response'])) {
    echo json_encode(['error' => $productData['error_response']['msg'] ?? 'API Error']); exit;
}

$resp = $productData['aliexpress_ds_product_get_response']['result'] ?? null;
if (!$resp) {
    echo json_encode(['error' => 'Invalid product structure']); exit;
}

// Extract first SKU price
$skus     = $resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? [];
$rawPrice = $skus[0]['offer_sale_price'] ?? 0;
$price    = round(floatval($rawPrice) * 1.30, 2);

// SKU prices map
$skuPrices = [];
foreach ($skus as $sku) {
    $skuPrice  = round(floatval($sku['offer_sale_price'] ?? 0) * 1.30, 2);
    $keyParts  = [];
    $props     = $sku['ae_sku_property_dtos']['ae_sku_property_d_t_o'] ?? [];
    foreach ($props as $prop) {
        $keyParts[] = $prop['property_value_definition_name'] ?? $prop['sku_property_value'] ?? 'Unknown';
    }
    sort($keyParts);
    $skuPrices[implode('||', $keyParts)] = $skuPrice;
}

// Shipping info
$logisticsInfo  = $resp['logistics_info_dto'] ?? [];
$storeInfo      = $resp['ae_store_info'] ?? [];
$countryMap     = [
    'CN' => 'China', 'US' => 'United States', 'TR' => 'Turkey',
    'DE' => 'Germany', 'FR' => 'France', 'GB' => 'United Kingdom',
    'IT' => 'Italy', 'ES' => 'Spain', 'JP' => 'Japan', 'KR' => 'South Korea',
    'MA' => 'Morocco', 'AE' => 'UAE', 'SA' => 'Saudi Arabia',
    'EG' => 'Egypt', 'DZ' => 'Algeria', 'TN' => 'Tunisia', 'NG' => 'Nigeria',
    'SN' => 'Senegal', 'CA' => 'Canada', 'AU' => 'Australia',
    'BR' => 'Brazil', 'IN' => 'India', 'RU' => 'Russia',
];
$shipFromCode   = $storeInfo['store_country_code'] ?? 'CN';
$shipFrom       = $countryMap[$shipFromCode] ?? $shipFromCode;
$deliveryDays   = isset($logisticsInfo['delivery_time']) ? intval($logisticsInfo['delivery_time']) * 7 : null;
$deliveryText   = $deliveryDays
    ? "Estimated delivery: {$deliveryDays}–" . ($deliveryDays + 5) . " days to " . ($countryMap[$country] ?? $country)
    : 'Standard Shipping to ' . ($countryMap[$country] ?? $country);
$storeName      = $storeInfo['store_name'] ?? '';
$storeRating    = $storeInfo['item_as_described_rating'] ?? null;
$shippingRating = $storeInfo['shipping_speed_rating'] ?? null;

echo json_encode([
    'price'         => $price,
    'oldPrice'      => round($price * 1.2, 2),
    'currency'      => $currency,
    'skuPrices'     => $skuPrices,
    'shipping' => [
        'from'           => $shipFrom,
        'deliveryText'   => $deliveryText,
        'storeName'      => $storeName,
        'storeRating'    => $storeRating,
        'shippingRating' => $shippingRating,
    ],
]);

