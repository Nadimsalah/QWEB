<?php
require_once 'aliexpress_config.php';

function testAliExpressAPI() {
    $method = 'aliexpress.ds.product.get';
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
        'product_id' => '1005011911661082',
        'target_currency' => 'MAD',
        'target_language' => 'EN',
        'ship_to_country' => 'MA'
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
    
    $res = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP CODE: " . $httpcode . "<br><br>";
    echo "RESPONSE:<br>";
    echo "<pre>" . htmlspecialchars($res) . "</pre>";
}

testAliExpressAPI();
?>
