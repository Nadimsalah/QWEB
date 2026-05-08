<?php
require_once 'aliexpress_config.php';

// This is the callback page where AliExpress will redirect you after you log in to authorize the app.
// It captures the 'code' and exchanges it for an Access Token.

if (isset($_GET['code'])) {
    $authCode = $_GET['code'];
    echo "<h3>Success! We received the Authorization Code:</h3>";
    echo "<p><b>" . htmlspecialchars($authCode) . "</b></p>";
    
    if (empty(ALIEXPRESS_APP_KEY)) {
        echo "<p style='color:red;'>Error: App Key is missing in aliexpress_config.php. Please add it.</p>";
        exit;
    }

    // Exchange the code for an Access Token
    $timestamp = time() * 1000;
    
    $params = [
        'method' => '/auth/token/create',
        'app_key' => ALIEXPRESS_APP_KEY,
        'sign_method' => 'md5',
        'timestamp' => $timestamp,
        'code' => $authCode,
        'grant_type' => 'authorization_code'
    ];
    
    ksort($params);
    $sign_str = ALIEXPRESS_APP_SECRET . '/auth/token/create';
    foreach ($params as $k => $v) {
        $sign_str .= $k . $v;
    }
    $sign_str .= ALIEXPRESS_APP_SECRET;
    $sign = strtoupper(md5($sign_str));
    $params['sign'] = $sign;

    $url = 'https://api-sg.aliexpress.com/rest/auth/token/create';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        echo "<h3 style='color:green;'>Access Token generated successfully!</h3>";
        echo "<p><b>Access Token:</b> " . htmlspecialchars($data['access_token']) . "</p>";
        echo "<p>Please copy this Access Token and send it to the assistant.</p>";
    } else {
        echo "<h3 style='color:red;'>Failed to generate token.</h3>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    }
} else {
    echo "No code received.";
}
?>
