<?php
require_once 'init.php';

$shopName = $_SESSION['SellerName'];
$shopLogo = $SHOP_DATA['ShopPhoto'] ?? '';

if (isset($_GET['action']) && $_GET['action'] == 'check_username' && isset($_GET['u'])) {
    $u = preg_replace("/[^a-zA-Z0-9_]/", "", $_GET['u']);
    if (strlen($u) < 3) {
        echo json_encode(['status' => 'error', 'msg' => 'Too short']);
        exit;
    }
    $check = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$u' AND ShopID != " . $SHOP_DATA['ShopID']);
    if ($check && $check->num_rows > 0) {
        echo json_encode(['status' => 'taken']);
    } else {
        echo json_encode(['status' => 'available']);
    }
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    $new_u = preg_replace("/[^a-zA-Z0-9_]/", "", $_POST['new_username']);
    if (!empty($new_u)) {
        $check = $con->query("SELECT ShopID FROM Shops WHERE ShopLogName = '$new_u' AND ShopID != " . $SHOP_DATA['ShopID']);
        if ($check && $check->num_rows == 0) {
            $con->query("UPDATE Shops SET ShopLogName = '$new_u' WHERE ShopID = " . $SHOP_DATA['ShopID']);
            $SHOP_DATA['ShopLogName'] = $new_u;
            $msg = "<div style='color:green; font-size:12px; margin-top:5px;'>Username successfully updated!</div>";
        } else {
            $msg = "<div style='color:red; font-size:12px; margin-top:5px;'>That username is already taken.</div>";
        }
    }
}

$domainMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_domain'])) {
    $d = strtolower(trim($_POST['custom_domain']));
    $d = preg_replace('#^https?://#', '', $d); // Strip HTTP prefix
    if (!empty($d) && $con) {
        try {
            @$con->query("ALTER TABLE Shops ADD COLUMN ShopDomain VARCHAR(255) DEFAULT ''");
        } catch (Exception $e) {
        }
        $con->query("UPDATE Shops SET ShopDomain = '" . $con->real_escape_string($d) . "' WHERE ShopID = " . $SHOP_DATA['ShopID']);
        $SHOP_DATA['ShopDomain'] = $d;
        $domainMsg = "<div style='color:green; font-weight:600; font-size:12px; margin-bottom:10px;'><i class='fas fa-check-circle'></i> Domain connected! DNS propagation may take up to 24 hours.</div>";
    }
}

