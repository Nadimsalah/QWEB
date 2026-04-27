<?php
// ══ AliExpress Token Refresh — DELETE AFTER USE ══
define('FROM_UI', true);

$appKey    = "532966";
$appSecret = "OuzUIdMqmJ9qsnkid6w9RWLB7eNmwDjB";
$callbackUrl = "https://qoon.app/ali_oauth.php"; // Must match AliExpress app settings

// ── Step 2: Handle callback with auth code ──────────────────────────────────
if (isset($_GET['code'])) {
    $code = $_GET['code'];

    $ch = curl_init("https://oauth.aliexpress.com/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'code'          => $code,
        'grant_type'    => 'authorization_code',
        'client_id'     => $appKey,
        'client_secret' => $appSecret,
        'redirect_uri'  => $callbackUrl,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    $data = json_decode($res, true);
    $token = $data['access_token'] ?? null;
    $refresh = $data['refresh_token'] ?? null;
    $expires = $data['expire_time'] ?? null;

    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>body{font-family:monospace;background:#0a0a0a;color:#0f0;padding:30px;font-size:14px;}
    .box{background:#111;border:1px solid #333;border-radius:12px;padding:20px;margin:16px 0;}
    .token{background:#000;border:1px solid #0f0;border-radius:8px;padding:16px;word-break:break-all;color:#0f0;font-size:13px;}
    h2{color:#fff;} .err{color:#f87171;} .ok{color:#4ade80;}
    .copy-btn{background:#6366f1;color:#fff;border:none;padding:8px 18px;border-radius:8px;cursor:pointer;font-size:13px;margin-top:10px;}
    </style></head><body>';

    echo '<h2>🔑 AliExpress Token Exchange</h2>';

    if ($err) {
        echo "<div class='box'><span class='err'>cURL Error: $err</span></div>";
    } elseif ($token) {
        $expiresDate = $expires ? date('Y-m-d H:i:s', $expires/1000) : 'unknown';
        echo "<div class='box'>";
        echo "<span class='ok'>✅ SUCCESS! New token obtained.</span><br><br>";
        echo "<b>Access Token:</b><br><div class='token' id='tok'>$token</div>";
        echo "<button class='copy-btn' onclick='navigator.clipboard.writeText(document.getElementById(\"tok\").innerText)'>📋 Copy Token</button><br><br>";
        if ($refresh) echo "<b>Refresh Token:</b><br><div class='token'>$refresh</div><br>";
        echo "<b>Expires:</b> $expiresDate<br>";
        echo "</div>";

        // Auto-save to a temp file for reference
        $saveData = json_encode([
            'access_token'  => $token,
            'refresh_token' => $refresh,
            'expires'       => $expiresDate,
            'generated_at'  => date('Y-m-d H:i:s'),
        ], JSON_PRETTY_PRINT);
        file_put_contents(__DIR__ . '/ali_token_temp.json', $saveData);

        echo "<div class='box'>";
        echo "<b>📋 Next step — update these files with the new token:</b><br><br>";
        echo "1. <b>ajax_get_ali_product.php</b> line 32<br>";
        echo "2. <b>ajax_get_ali_feed.php</b> line 11<br>";
        echo "3. <b>ajax_ali_shipping.php</b><br><br>";
        echo "Replace the old token string with:<br>";
        echo "<div class='token'>$token</div>";
        echo "</div>";
        echo "<div class='box' style='color:#888;font-size:12px;'>⚠️ Delete ali_oauth.php and ali_token_temp.json from your server after updating the token!</div>";
    } else {
        echo "<div class='box'><span class='err'>❌ Token exchange failed.</span><br><pre>" . htmlspecialchars($res) . "</pre></div>";
    }

    echo '<a href="/ali_oauth.php" style="color:#6366f1;">← Try Again</a>';
    echo '</body></html>';
    exit;
}

// ── Step 1: If refresh_token exists in temp file, try refresh flow ──────────
$refreshToken = null;
$tempFile = __DIR__ . '/ali_token_temp.json';
if (file_exists($tempFile)) {
    $saved = json_decode(file_get_contents($tempFile), true);
    $refreshToken = $saved['refresh_token'] ?? null;
}

if (isset($_GET['do_refresh']) && $refreshToken) {
    $ch = curl_init("https://oauth.aliexpress.com/token");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type'    => 'refresh_token',
        'client_id'     => $appKey,
        'client_secret' => $appSecret,
        'refresh_token' => $refreshToken,
    ]));
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    if (!empty($data['access_token'])) {
        header("Location: /ali_oauth.php?code=REFRESH_BYPASS&_token=" . urlencode($data['access_token']));
        exit;
    }
}

// Handle bypass for refresh flow
if (isset($_GET['_token'])) {
    $token = $_GET['_token'];
    echo "New token via refresh: <b>$token</b>";
    exit;
}

// ── Step 1: Show auth link ──────────────────────────────────────────────────
$authUrl = "https://oauth.aliexpress.com/authorize?" . http_build_query([
    'response_type' => 'code',
    'client_id'     => $appKey,
    'redirect_uri'  => $callbackUrl,
    'state'         => 'qoon_ali_refresh_' . time(),
    'sp'            => 'ae',
    'view'          => 'web',
]);

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
body{font-family:Inter,sans-serif;background:#050510;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
.card{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);border-radius:24px;padding:40px;max-width:480px;width:90%;text-align:center;}
h2{font-size:22px;font-weight:700;margin-bottom:8px;}
p{color:rgba(255,255,255,0.55);font-size:14px;line-height:1.6;margin-bottom:28px;}
.auth-btn{display:inline-block;background:linear-gradient(135deg,#a855f7,#6366f1);color:#fff;padding:14px 32px;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px;box-shadow:0 8px 24px rgba(168,85,247,0.4);transition:all 0.2s;}
.auth-btn:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(168,85,247,0.55);}
.warning{background:rgba(251,191,36,0.1);border:1px solid rgba(251,191,36,0.3);border-radius:12px;padding:14px;font-size:13px;color:rgba(251,191,36,0.9);margin-top:20px;text-align:left;}
</style></head><body>
<div class="card">
    <div style="font-size:40px;margin-bottom:16px;">🔑</div>
    <h2>AliExpress Token Refresh</h2>
    <p>Your access token has expired. Click below to authorize QOON with AliExpress and get a fresh token.<br><br>
    Make sure <code style="color:#a855f7;">https://qoon.app/ali_oauth.php</code> is set as an authorized redirect URI in your AliExpress app settings.</p>
    <a href="' . $authUrl . '" class="auth-btn">🚀 Authorize & Get Token</a>';

if ($refreshToken) {
    echo '<br><br><a href="/ali_oauth.php?do_refresh=1" style="color:#6366f1;font-size:13px;">↩ Try silent refresh with saved refresh_token</a>';
}

echo '    <div class="warning">
        ⚠️ <b>Important:</b> After getting the token, update it in:<br>
        • ajax_get_ali_product.php<br>
        • ajax_get_ali_feed.php<br>
        • ajax_ali_shipping.php<br>
        Then delete this file.
    </div>
</div>
</body></html>';
