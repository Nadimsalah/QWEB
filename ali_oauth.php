<?php
// ══ AliExpress Token Helper — shows all possible auth URLs ══
$appKey     = "532966";
$appSecret  = "cTzCrj5XNUjx9lXKsrD6Fo1AuUf1Th2J";
$callbackUrl = "https://qoon.app/ali_oauth.php";

// ── Handle callback ──────────────────────────────────────────────────────────
if (isset($_GET['code']) && $_GET['code'] !== 'skip') {
    $code = $_GET['code'];
    // Try all token endpoints
    $endpoints = [
        "https://oauth.aliexpress.com/token",
        "https://oauth.aliexpress.gw.alipayobjects.com/token",
    ];
    $token = null; $rawResult = '';
    foreach ($endpoints as $ep) {
        $ch = curl_init($ep);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code, 'grant_type' => 'authorization_code',
                'client_id' => $appKey, 'client_secret' => $appSecret,
                'redirect_uri' => $callbackUrl,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $res = curl_exec($ch); curl_close($ch);
        $data = json_decode($res, true);
        $rawResult .= "\nEndpoint: $ep\nResponse: $res\n";
        if (!empty($data['access_token'])) { $token = $data['access_token']; break; }
    }
    
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><style>
    body{font-family:monospace;background:#050510;color:#fff;padding:30px;font-size:14px;}
    .box{background:#111;border:1px solid #333;border-radius:12px;padding:20px;margin:16px 0;}
    .tok{background:#000;border:1px solid #0f0;border-radius:8px;padding:16px;word-break:break-all;color:#0f0;}
    h2{color:#fff;}.ok{color:#4ade80;}.err{color:#f87171;}
    .btn{display:inline-block;background:linear-gradient(135deg,#a855f7,#6366f1);color:#fff;padding:12px 24px;border-radius:50px;text-decoration:none;font-weight:700;margin-top:12px;}
    </style></head><body>';
    
    if ($token) {
        echo "<h2 class='ok'>✅ New Token Obtained!</h2>";
        echo "<div class='box'><b>Your new Access Token:</b><br><div class='tok' id='tok'>$token</div>";
        echo "<br><button onclick=\"navigator.clipboard.writeText(document.getElementById('tok').innerText);this.textContent='✅ Copied!'\" style='background:#6366f1;color:#fff;border:none;padding:10px 20px;border-radius:8px;cursor:pointer;font-size:14px;'>📋 Copy Token</button></div>";
        echo "<div class='box' style='color:#fbbf24;'>⚠️ Paste this token to your developer and then delete this file.</div>";
        file_put_contents(__DIR__.'/ali_token_temp.json', json_encode(['access_token'=>$token,'generated_at'=>date('c')], JSON_PRETTY_PRINT));
    } else {
        echo "<h2 class='err'>❌ Token exchange failed</h2>";
        echo "<div class='box'><pre>" . htmlspecialchars($rawResult) . "</pre></div>";
        echo "<a href='/ali_oauth.php' class='btn'>← Try Again</a>";
    }
    echo '</body></html>'; exit;
}

// ── Generate all possible auth URLs ─────────────────────────────────────────
$params = ['response_type'=>'code','client_id'=>$appKey,'redirect_uri'=>$callbackUrl,'state'=>'qoon'.time()];

$authUrls = [
    'AliExpress OAuth (Standard)'    => "https://oauth.aliexpress.com/authorize?" . http_build_query(array_merge($params, ['sp'=>'ae'])),
    'AliExpress OAuth (No SP)'        => "https://oauth.aliexpress.com/authorize?" . http_build_query($params),
    'AliExpress OAuth (web view)'     => "https://oauth.aliexpress.com/authorize?" . http_build_query(array_merge($params, ['view'=>'web'])),
    'Taobao/TOP OAuth'                => "https://oauth.taobao.com/authorize?"     . http_build_query(array_merge($params, ['view'=>'web'])),
    'AliExpress GW OAuth'             => "https://oauth.aliexpress.gw.alipayobjects.com/authorize?" . http_build_query($params),
];

echo '<!DOCTYPE html><html><head><meta charset="utf-8">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:Inter,sans-serif;background:#050510;color:#fff;padding:30px;font-size:14px;line-height:1.6;}
h1{font-size:22px;font-weight:700;margin-bottom:6px;}
.sub{color:rgba(255,255,255,0.45);font-size:13px;margin-bottom:24px;}
.card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:18px 20px;margin-bottom:14px;}
.card h3{font-size:14px;font-weight:600;margin-bottom:10px;color:#a855f7;}
.btn{display:inline-block;background:linear-gradient(135deg,#a855f7,#6366f1);color:#fff;padding:10px 22px;border-radius:50px;text-decoration:none;font-weight:600;font-size:13px;transition:0.2s;}
.btn:hover{opacity:.85;transform:translateY(-1px);}
.warn{background:rgba(251,191,36,0.08);border:1px solid rgba(251,191,36,0.25);border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#fbbf24;}
.step{background:rgba(99,102,241,0.08);border:1px solid rgba(99,102,241,0.2);border-radius:12px;padding:18px;margin-bottom:20px;}
.step h2{font-size:15px;font-weight:700;margin-bottom:10px;color:#818cf8;}
.step ol{padding-left:20px;} .step li{margin-bottom:8px;color:rgba(255,255,255,0.75);}
.step code{background:rgba(255,255,255,0.07);padding:2px 7px;border-radius:5px;font-family:monospace;color:#a855f7;}
hr{border:none;border-top:1px solid rgba(255,255,255,0.07);margin:20px 0;}
</style></head><body>

<h1>🔑 AliExpress Token Refresh</h1>
<p class="sub">App Key: <b>532966</b> — Try each button below until one works</p>

<div class="warn">
⚠️ <b>Before clicking any button</b>, make sure <code>https://qoon.app/ali_oauth.php</code> is added as a whitelisted redirect URI in your AliExpress app settings. Otherwise you will always get "appkey not exists".
</div>

<div class="step">
<h2>📋 Step 1 — Add the redirect URI to your app</h2>
<ol>
  <li>Go to <a href="https://open.aliexpress.com" target="_blank" style="color:#818cf8;">open.aliexpress.com</a> → My Applications</li>
  <li>Find app <b>532966</b> → click <b>Edit / Settings</b></li>
  <li>Find <b>"OAuth Redirect URL"</b> field</li>
  <li>Add: <code>https://qoon.app/ali_oauth.php</code></li>
  <li>Save, then come back here</li>
</ol>
</div>

<div class="step">
<h2>🚀 Step 2 — Try each authorization URL</h2>
<p style="color:rgba(255,255,255,0.5);font-size:12px;margin-bottom:14px;">Try them one by one. If you see AliExpress login/permission page = that one works!</p>';

foreach ($authUrls as $label => $url) {
    echo "<div class='card'><h3>$label</h3><a href='" . htmlspecialchars($url) . "' class='btn' target='_blank'>🔗 Try: $label</a></div>\n";
}

echo '</div>

<hr>

<div class="step">
<h2>✨ Alternative: Get Token Directly from Console (Easiest)</h2>
<ol>
  <li>Go to <a href="https://open.aliexpress.com" target="_blank" style="color:#818cf8;">open.aliexpress.com</a></li>
  <li>Login → click your app</li>
  <li>Find <b>"Token Management"</b> or <b>"Self Authorization"</b> tab</li>
  <li>Click <b>Generate Token</b></li>
  <li>Copy the token shown and send it to your developer</li>
</ol>
<p style="margin-top:12px;color:rgba(255,255,255,0.45);font-size:12px;">This skips OAuth entirely — no redirect URI needed.</p>
</div>

</body></html>';