$shopSlug = $SHOP_DATA['ShopLogName'] ?? strtolower(str_replace(' ', '', $shopName));
$shopDomain = $SHOP_DATA['ShopDomain'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Store | QOON Partner</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
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
            --accent-green: #059669;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-master);
            color: var(--text-base);
            display: flex;
            height: 100vh;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .app-envelope {
            width: 100%;
            height: 100%;
            display: flex;
            background: var(--bg-surface);
            overflow: hidden;
        }

        .main-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            background: #FAFAFB;
        }

        .content-wrapper {
            padding: 40px;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 10px;
        }

        .s-title {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-strong);
            letter-spacing: -0.5px;
        }

        .s-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .hero-card {
            background: var(--brand-purple-grad);
            border-radius: 24px;
            padding: 40px;
            color: #FFF;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(107, 78, 230, 0.2);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 15px;
            display: inline-block;
        }

        .hero-title {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .hero-sub {
            opacity: 0.9;
            font-size: 15px;
            font-weight: 500;
        }

        .btn-preview {
            background: #FFF;
            color: var(--brand-purple);
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .btn-preview:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            max-width: 800px;
        }

        .t-card {
            background: #FFF;
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
            gap: 20px;
            border: 1px solid #F1F5F9;
        }

        .t-card-head {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            font-size: 18px;
            color: var(--text-strong);
        }

        /* Theme Selector */
        .theme-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
        }

        .theme-item {
            border: 2px solid #F1F5F9;
            border-radius: 16px;
            padding: 15px;
            cursor: pointer;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .theme-item:hover {
            border-color: var(--brand-purple-light);
            background: #FAFAFB;
        }

        .theme-item.active {
            border-color: var(--brand-purple);
            background: var(--brand-purple-light);
        }

        .theme-item.active::after {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            color: var(--brand-purple);
        }

        .theme-thumb {
            width: 100%;
            aspect-ratio: 4/3;
            background: #F3F4F6;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .theme-thumb i {
            font-size: 40px;
            color: #D1D5DB;
        }

        .theme-info {
            text-align: center;
        }

        .theme-name {
            font-weight: 700;
            font-size: 14px;
            display: block;
            margin-bottom: 2px;
        }

        .theme-desc {
            font-size: 11px;
            color: var(--text-muted);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--accent-green);
            display: inline-block;
            margin-right: 8px;
        }

        /* Shimmer Loading Skeleton */
        @keyframes shimmerEffect {
            0% { background-position: -400px 0; }
            100% { background-position: 400px 0; }
        }
        .shimmer-bg {
            background: #F3F4F6;
            background-image: linear-gradient(to right, #F3F4F6 0%, #E5E7EB 20%, #F3F4F6 40%, #F3F4F6 100%);
            background-repeat: no-repeat;
            background-size: 800px 100%; 
            animation: shimmerEffect 1.5s infinite linear;
        }

        .fade-in-up { animation: fadeInUp 0.4s ease forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>

<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <div class="content-wrapper">

                <div class="section-header">
                    <div>
                        <div class="s-title">Online Store Hub</div>
                        <div class="s-subtitle">Manage your external digital storefront and themes.</div>
                    </div>
                </div>

                <!-- SPA SHIMMER SKELETON -->
                <div id="skeleton-store-setup">
                    <div class="hero-card" style="margin-bottom:30px; background:#FFF; border:1px solid #F1F5F9; box-shadow:0 4px 6px -1px rgba(0,0,0,0.02); height: 260px;">
                        <div class="hero-content" style="width: 100%;">
                            <div class="shimmer-bg" style="height:20px; width:220px; border-radius:20px; margin-bottom:15px;"></div>
                            <div class="shimmer-bg" style="height:40px; width:350px; border-radius:8px; margin-bottom:10px;"></div>
                            <div class="shimmer-bg" style="height:15px; width:450px; border-radius:4px; margin-bottom:25px;"></div>
                            <div style="display:flex; gap:15px;">
                                <div class="shimmer-bg" style="height:48px; width:160px; border-radius:12px;"></div>
                                <div class="shimmer-bg" style="height:48px; width:130px; border-radius:12px;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid-layout">
                        <div class="t-card" style="border:none;">
                            <div class="t-card-head"><div class="shimmer-bg" style="height:24px; width:150px; border-radius:4px;"></div></div>
                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <?php for($i=0; $i<3; $i++): ?>
                                <div style="padding: 15px; background: #FFF; border-radius: 16px; border: 1px solid #F1F5F9;">
                                    <div class="shimmer-bg" style="height:11px; width:120px; border-radius:4px; margin-bottom:15px;"></div>
                                    <div class="shimmer-bg" style="height:20px; width:250px; border-radius:4px; margin-bottom:15px;"></div>
                                    <div class="shimmer-bg" style="height:42px; width:100%; border-radius:10px;"></div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REAL CONTENT HIDDEN INITIALLY -->
                <div id="real-store-setup" style="display:none;">
                    <!-- Hero: Pulse of the store -->
                    <div class="hero-card fade-in-up" style="animation-delay: 0s;">
                        <div class="hero-content">
                            <div class="hero-badge"><i class="fas fa-check-circle"></i> LIVE ON QOON ECOSYSTEM</div>
                            <h1 class="hero-title">Your store is worldwide.</h1>
                            <p class="hero-sub">Your digital identity is active and synchronized across all QOON nodes.</p>
                            <div style="margin-top: 25px; display: flex; gap: 15px;">
                                <a href="store.php?u=<?= urlencode($shopSlug) ?>" target="_blank" class="btn-preview">
                                    <i class="fas fa-external-link-alt"></i> See Store Online
                                </a>
                                <button onclick="showQRCode()" class="btn-preview"
                                    style="background: rgba(255,255,255,0.15); color: #FFF; box-shadow: none; border: 1px solid rgba(255,255,255,0.2);">
                                    <i class="fas fa-qrcode"></i> Store QR
                                </button>
                            </div>
                        </div>
                        <div
                            style="opacity: 0.15; font-size: 180px; transform: rotate(15deg); position: absolute; right: -20px; top: -20px;">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>

                    <div class="grid-layout fade-in-up" style="animation-delay: 0.15s; margin-top: 30px;">
                        <!-- Column: Quick Stats / Meta -->
                        <div class="t-card">
                            <div class="t-card-head"><i class="fas fa-info-circle" style="color:var(--text-muted);"></i>
                                Store Meta</div>

                            <div style="display: flex; flex-direction: column; gap: 15px;">
                                <div
                                    style="padding: 15px; background: #F9FAFB; border-radius: 16px; border: 1px solid #F1F5F9;">
                                    <div
                                        style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                                        Public URL & Username</div>
                                    <div
                                        style="font-size: 14px; font-weight: 600; color: var(--brand-purple); margin-bottom: 15px;">
                                        qoon.app/store.php?u=<?= htmlspecialchars($shopSlug) ?></div>

                                    <form method="POST" style="display: flex; gap: 8px;">
                                        <span
                                            style="display: flex; align-items: center; background: #E5E7EB; padding: 0 10px; border-radius: 10px; font-size: 13px; font-weight: 600;">@</span>
                                        <div style="position:relative; flex:1; display:flex; align-items:center;">
                                            <input type="text" id="usernameInput" name="new_username"
                                                value="<?= htmlspecialchars($shopSlug) ?>"
                                                style="width: 100%; padding: 10px 32px 10px 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 13px; outline: none; transition: border-color 0.2s;">
                                            <div id="usernameStatus"
                                                style="position:absolute; right:12px; font-size:15px; pointer-events: none;">
                                                <i class="fas fa-check-circle" style="color:#10B981;"></i></div>
                                        </div>
                                        <button type="submit"
                                            style="background: var(--brand-purple); color: #FFF; border: none; padding: 0 16px; border-radius: 10px; font-weight: 600; font-size: 12px; cursor: pointer; transition: transform 0.2s;">Save</button>
                                    </form>
                                    <?= $msg ?>
                                </div>

                                <div
                                    style="padding: 15px; background: #F9FAFB; border-radius: 16px; border: 1px solid #F1F5F9;">
                                    <div
                                        style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">
                                        Discovery Status</div>
                                    <div style="display: flex; align-items: center; font-size: 14px; font-weight: 700;">
                                        <span class="status-dot"></span> Visible to Public
                                    </div>
                                </div>

                                <div
                                    style="padding: 15px; background: #F9FAFB; border-radius: 16px; border: 1px solid #F1F5F9;">
                                    <div
                                        style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 10px;">
                                        Connect Custom Domain</div>

                                    <form method="POST" style="display: flex; gap: 8px; margin-bottom: 12px;">
                                        <input type="text" name="custom_domain" value="<?= htmlspecialchars($shopDomain) ?>"
                                            placeholder="e.g. www.mystore.com"
                                            style="flex: 1; padding: 10px 14px; border: 1px solid #E5E7EB; border-radius: 10px; font-family: 'Poppins', sans-serif; font-size: 13px; outline: none;">
                                        <button type="submit"
                                            style="background: #111; color: #FFF; border: none; padding: 0 16px; border-radius: 10px; font-weight: 600; font-size: 12px; cursor: pointer; transition: transform 0.2s;"><?= !empty($shopDomain) ? 'Update' : 'Connect' ?></button>
                                    </form>
                                    <?= $domainMsg ?>

                                    <div
                                        style="background: rgba(107, 78, 230, 0.05); border: 1px dashed rgba(107, 78, 230, 0.3); padding: 12px; border-radius: 10px;">
                                        <div style="font-size: 12px; color: var(--text-base); margin-bottom: 6px;">To link
                                            your domain, update your DNS nameservers to:</div>
                                        <div
                                            style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--brand-purple);">
                                            ns1.qoon.app</div>
                                        <div
                                            style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--brand-purple);">
                                            ns2.qoon.app</div>
                                    </div>
                                </div>
                            </div>

                            <div
                                style="margin-top: auto; padding: 15px; border-radius: 12px; background: #FFF9F2; border-left: 4px solid #F59E0B; font-size: 12px; color: #92400E; display: flex; gap: 10px;">
                                <i class="fas fa-lightbulb"></i>
                                <span><b>Pro Tip:</b> Use the "Modern Dark" theme to increase conversion rates for luxury
                                    products.</span>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

            </div>
        </main>
    </div>

    <!-- QR CODE MODAL -->
    <?php $storeUrl = "http://" . $_SERVER['HTTP_HOST'] . "/sellers/store.php?u=" . urlencode($shopSlug); ?>
    <div id="qrModal"
        style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center; backdrop-filter: blur(8px);">
        <div
            style="background:#FFF; padding:40px; border-radius:28px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.15); max-width: 320px; animation: popup 0.3s cubic-bezier(0.34,1.4,0.64,1);">
            <h3 style="margin-bottom: 24px; font-weight:800; color:#111; font-size: 20px;">Your Store QR</h3>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($storeUrl) ?>"
                style="border-radius:16px; margin-bottom: 24px; border: 1px solid #f0f0f0; padding: 10px;"
                alt="QR Code">
            <p style="font-size:14px; color:#7a7a8c; margin-bottom: 24px; line-height: 1.5;">Customers can scan this
                code to instantly open your beautifully modernized store on their mobile devices.</p>
            <div style="display:flex; gap:10px;">
                <button type="button" id="btnDownloadQr" onclick="downloadQR()"
                    style="background:var(--brand-purple); color:#fff; padding:12px; border-radius:12px; flex:1; border:none; font-weight:700; font-size: 13px; cursor:pointer;"><i
                        class="fas fa-download"></i> Download</button>
                <button type="button" onclick="document.getElementById('qrModal').style.display='none'"
                    style="background:#f0f0f5; color:#111; padding:12px; border-radius:12px; flex:1; border:none; font-weight:700; font-size: 13px; cursor:pointer; transition: background 0.2s;">Close</button>
            </div>
        </div>
    </div>
    <style>
        @keyframes popup {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>

    <script>
        function selectTheme(el) {
            document.querySelectorAll('.theme-item').forEach(item => item.classList.remove('active'));
            el.classList.add('active');
        }
        function showQRCode() {
            document.getElementById('qrModal').style.display = 'flex';
        }
        function downloadQR() {
            const btn = document.getElementById('btnDownloadQr');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            fetch('https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=<?= urlencode($storeUrl) ?>')
                .then(res => res.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = '<?= htmlspecialchars($shopSlug) ?>_QR.png';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    a.remove();
                    btn.innerHTML = '<i class="fas fa-check"></i> Downloaded';
                    setTimeout(() => btn.innerHTML = originalText, 2500);
                })
                .catch(() => {
                    alert('Failed to download image.');
                    btn.innerHTML = originalText;
                });
        }

        // Real-Time Username Availability Engine
        let typingTimer;
        const $uInput = document.getElementById('usernameInput');
        const $uStatus = document.getElementById('usernameStatus');
        if ($uInput) {
            $uInput.addEventListener('input', function () {
                clearTimeout(typingTimer);
                if (!this.value.trim()) { $uStatus.innerHTML = ''; $uInput.style.borderColor = '#E5E7EB'; return; }
                $uStatus.innerHTML = '<i class="fas fa-spinner fa-spin" style="color:#a0a0b0;"></i>';
                let val = this.value;
                typingTimer = setTimeout(() => {
                    fetch('store_setup.php?action=check_username&u=' + encodeURIComponent(val))
                        .then(r => r.json())
                        .then(d => {
                            if (d.status === 'error') {
                                $uStatus.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;" title="Too short"></i>';
                                $uInput.style.borderColor = '#EF4444';
                            } else if (d.status === 'taken') {
                                $uStatus.innerHTML = '<i class="fas fa-times-circle" style="color:#EF4444;" title="Taken"></i>';
                                $uInput.style.borderColor = '#EF4444';
                            } else {
                                $uStatus.innerHTML = '<i class="fas fa-check-circle" style="color:#10B981;" title="Available"></i>';
                                $uInput.style.borderColor = '#10B981';
                            }
                        }).catch(() => $uStatus.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#F59E0B;"></i>');
                }, 400); // 400ms debounce
            });
        }

        window.onload = function() {
            setTimeout(() => {
                const sk = document.getElementById('skeleton-store-setup');
                const rl = document.getElementById('real-store-setup');
                if(sk && rl) {
                    sk.style.display = 'none';
                    rl.style.display = 'block';
                }
            }, 500);
        }
    </script>
</body>

</html>