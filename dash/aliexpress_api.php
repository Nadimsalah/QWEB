<?php
require_once __DIR__ . '/aliexpress_config.php';

function getAliExpressProducts($page_no = 1) {
    $method = 'aliexpress.ds.recommend.feed.get';
    $timestamp = date('Y-m-d H:i:s');
    $params = [
        'method' => $method,
        'app_key' => ALIEXPRESS_APP_KEY,
        'sign_method' => 'md5',
        'timestamp' => $timestamp,
        'format' => 'json',
        'v' => '2.0',
        'sign_source' => 'top',
        'session' => ALIEXPRESS_ACCESS_TOKEN,
        'country' => 'MA',
        'target_currency' => 'MAD',
        'target_language' => 'EN',
        'page_no' => (string)$page_no,
        'page_size' => '10',
        'feed_name' => 'DS_Best_selling'
    ];
    
    ksort($params);
    $sign_str = ALIEXPRESS_APP_SECRET;
    foreach ($params as $k => $v) {
        $sign_str .= $k . $v;
    }
    $sign_str .= ALIEXPRESS_APP_SECRET;
    $params['sign'] = strtoupper(md5($sign_str));

    $ch = curl_init('https://api-sg.aliexpress.com/sync');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5); 
    
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    $products = [];
    
    // Check if real API returned products
    if (isset($data['aliexpress_ds_recommend_feed_get_response']['result']['products']) && !empty($data['aliexpress_ds_recommend_feed_get_response']['result']['products'])) {
        $items = $data['aliexpress_ds_recommend_feed_get_response']['result']['products'];
        foreach ($items as $item) {
            $products[] = [
                'id' => $item['product_id'] ?? rand(1000,9999),
                'name' => $item['product_title'] ?? 'Global Product',
                'price' => isset($item['target_sale_price']) ? floatval($item['target_sale_price']) * 1.2 : 0, 
                'oldPrice' => isset($item['target_original_price']) ? floatval($item['target_original_price']) * 1.2 : null,
                'img' => $item['product_main_image_url'] ?? '',
                'shopName' => 'AliExpress',
                'shopLogo' => 'https://ui-avatars.com/api/?name=Ali&background=E62E04&color=fff',
                'desc' => 'High quality product shipped globally.',
            ];
        }
    }
    
    return $products;
}

function getAliExpressSearch($query) {
    // For now, AliExpress Dropshipping API does not natively expose a keyword search without Affiliate scopes.
    // If the real search API is implemented later, it will go here.
    return [];
}
?>
