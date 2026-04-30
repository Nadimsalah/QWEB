<?php
// ══ QOON — AliExpress Auto-Token Generator (Dropshipping GOP Version) ══
define('FROM_UI', true);
require_once 'conn.php';
require_once 'includes/AliExpressAPI.php';

$appKey = "532966";
$appSecret = "7AD6C8dWaQzf2GnjxTpm4eOB0bHe3yNT";

// The exact Callback URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$callbackUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . explode('?', $_SERVER['REQUEST_URI'])[0];

$message = "";
$msgType = "";

// ── 1. Exchanging Code for Token ──
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // We must use the IOP (rest) token creation endpoint for Dropshipping apps
    $apiUrl = "https://api-sg.aliexpress.com/rest";
    $apiName = "/auth/token/create";
    
    // Build GOP signature (IopClient signature method)
    $params = [
        "method" => $apiName,
        "app_key" => $appKey,
        "timestamp" => round(microtime(true) * 1000), // IOP uses millisecond timestamp
        "sign_method" => "sha256",
        "code" => $code
    ];
    
    ksort($params);
    $stringToBeSigned = $apiName; // IOP protocol prepends the API name
    foreach ($params as $k => $v) {
        $stringToBeSigned .= "$k$v";
    }
    
    // IOP uses HMAC_SHA256 with the appSecret
    $sign = strtoupper(hash_hmac("sha256", $stringToBeSigned, $appSecret));
    $params["sign"] = $sign;
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $parsed = json_decode($response, true);
    
    // Check if we got an access token
    if (isset($parsed['access_token'])) {
        $newToken = $parsed['access_token'];
        
        $filesToUpdate = [
            'ajax_get_ali_product.php', 'ajax_search_ali.php', 'ajax_get_ali_feed.php',
            'ajax_image_search.php', 'ajax_ali_shipping.php', 'ali_debug.php'
        ];
        $updated = 0;
        foreach ($filesToUpdate as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $content = preg_replace('/\$token\s*=\s*"[^"]*";/', '$token     = "' . $newToken . '";', $content);
                file_put_contents($path, $content);
                $updated++;
            }
        }
        $message = "✅ SUCCESS! Token generated and updated in $updated files.";
        $msgType = "success";
    } else {
        $message = "❌ Token Exchange Failed. Code received, but API said: " . htmlspecialchars($response);
        $msgType = "error";
    }
}

// ── DIRECT TOKEN PASTE (most reliable method for self-use apps) ──
if (isset($_POST['direct_token']) && !empty(trim($_POST['direct_token']))) {
    $newToken = trim($_POST['direct_token']);
    
    $filesToUpdate = [
        'ajax_get_ali_product.php', 'ajax_search_ali.php', 'ajax_get_ali_feed.php',
        'ajax_image_search.php', 'ajax_ali_shipping.php', 'ali_debug.php'
    ];
    $updated = 0;
    foreach ($filesToUpdate as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            $content = file_get_contents($path);
            $content = preg_replace('/\$token\s*=\s*"[^"]*";/', '$token     = "' . $newToken . '";', $content);
            file_put_contents($path, $content);
            $updated++;
        }
    }
    $message = "✅ SUCCESS! Token manually updated in $updated files. Your AliExpress integration is now live!";
    $msgType = "success";
}

// ── 2. Build the Dropshipping Authorization URL ──
// This uses api-sg.aliexpress.com instead of oauth.aliexpress.com
$authParams = http_build_query([
    'response_type' => 'code',
    'force_auth' => 'true',
    'redirect_uri' => $callbackUrl,
    'client_id' => $appKey
]);
$dropshippingAuthUrl = "https://api-sg.aliexpress.com/oauth/authorize?" . $authParams;

?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>AliExpress Token Generator (Dropshipping)</title>
<style>
body { font-family: sans-serif; background: #0f172a; color: #fff; padding: 40px; text-align: center; }
.box { background: #1e293b; padding: 40px; border-radius: 12px; max-width: 600px; margin: 0 auto; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
.btn { display: block; background: #f97316; color: white; padding: 15px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; margin-bottom: 15px; transition: 0.2s; }
.btn:hover { background: #ea580c; }
.msg { padding: 20px; border-radius: 8px; margin-bottom: 25px; text-align: left; word-break: break-all; }
.success { background: #064e3b; color: #34d399; border: 1px solid #059669; }
.error { background: #7f1d1d; color: #fca5a5; border: 1px solid #dc2626; }
</style></head>
<body>

<div class="box">
    <h1 style="margin-top:0; color:#f97316;">📦 Dropshipping Token Generator</h1>
    <p style="color:#94a3b8; margin-bottom: 30px;">This uses the new API-SG system specifically for Dropshipping apps.</p>
    
    <?php if ($message): ?>
        <div class="msg <?= $msgType ?>"><?= $message ?></div>
        <?php if ($msgType === 'success'): ?>
            <a href="shop.php" class="btn" style="background:#10b981;">Go to Shop / Test API</a>
        <?php endif; ?>
    <?php else: ?>
        <div style="background: rgba(245,158,11,0.1); border: 1px solid #d97706; padding: 15px; border-radius: 8px; color: #fbbf24; font-size: 13px; text-align:left; margin-bottom: 20px;">
            ⚠️ <b>Important:</b> Ensure your AliExpress console Callback URL is set exactly to: <br><code style="color:#fff;"><?= $callbackUrl ?></code>
        </div>

        <a href="<?= $dropshippingAuthUrl ?>" class="btn">Login & Generate Token</a>
    <?php endif; ?>

    <hr style="border-color:#334155; margin: 30px 0;">

    <h2 style="color:#94a3b8; font-size:16px; margin-bottom:15px;">✏️ Or Paste Your Access Token Directly</h2>
    <p style="color:#64748b; font-size:13px; text-align:left; margin-bottom:15px;">
        Get the token from your AliExpress console → <b>Token Management</b> → <b>Access Token</b>. 
        It starts with <code style="color:#fbbf24;">5000...</code>
    </p>
    <form method="POST" action="">
        <textarea name="direct_token" rows="3" placeholder="Paste your Access Token here (e.g. 50000100827...)" style="width:100%; padding:12px; border-radius:8px; border:1px solid #475569; background:#0f172a; color:#fff; font-size:14px; resize:vertical; box-sizing:border-box; margin-bottom:12px;"></textarea>
        <button type="submit" class="btn" style="border:none; cursor:pointer; width:100%;">💾 Save Token to All Files</button>
    </form>
</div>

</body></html>
