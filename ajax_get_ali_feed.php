<?php
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

header('Content-Type: application/json');

session_start();
$appKey = "532966";
$appSecret = "7AD6C8dWaQzf2GnjxTpm4eOB0bHe3yNT";
// Fallback to the known token
$token     = "50000100827ezZgp7jnnaRwf9df2jpqaTpD9dcT1df32eaaBtxveHgwIXDOqM94vK3KQ";

$api = new AliExpressAPI($appKey, $appSecret, $token);

$feedName = "DS_NewArrivals";
$feedResp = $api->getFeedItemIds($feedName, "MA", 1, 5);

$itemIds = $feedResp['aliexpress_ds_feed_itemids_get_response']['result']['products']['number'] ?? [];

$products = [];
foreach ($itemIds as $id) {
    $productData = $api->getProductDetails($id, "MA", "MAD", "EN");
    
    $resp = $productData['aliexpress_ds_product_get_response']['result'] ?? null;
    if ($resp) {
        $images = $resp['ae_multimedia_info_dto']['image_urls'] ?? "";
        $imageArray = array_filter(array_map('trim', explode(';', $images)));
        $mainImage = !empty($imageArray) ? $imageArray[0] : "";
        
        // Debug raw response to file
        if (empty($products)) {
            file_put_contents('ali_raw_debug.json', json_encode($resp, JSON_PRETTY_PRINT));
        }

        $price = $resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'][0]['offer_sale_price'] ?? 0;
        $title = $resp['ae_item_base_info_dto']['subject'] ?? "Unknown Product";
        
        // Add 30% margin for QOON display
        $qoonPrice = floatval($price) * 1.30;

        $products[] = [
            'id' => 'ALI_' . $id,
            'name' => $title,
            'price' => round($qoonPrice, 2),
            'oldPrice' => round($qoonPrice * 1.2, 2), // Fake discount
            'img' => $mainImage,
            'images' => $imageArray,
            'desc' => $resp['ae_item_base_info_dto']['detail'] ?? "",
            'cat_id' => 'ALI',
            'extra1' => '',
            'extra2' => '',
            'shop_logo' => 'https://ae01.alicdn.com/kf/S7a591cd6267f4010a3070bd2de984ea7S/480x480.png', // Default Ali Express icon
            'has_variants' => count($resp['ae_item_sku_info_dtos']['ae_item_sku_info_d_t_o'] ?? []) > 1
        ];
    }
}

echo json_encode(['products' => $products]);

