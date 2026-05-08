<?php
require_once 'init.php';
$shopName = $_SESSION['SellerName'];

// Retrieve Shop Tokens from DB
$shopID = $SHOP_DATA['ShopID'] ?? 0;

if (isset($_GET['action']) && $_GET['action'] == 'disconnect') {
    $con->query("UPDATE Shops SET FB_AccessToken='', FB_PageID='', IG_AccessToken='', IG_AccountID='' WHERE ShopID=" . intval($shopID));
    header("Location: social.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'manual_auth') {
    $m_tok = $con->real_escape_string($_POST['m_tok'] ?? '');
    $m_pid = $con->real_escape_string($_POST['m_pid'] ?? '');
    if (!empty($m_tok) && !empty($m_pid)) {
        $con->query("UPDATE Shops SET FB_AccessToken='$m_tok', FB_PageID='$m_pid' WHERE ShopID=" . intval($shopID));
    }
    header("Location: social.php");
    exit;
}

$fbToken = $SHOP_DATA['FB_AccessToken'] ?? '';
$igToken = $SHOP_DATA['IG_AccessToken'] ?? '';
$fbPageId = $SHOP_DATA['FB_PageID'] ?? '';

// Build Authentic OAuth URL
$META_APP_ID = "1632632334612163";
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$REDIRECT_URI = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/sellers/social_auth.php";
$oauthUrl = "https://www.facebook.com/v18.0/dialog/oauth?client_id=" . $META_APP_ID . "&redirect_uri=" . urlencode($REDIRECT_URI) . "&scope=pages_manage_posts,pages_read_engagement,instagram_basic,instagram_content_publish";

// Native Sync Fetching
$syncedPosts = [];
if (!empty($fbToken) && !empty($fbPageId)) {
    // Ping Live Facebook Graph API
    $feedUrl = "https://graph.facebook.com/v18.0/" . $fbPageId . "/posts?fields=message,created_time,full_picture,permalink_url&limit=3&access_token=" . $fbToken;
    $ch = curl_init($feedUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    $feedData = json_decode($res, true);
    if(isset($feedData['data'])) {
        $syncedPosts = $feedData['data'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Sync Engine | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-master: #F3F5FA;
            --bg-surface: #FFFFFF;
            --text-strong: #111827;
            --text-base: #374151;
            --text-muted: #9CA3AF;
            --brand-purple: #6B4EE6;
            --brand-purple-light: #EBE8FA;
            --brand-purple-grad: linear-gradient(135deg, #7C5CFF 0%, #5235E8 100%);
            --radius-md: 20px;
            --radius-sm: 12px;
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.02);
            --fb-color: #1877F2;
            --ig-grad: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-master); color: var(--text-base); display: flex; height: 100vh; overflow: hidden; font-family: 'Poppins', sans-serif; }
        .app-envelope { width: 100%; height: 100%; display: flex; background: var(--bg-surface); overflow: hidden; }
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background: #FAFAFB; }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 30px; }
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 10px; }
        .s-title { font-size: 26px; font-weight: 800; color: var(--text-strong); letter-spacing: -0.5px; }
        .s-subtitle { font-size: 14px; color: var(--text-muted); margin-top: 4px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card-node { background: #FFF; border-radius: var(--radius-md); padding: 30px; border: 1px solid #F1F3F5; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; align-items: center; text-align: center; position: relative; overflow: hidden; }
        .card-node.on { border-color: #10B981; background: #F8FAFC; }
        .node-icon { width: 70px; height: 70px; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-size: 34px; color: #FFF; margin-bottom: 20px; }
        .node-icon.fb { background: var(--fb-color); box-shadow: 0 10px 20px rgba(24, 119, 242, 0.2); }
        .node-icon.ig { background: var(--ig-grad); box-shadow: 0 10px 20px rgba(220, 39, 67, 0.2); }
        .node-title { font-weight: 700; font-size: 18px; color: var(--text-strong); margin-bottom: 8px; }
        .btn-connect { padding: 12px 28px; border-radius: 12px; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; border: none; font-family: 'Poppins', sans-serif; background: #E2E8F0; color: #000; }
        .btn-connect.primary { background: var(--brand-purple); color: #FFF; }
        .connected-badge { position: absolute; top: 20px; right: 20px; background: #ECFDF5; color: #059669; font-size: 11px; font-weight: 700; padding: 6px 12px; border-radius: 20px; }

        .publisher-card { background: #FFF; border-radius: var(--radius-md); border: 1px solid #F1F3F5; box-shadow: var(--shadow-soft); display: flex; flex-direction: column; overflow: hidden; margin-top: 10px; }
        .pub-header { padding: 20px 24px; border-bottom: 1px solid #F1F3F5; font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 10px; }
        .pub-textarea { width: 100%; border: none; min-height: 120px; padding: 24px; font-size: 16px; resize: none; font-family: 'Inter', sans-serif; outline: none; }
        .pub-footer { padding: 20px 24px; background: #F8FAFC; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #E5E7EB; }
        .btn-publish { background: var(--text-strong); color: #FFF; padding: 12px 24px; border: none; border-radius: 10px; font-weight: 600; font-family: 'Poppins', sans-serif; cursor: pointer; font-size: 14px; }
        .btn-publish:hover { background: var(--brand-purple); }

        .sync-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .sync-item { background: #FFF; border-radius: 16px; overflow: hidden; border: 1px solid #F1F3F5; }
        .sync-img { width: 100%; aspect-ratio: 1; object-fit: cover; background: #F3F5FA; display:block; }
        .sync-meta { padding: 14px; display: flex; flex-direction: column; gap: 6px; }
    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <div class="content-wrapper">
                
                <?php if(isset($_GET['success'])): ?>
                <div style="background:#ECFDF5; color:#059669; padding: 15px; border-radius: 12px; font-weight:600;"><i class="fas fa-check-circle"></i> Meta Authentication Successful! Accounts Linked.</div>
                <?php endif; ?>
                
                <?php if(isset($_GET['published'])): ?>
                <div style="background:#ECFDF5; color:#059669; padding: 15px; border-radius: 12px; font-weight:600;"><i class="fas fa-check-circle"></i> Content officially published to Live Facebook API!</div>
                <?php endif; ?>

                <div class="section-header">
                    <div>
                        <h1 class="s-title">Authentic Meta Gateway</h1>
                        <p class="s-subtitle">Live Graph API connection initialized.</p>
                        <?php if(empty($fbToken)): ?>
                        <div style="background:#FFF3CD; color:#856404; padding:12px; border-radius:8px; margin-top:20px; font-size:13px; font-weight:600; border:1px solid #FFEEBA;">
                            <i class="fas fa-exclamation-triangle"></i> FACEBOOK DEVELOPER CONSOLE MATCHING REQUIRED:<br>
                            Inside developers.facebook.com, you must ensure "App Domains" is EMPTY, and paste the exact string below into <b>Valid OAuth Redirect URIs</b>:<br>
                            <code style="color:#D9534F; font-size:14px; display:inline-block; margin-top:8px; background:#FFF; padding:4px 8px; border-radius:4px;"><?= $REDIRECT_URI ?></code>
                        </div>
                        
                        <div style="margin-top:20px; background:#F8FAFC; border:1px solid #E2E8F0; padding:20px; border-radius:12px;">
                            <h4 style="margin-bottom:10px; font-size:14px;"><i class="fas fa-hammer"></i> Localhost Debug Bypass</h4>
                            <p style="font-size:12px; color:var(--text-muted); margin-bottom:15px;">If Facebook continues to block HTTP localhost redirects, you can generate a <b>Page Access Token</b> using the Meta Graph API Explorer and paste it here.</p>
                            <form action="social.php?action=manual_auth" method="POST" style="display:flex; gap:10px; flex-wrap:wrap;">
                                <input type="text" name="m_pid" placeholder="Facebook Page ID" required style="flex:1; padding:10px; border-radius:8px; border:1px solid #E5E7EB; min-width:200px;">
                                <input type="text" name="m_tok" placeholder="Page Access Token (Starts with EAA...)" required style="flex:2; padding:10px; border-radius:8px; border:1px solid #E5E7EB; min-width:300px;">
                                <button type="submit" class="btn-publish" style="padding:10px 20px;"><i class="fas fa-plug"></i> Force Connection</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card-node <?= !empty($fbToken) ? 'on' : '' ?>">
                        <?php if(!empty($fbToken)): ?><div class="connected-badge"><i class="fas fa-check-circle"></i> Connected API</div><?php endif; ?>
                        <div class="node-icon fb"><i class="fab fa-facebook-f"></i></div>
                        <h3 class="node-title">Facebook Page</h3>
                        <p class="node-desc" style="color:var(--text-muted); font-size:13px;">Securely route posts to Facebook Database via Graph v18.0</p>
                        <?php if(empty($fbToken)): ?>
                            <a href="<?= $oauthUrl ?>" class="btn-connect primary">Authorize Live API</a>
                        <?php else: ?>
                            <div style="display:flex; gap:10px;">
                                <button class="btn-connect" disabled style="background:#E2E8F0; color:#10B981;"><i class="fas fa-lock"></i> Connection Secured</button>
                                <a href="social.php?action=disconnect" class="btn-connect" style="background:#fee2e2; color:#ef4444;"><i class="fas fa-unlink"></i> Reset Auth</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Publisher Form -->
                <div style="margin-top:20px;">
                    <h2 class="s-title" style="font-size:20px;">Graph Publisher POST</h2>
                    <form action="social_auth.php?action=publish" method="POST" class="publisher-card">
                        <div class="pub-header"><i class="fas fa-satellite-dish"></i> Emit Live Payload</div>
                        <textarea name="caption" class="pub-textarea" placeholder="Enter raw string message. This will natively transmit to Facebooks APIs." required></textarea>
                        <div class="pub-footer">
                            <label><input type="checkbox" name="post_fb" checked> FB Page Webhook</label>
                            <button type="submit" class="btn-publish" <?= empty($fbToken) ? 'disabled style="opacity:0.5;" title="Please authorize first"' : '' ?>>
                                <i class="fas fa-code-branch"></i> Execute API Push
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Feed Grid -->
                <div style="margin-top:20px;">
                    <div class="section-header">
                        <h2 class="s-title" style="font-size:20px;">Live Sync Query Results</h2>
                    </div>
                    <?php if(empty($fbToken)): ?>
                        <div style="color:var(--text-muted); font-size:14px; padding: 20px; text-align:center; border:2px dashed #E5E7EB; border-radius:12px;">Waiting for API Authorization code exchange...</div>
                    <?php else: ?>
                        <div class="sync-grid">
                            <?php foreach($syncedPosts as $post): ?>
                            <div class="sync-item">
                                <?php if(isset($post['full_picture'])): ?>
                                    <img src="<?= $post['full_picture'] ?>" class="sync-img">
                                <?php endif; ?>
                                <div class="sync-meta">
                                    <div style="font-size:12px; color:var(--text-strong);"><?= htmlspecialchars(substr($post['message'] ?? 'No text', 0, 80)) ?>...</div>
                                    <a href="<?= $post['permalink_url'] ?>" target="_blank" style="font-size:11px; color:var(--fb-color);">View Live Network Data <i class="fas fa-external-link-alt"></i></a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
