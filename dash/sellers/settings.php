<?php
require_once 'init.php';

// Helper: push a locally-saved file to the live server
function pushFileToLive($localPath, $fileName, &$log) {
    if (!function_exists('curl_init')) { $log[] = "cURL not available"; return; }
    $ch = curl_init("https://qoon.app/dash/sellers/receive_upload.php?token=qoon_sync_2024&file=" . urlencode($fileName));
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => ['file' => new CURLFile($localPath, 'image/png', $fileName)],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    $log[] = "Live push: " . ($err ?: $resp);
}

$sellerID = (int)$_SESSION['SellerID'];

// --- HANDLE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $shopName = $con->real_escape_string($_POST['ShopName']);
    $shopPhone = $con->real_escape_string($_POST['ShopPhone']);
    $ownerPhone = $con->real_escape_string($_POST['OwnerPhone']);
    $email = $con->real_escape_string($_POST['Email']);
    $bankName = $con->real_escape_string($_POST['BankName']);
    $bankNum = $con->real_escape_string($_POST['BankNum']);
    $catID = $con->real_escape_string($_POST['CategoryID']);
    $lat = $con->real_escape_string($_POST['ShopLat']);
    $lng = $con->real_escape_string($_POST['ShopLongt']);

    $updateSql = "UPDATE Shops SET 
        ShopName = '$shopName',
        ShopPhone = '$shopPhone',
        OwnerPhone = '$ownerPhone',
        Email = '$email',
        BankName = '$bankName',
        BankNum = '$bankNum',
        CategoryID = '$catID',
        ShopLat = '$lat',
        ShopLongt = '$lng'
        WHERE ShopID = $sellerID";
    
    if ($con->query($updateSql)) {
        $debugLog = [];
        
        // Handle Logo Upload
        if (!empty($_FILES['ShopLogo']['tmp_name'])) {
            $photo1name = "w-" . rand();
            $logoName   = $photo1name . ".png";
            $path       = __DIR__ . "/../photo/" . $logoName;  // local disk
            $actualpath = "https://qoon.app/dash/photo/" . $logoName; // DB URL

            if (move_uploaded_file($_FILES['ShopLogo']['tmp_name'], $path)) {
                $con->query("UPDATE Shops SET ShopLogo = '$actualpath' WHERE ShopID = $sellerID");
                $debugLog[] = "Logo saved locally + DB updated: $actualpath";
                // Push to live server
                pushFileToLive($path, $logoName, $debugLog);
            } else {
                $debugLog[] = "Logo move_uploaded_file FAILED. Error=" . $_FILES['ShopLogo']['error'];
            }
        }

        // Handle Cover Upload
        if (!empty($_FILES['ShopCover']['tmp_name'])) {
            $photo2name = "w-" . rand();
            $coverName  = $photo2name . ".png";
            $path2      = __DIR__ . "/../photo/" . $coverName;
            $actualpath2 = "https://qoon.app/dash/photo/" . $coverName;

            if (move_uploaded_file($_FILES['ShopCover']['tmp_name'], $path2)) {
                $con->query("UPDATE Shops SET ShopCover = '$actualpath2' WHERE ShopID = $sellerID");
                $debugLog[] = "Cover saved locally + DB updated: $actualpath2";
                // Push to live server
                pushFileToLive($path2, $coverName, $debugLog);
            } else {
                $debugLog[] = "Cover move_uploaded_file FAILED. Error=" . $_FILES['ShopCover']['error'];
            }
        }
        
        // Write debug log
        if (!empty($debugLog)) {
            file_put_contents(__DIR__ . '/upload_debug.log', date('Y-m-d H:i:s') . "\n" . implode("\n", $debugLog) . "\n---\n", FILE_APPEND);
        }
        header("Location: settings.php?msg=updated");
        exit;
    }
}

// Fetch Categories
$catRes = $con->query("SELECT * FROM Categories ORDER BY EnglishCategory ASC");
$allCats = [];
if($catRes) { while($r = $catRes->fetch_assoc()) { $allCats[] = $r; } }

// Current Category Data
$curCatId = $SHOP_DATA['CategoryID'];
$curCatPhoto = '';
foreach($allCats as $c) {
    if($c['CategoryId'] == $curCatId) { $curCatPhoto = $c['Photo']; break; }
}

// Fetch Active Boost Packages from dashboard
$pkgRes = $con->query("SELECT * FROM BoostPrices WHERE BoostPricesStatus = 'ACTIVE' ORDER BY DDay ASC");
$packages = [];
if($pkgRes) { while($r = $pkgRes->fetch_assoc()) { $packages[] = $r; } }

// Current shop subscription info (PaySub = subscription expiry or status)
$shopSub = $SHOP_DATA['PaySub'] ?? '';
$shopBalance = (float)($SHOP_DATA['Balance'] ?? 0);

// Plan prices (can be made dynamic from DB later)
$planPrices = [
    'Free Tier'    => 0,
    'Premium Pro'  => 199,
    'Premium Plus' => 349,
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        :root {
            --bg-master: #F3F5FA; --bg-surface: #FFFFFF;
            --text-strong: #111827; --text-base: #374151; --text-muted: #9CA3AF;
            --brand-purple: #6B4EE6; --brand-purple-light: #F3F0FF;
            --brand-purple-grad: linear-gradient(135deg, #7C5CFF 0%, #5235E8 100%);
            --radius-lg: 30px; --radius-md: 20px; --radius-sm: 14px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Poppins', sans-serif; background: #FAFBFF; color: var(--text-base); height: 100vh; overflow: hidden; }
        .app-envelope { display: flex; width: 100%; height: 100%; overflow: hidden; }
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background: #FAFBFF; }
        .content-wrapper { padding: 50px; max-width: 1400px; width: 100%; display: flex; flex-direction: column; gap: 40px; margin: 0 auto; }

        .settings-container { display: grid; grid-template-columns: 280px 1fr; gap: 40px; align-items: start; }
        
        /* SETTINGS NAV */
        .settings-nav { background: #FFF; border-radius: var(--radius-md); padding: 15px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.03); position: sticky; top: 0; }
        .snav-item { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-radius: 15px; color: var(--text-muted); text-decoration: none; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.3s; margin-bottom: 5px; }
        .snav-item i { font-size: 18px; width: 24px; text-align: center; }
        .snav-item:hover { background: #F9FAFB; color: var(--text-strong); }
        .snav-item.active { background: var(--brand-purple-light); color: var(--brand-purple); }

        /* CARDS & FORMS */
        .settings-content { display: flex; flex-direction: column; gap: 30px; }
        .card { 
            background: #FFF; border-radius: var(--radius-lg); padding: 40px; 
            box-shadow: 0 20px 40px -15px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.02);
            opacity: 0; transform: translateY(20px); animation: fadeInUp 0.5s forwards;
        }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        .section-tag { font-size: 10px; font-weight: 800; color: var(--brand-purple); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; display: block; }
        .section-title { font-size: 22px; font-weight: 800; color: var(--text-strong); margin-bottom: 30px; }

        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 10px; }
        .form-group label { font-size: 13px; font-weight: 700; color: var(--text-strong); }
        .form-control { background: #F8FAFC; border: 2px solid #F1F5F9; padding: 16px 20px; border-radius: 16px; font-size: 14px; outline: none; transition: 0.3s; font-family: 'Inter', sans-serif; color: var(--text-strong); }
        .form-control:focus { border-color: var(--brand-purple); background: #FFF; box-shadow: 0 0 0 5px var(--brand-purple-light); }
        
        /* IMAGE UPLOADERS */
        .visual-identity { 
            position: relative; 
            margin-bottom: 80px; 
        }
        .cover-area { width: 100%; height: 260px; border-radius: var(--radius-md); background: #F1F5F9; overflow: hidden; position: relative; cursor: pointer; transition: 0.4s; box-shadow: inset 0 0 40px rgba(0,0,0,0.05); }
        .cover-img { width: 100%; height: 100%; object-fit: cover; }
        .logo-box-wrap { position: absolute; bottom: -60px; left: 40px; width: 140px; height: 140px; background: #FFF; border-radius: 40px; border: 8px solid #FFF; box-shadow: 0 20px 40px rgba(0,0,0,0.1); overflow: hidden; cursor: pointer; transition: 0.3s; z-index: 10; }
        .logo-img { width: 100%; height: 100%; object-fit: cover; }
        .up-badge { position: absolute; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; color: #FFF; font-size: 20px; opacity: 0; transition: 0.3s; }
        .cover-area:hover .up-badge, .logo-box-wrap:hover .up-badge { opacity: 1; }
        .cover-area:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }

        #map-picker { width: 100%; height: 350px; border-radius: 20px; border: 2px solid #F1F5F9; margin-top: 20px; }

        /* ── Category Card Picker ── */
        .s-macro-filters { display:flex; background:#F8FAFC; border:1px solid #F1F5F9; border-radius:12px; padding:4px; margin-bottom:14px; }
        .s-macro-btn { flex:1; padding:10px 0; background:transparent; border:none; border-radius:8px; font-size:13px; font-weight:700; color:var(--text-muted); cursor:pointer; transition:all 0.2s; }
        .s-macro-btn.active { background:#FFF; color:var(--text-strong); box-shadow:0 2px 8px rgba(0,0,0,0.06); }
        .s-cat-drawer { max-height:260px; overflow-y:auto; display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:2px; }
        .s-cat-drawer::-webkit-scrollbar { width:5px; }
        .s-cat-drawer::-webkit-scrollbar-thumb { background:#E5E7EB; border-radius:10px; }
        .s-cat-item { border:2px solid #F1F5F9; border-radius:12px; padding:11px; display:flex; align-items:center; gap:10px; cursor:pointer; transition:all 0.2s; background:#FAFBFF; }
        .s-cat-item img { width:38px; height:38px; border-radius:8px; object-fit:cover; }
        .s-cat-item span { font-size:13px; font-weight:600; color:var(--text-strong); }
        .s-cat-item:hover { border-color:#9CA3AF; }
        .s-cat-item.s-cat-selected { border-color:var(--brand-purple); background:#F3F0FF; box-shadow:0 4px 12px rgba(107,78,230,0.1); }

        .save-footer { position: sticky; bottom: 30px; background: rgba(255,255,255,0.8); backdrop-filter: blur(20px); padding: 20px 40px; border-radius: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 15px 50px rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.3); z-index: 100; margin-top: 40px; transition: 0.3s; }
        .save-footer:hover { transform: translateY(-5px); }
        .btn-save { background: var(--brand-purple-grad); color: #FFF; border: none; padding: 16px 40px; border-radius: 18px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 25px rgba(107, 78, 230, 0.4); }
        .btn-save:hover { transform: scale(1.05); }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; margin-top: 10px;}
            .settings-container { display: flex; flex-direction: column; gap: 20px;}
            .settings-nav { 
                position: static; width: 100%; display: grid; grid-template-columns: 1fr 1fr; gap: 12px; 
                padding: 16px; margin-bottom: 20px; border-radius: 20px; background: #FFF; box-shadow: 0 10px 30px rgba(0,0,0,0.03); 
            }
            .snav-item { 
                flex-direction: column; justify-content: center; text-align: center; 
                margin-bottom: 0; padding: 16px 12px; font-size: 11px; white-space: normal; gap: 6px; border-radius: 14px;
            }
            .snav-item i { font-size: 20px; }
            .card { padding: 24px; }
            .form-row { grid-template-columns: 1fr; }
            .save-footer { flex-direction: column; gap:16px; padding: 20px; border-radius: 20px; text-align: center;}
            .btn-save { width: 100%; }
            #map-picker { height: 250px; }
            .logo-box-wrap { left: 50%; transform: translateX(-50%); }
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
            <form action="settings.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_settings">
                <input type="hidden" name="ShopLat" id="latInput" value="<?= $SHOP_DATA['ShopLat'] ?>">
                <input type="hidden" name="ShopLongt" id="lngInput" value="<?= $SHOP_DATA['ShopLongt'] ?>">

                <div class="content-wrapper">
                    
                    <!-- SPA SHIMMER SKELETON -->
                    <div id="skeleton-settings" class="settings-container">
                        <!-- Navigation Skeleton -->
                        <div class="settings-nav" style="border:none; box-shadow:none; padding:0;">
                            <?php for($i=0; $i<4; $i++): ?>
                            <div class="shimmer-bg" style="height:55px; width:100%; border-radius:15px; margin-bottom:5px;"></div>
                            <?php endfor; ?>
                        </div>

                        <!-- Content Skeleton -->
                        <div class="settings-content">
                            <div class="card" style="border:none;">
                                <div class="shimmer-bg" style="height:12px; width:100px; border-radius:4px; margin-bottom:10px;"></div>
                                <div class="shimmer-bg" style="height:28px; width:200px; border-radius:6px; margin-bottom:30px;"></div>
                                
                                <div class="shimmer-bg" style="height:260px; width:100%; border-radius:20px; margin-bottom:80px;"></div>
                                
                                <div class="form-row">
                                    <div class="shimmer-bg" style="height:56px; width:100%; border-radius:16px;"></div>
                                    <div class="shimmer-bg" style="height:56px; width:100%; border-radius:16px;"></div>
                                </div>
                                <div class="shimmer-bg" style="height:76px; width:100%; border-radius:20px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- REAL CONTENT HIDDEN INITIALLY -->
                    <div id="real-settings" style="display:none;">
                        <div class="settings-container fade-in-up">
                            <!-- Navigation on Left -->
                            <div class="settings-nav">
                                <div class="snav-item" onclick="scrollToSec('sec-identity')"><i class="fas fa-id-card"></i> Identity</div>
                                <div class="snav-item" onclick="scrollToSec('sec-info')"><i class="fas fa-info-circle"></i> Info</div>
                                <div class="snav-item" onclick="scrollToSec('sec-bank')"><i class="fas fa-university"></i> Pay out Multi-Bank</div>
                                <div class="snav-item" onclick="scrollToSec('sec-packages')"><i class="fas fa-box-open"></i> Packages</div>
                                <div class="snav-item" onclick="scrollToSec('sec-map')"><i class="fas fa-map-marked-alt"></i> Location</div>
                            </div>

                            <!-- Content on Right -->
                            <div class="settings-content">
                                
                                <!-- Visual Section -->
                                <div class="card" id="sec-identity" style="animation: none; transform: none; opacity: 1;">
                                    <span class="section-tag">Appearance</span>
                                    <h2 class="section-title">Visual Identity</h2>
                                    
                                    <div class="visual-identity">
                                        <div class="cover-area" onclick="document.getElementById('coverInput').click()">
                                            <img src="<?= htmlspecialchars($SHOP_DATA['ShopCover'] ?: 'https://images.unsplash.com/photo-1557683316-973673baf926') ?>" class="cover-img" id="coverPreview" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1557683316-973673baf926';">
                                            <div class="up-badge"><i class="fas fa-cloud-upload-alt"></i></div>
                                            <input type="file" name="ShopCover" id="coverInput" hidden onchange="previewImg(this, 'coverPreview')">
                                        </div>
                                        <div class="logo-box-wrap" onclick="document.getElementById('logoInput').click()">
                                            <img src="<?= htmlspecialchars($SHOP_DATA['ShopLogo']) ?>" class="logo-img" id="logoPreview" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['SellerName'] ?? 'S') ?>&background=EBE8FA&color=6B4EE6&bold=true';">
                                            <div class="up-badge"><i class="fas fa-camera"></i></div>
                                            <input type="file" name="ShopLogo" id="logoInput" hidden onchange="previewImg(this, 'logoPreview')">
                                        </div>
                                    </div>
                                    <div style="margin-top: 50px;">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label>Display Name</label>
                                                <input type="text" name="ShopName" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['ShopName']) ?>" required>
                                            </div>
                                        </div>
                                        <!-- Category Picker -->
                                        <div>
                                            <label style="font-size:13px;font-weight:700;color:var(--text-strong);display:block;margin-bottom:12px;">Business Category</label>
                                            <input type="hidden" name="CategoryID" id="settingsCatId" value="<?= (int)$curCatId ?>">

                                            <!-- Macro Filter Tabs -->
                                            <div class="s-macro-filters">
                                                <button type="button" class="s-macro-btn active" onclick="sFilterCats('QOON', this)">QOON</button>
                                                <button type="button" class="s-macro-btn" onclick="sFilterCats('Kenz', this)">Kenz Madinty</button>
                                                <button type="button" class="s-macro-btn" onclick="sFilterCats('Pro', this)">QOON Pro</button>
                                            </div>

                                            <!-- Category Cards -->
                                            <div class="s-cat-drawer" id="sCatDrawer">
                                                <?php foreach($allCats as $c): ?>
                                                <div class="s-cat-item <?= $c['CategoryId'] == $curCatId ? 's-cat-selected' : '' ?>"
                                                     data-type="<?= htmlspecialchars($c['Type']) ?>"
                                                     data-pro="<?= htmlspecialchars($c['Pro']) ?>"
                                                     onclick="sSelectCat(this, <?= (int)$c['CategoryId'] ?>)">
                                                    <img src="<?= htmlspecialchars($c['Photo']) ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['EnglishCategory']) ?>&background=EBE8FA&color=6B4EE6'">
                                                    <span><?= htmlspecialchars($c['EnglishCategory'] ?: $c['FrenchCategory']) ?></span>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Basic Info -->
                                <div class="card" id="sec-info" style="animation: none; transform: none; opacity: 1;">
                                    <span class="section-tag">Communication</span>
                                    <h2 class="section-title">Contact Information</h2>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Email Address</label>
                                            <input type="email" name="Email" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['Email']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Store Support Line</label>
                                            <input type="text" name="ShopPhone" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['ShopPhone']) ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Owner Personal Mobile</label>
                                        <input type="text" name="OwnerPhone" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['OwnerPhone']) ?>">
                                    </div>
                                </div>

                                <!-- Bank Details -->
                                <div class="card" id="sec-bank" style="animation: none; transform: none; opacity: 1;">
                                    <span class="section-tag">Financials</span>
                                    <h2 class="section-title">Pay out Multi-Bank</h2>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Full Bank Name</label>
                                            <input type="text" name="BankName" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['BankName']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Account RIB (24 Digits)</label>
                                            <input type="text" name="BankNum" class="form-control" value="<?= htmlspecialchars($SHOP_DATA['BankNum']) ?>">
                                        </div>
                                    </div>
                                    <div style="display:flex; align-items:center; gap:10px; color:var(--text-muted); font-size:11px; background:#F8FAFC; padding:15px; border-radius:15px;">
                                        <i class="fas fa-shield-alt" style="color:var(--tag-green-text);"></i>
                                        Your sensitive banking data is encrypted and used only for automated payouts.
                                    </div>
                                </div>

                                <!-- Packages Section — Subscription Capabilities Matrix -->
                                <div class="card" id="sec-packages" style="animation: none; transform: none; opacity: 1; padding: 0; overflow: hidden;">

                                    <!-- Header -->
                                    <div style="padding: 32px 36px 20px; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px;">
                                        <div>
                                            <span class="section-tag">Subscription</span>
                                            <h2 class="section-title" style="margin-bottom:6px;">Package Capabilities Matrix</h2>
                                            <p style="font-size:13px; color:var(--text-muted);">Choose the plan that fits your business. Unlock more capabilities as you grow with QOON.</p>
                                        </div>
                                        <div style="background:#F8FAFC; border:1px solid #F1F5F9; border-radius:14px; padding:12px 20px; text-align:right; min-width:140px;">
                                            <div style="font-size:10px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:1px; margin-bottom:4px;">My Wallet</div>
                                            <div style="font-size:20px; font-weight:800; color:var(--text-strong);"><?= number_format($shopBalance, 2) ?> <span style="font-size:12px; font-weight:500; color:var(--text-muted);">MAD</span></div>
                                            <div id="wallet-live" style="font-size:11px; color:var(--text-muted);"></div>
                                        </div>
                                    </div>

                                    <?php
                                    // Current plan display name
                                    $currentPlanKey = strtoupper($shopSub);
                                    $planDisplayMap = ['FREE' => 'Free Tier', 'PRO' => 'Premium Pro', 'PLUS' => 'Premium Plus'];
                                    $currentPlanName = $planDisplayMap[$currentPlanKey] ?? 'Free Tier';
                                    ?>

                                    <!-- Tier Selection Headers -->
                                    <div style="display:grid; grid-template-columns: 1.4fr 1fr 1fr 1fr; border-top:1px solid #F1F5F9;" id="planHeaderRow">

                                        <div style="padding:16px 24px; background:#F8FAFC; border-right:1px solid #F1F5F9; display:flex; align-items:flex-end;">
                                            <div style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; color:var(--text-muted);">Feature</div>
                                        </div>

                                        <!-- Free Tier -->
                                        <div class="tier-col <?= $currentPlanName === 'Free Tier' ? 'tier-active-free' : '' ?>" data-plan="Free Tier" data-price="0" onclick="selectTier(this)" style="padding:16px 12px; text-align:center; border-right:1px solid #F1F5F9; background:#F8FAFC; cursor:pointer; transition:0.3s; position:relative;">
                                            <?php if($currentPlanName === 'Free Tier'): ?>
                                            <div style="position:absolute;top:6px;left:50%;transform:translateX(-50%);background:#10B981;color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap;">✓ Current</div>
                                            <?php endif; ?>
                                            <div style="font-size:10px;font-weight:700;color:#9CA3AF;text-transform:uppercase;letter-spacing:1px;margin-top:<?= $currentPlanName === 'Free Tier' ? '16px' : '0' ?>;">Free Tier</div>
                                            <div style="font-size:20px;font-weight:800;color:var(--text-strong);margin:4px 0;">0 <span style="font-size:11px;font-weight:500;color:var(--text-muted);">MAD</span></div>
                                            <div style="font-size:10px;color:var(--text-muted);">Forever</div>
                                        </div>

                                        <!-- Premium Pro -->
                                        <div class="tier-col <?= $currentPlanName === 'Premium Pro' ? 'tier-active-pro' : '' ?>" data-plan="Premium Pro" data-price="<?= $planPrices['Premium Pro'] ?>" onclick="selectTier(this)" style="padding:16px 12px; text-align:center; border-right:1px solid #EDE9FE; background:linear-gradient(180deg,#F3F0FF 0%,#FAF8FF 100%); cursor:pointer; transition:0.3s; position:relative;">
                                            <div style="position:absolute;top:0;left:50%;transform:translateX(-50%);background:var(--brand-purple);color:#fff;font-size:9px;font-weight:700;padding:3px 10px;border-radius:0 0 8px 8px;white-space:nowrap;letter-spacing:1px;">
                                                <?= $currentPlanName === 'Premium Pro' ? '✓ Current' : 'Popular' ?>
                                            </div>
                                            <div style="font-size:10px;font-weight:700;color:var(--brand-purple);text-transform:uppercase;letter-spacing:1px;margin-top:16px;">Premium Pro</div>
                                            <div style="font-size:20px;font-weight:800;color:var(--text-strong);margin:4px 0;"><?= $planPrices['Premium Pro'] ?> <span style="font-size:11px;font-weight:500;color:var(--text-muted);">MAD</span></div>
                                            <div style="font-size:10px;color:var(--text-muted);">/ month</div>
                                        </div>

                                        <!-- Premium Plus -->
                                        <div class="tier-col <?= $currentPlanName === 'Premium Plus' ? 'tier-active-plus' : '' ?>" data-plan="Premium Plus" data-price="<?= $planPrices['Premium Plus'] ?>" onclick="selectTier(this)" style="padding:16px 12px; text-align:center; background:linear-gradient(180deg,#FFF8E1 0%,#FFFDF5 100%); cursor:pointer; transition:0.3s; position:relative;">
                                            <?php if($currentPlanName === 'Premium Plus'): ?>
                                            <div style="position:absolute;top:6px;left:50%;transform:translateX(-50%);background:#D97706;color:#fff;font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap;">✓ Current</div>
                                            <?php endif; ?>
                                            <div style="font-size:10px;font-weight:700;color:#D97706;text-transform:uppercase;letter-spacing:1px;margin-top:<?= $currentPlanName === 'Premium Plus' ? '16px' : '0' ?>;">Premium Plus</div>
                                            <div style="font-size:20px;font-weight:800;color:var(--text-strong);margin:4px 0;"><?= $planPrices['Premium Plus'] ?> <span style="font-size:11px;font-weight:500;color:var(--text-muted);">MAD</span></div>
                                            <div style="font-size:10px;color:var(--text-muted);">/ month</div>
                                        </div>
                                    </div>

                                    <!-- Feature Rows -->
                                    <?php
                                    $features = [
                                        ['Digital Store Creation',      false, true,  true ],
                                        ['Full Control Of Store',        false, true,  true ],
                                        ['Add > 5 Products',             false, true,  true ],
                                        ['Receive Orders',               false, true,  false],
                                        ['Track & Manage Orders',        false, true,  true ],
                                        ['Delivery Service Request',     false, true,  true ],
                                        ['QOON Pay Integration',         false, false, true ],
                                        ['QOON Card Access',             false, false, true ],
                                        ['Withdraw Profits',             false, false, true ],
                                        ['QOON Ad Boost',                false, false, true ],
                                        ['Boost Now Pay Later',          false, false, true ],
                                        ['Organic CEO Dashboard',        false, false, false],
                                        ['5 Stories / Month',            false, true,  true ],
                                        ['5 Posts / Month',              false, true,  true ],
                                        ['Customer Interactions',        false, true,  true ],
                                        ['Cloud Hosting Server',         false, true,  true ],
                                    ];
                                    foreach($features as $i => $f):
                                        $rowBg = ($i % 2 === 0) ? '#FFFFFF' : '#FAFBFF';
                                    ?>
                                    <div style="display:grid; grid-template-columns:1.4fr 1fr 1fr 1fr; border-top:1px solid #F8FAFC;">
                                        <div style="padding:13px 24px; background:<?= $rowBg ?>; font-size:13px; color:var(--text-strong); font-weight:500; display:flex; align-items:center;"><?= htmlspecialchars($f[0]) ?></div>
                                        <!-- Free -->
                                        <div class="feat-col-free" style="padding:13px 12px; background:<?= $rowBg ?>; text-align:center; display:flex; align-items:center; justify-content:center; border-left:1px solid #F1F5F9;">
                                            <?php if($f[1]): ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#ECFDF5;color:#10B981;font-size:11px;"><i class="fas fa-check"></i></span>
                                            <?php else: ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#F9FAFB;color:#D1D5DB;font-size:11px;"><i class="fas fa-times"></i></span><?php endif; ?>
                                        </div>
                                        <!-- Pro -->
                                        <div class="feat-col-pro" style="padding:13px 12px; background:<?= $i%2===0 ? '#FAF8FF' : '#F7F5FF' ?>; text-align:center; display:flex; align-items:center; justify-content:center; border-left:1px solid #EDE9FE;">
                                            <?php if($f[2]): ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#EDE9FE;color:#6B4EE6;font-size:11px;"><i class="fas fa-check"></i></span>
                                            <?php else: ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#F3F4F6;color:#D1D5DB;font-size:11px;"><i class="fas fa-times"></i></span><?php endif; ?>
                                        </div>
                                        <!-- Plus -->
                                        <div class="feat-col-plus" style="padding:13px 12px; background:<?= $i%2===0 ? '#FFFDF5' : '#FFFBEB' ?>; text-align:center; display:flex; align-items:center; justify-content:center; border-left:1px solid #FDE68A;">
                                            <?php if($f[3]): ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#FEF3C7;color:#D97706;font-size:11px;"><i class="fas fa-check"></i></span>
                                            <?php else: ?><span style="display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:50%;background:#F9FAFB;color:#D1D5DB;font-size:11px;"><i class="fas fa-times"></i></span><?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>

                                    <!-- Subscribe Footer — appears after selection -->
                                    <div id="plan-cta" style="display:none; padding:20px 28px; border-top:1px solid #F1F5F9; background:#FAFBFF; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:14px;">
                                        <div>
                                            <div id="plan-cta-title" style="font-size:14px;font-weight:700;color:var(--text-strong);"></div>
                                            <div id="plan-cta-sub" style="font-size:12px;color:var(--text-muted);margin-top:3px;"></div>
                                        </div>
                                        <button type="button" id="plan-subscribe-btn" onclick="subscribePlan()" style="background:var(--brand-purple);color:#FFF;border:none;padding:14px 32px;border-radius:16px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 8px 20px rgba(107,78,230,0.3);transition:0.3s;">
                                            <i class="fas fa-bolt" style="margin-right:8px;"></i> <span id="plan-btn-label">Activate</span>
                                        </button>
                                    </div>

                                </div>

                                <!-- Map Section -->
                                <div class="card" id="sec-map" style="animation: none; transform: none; opacity: 1;">
                                    <span class="section-tag">Geographic</span>
                                    <h2 class="section-title">Operational Location</h2>
                                    <div id="map-picker"></div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="save-footer">
                        <div>
                            <div style="font-weight:700; color:var(--text-strong); font-size:14px;">Review your changes</div>
                            <div style="font-size:12px; color:var(--text-muted);">Ensure your bank details are 100% accurate.</div>
                        </div>
                        <button type="submit" class="btn-save">Finalize Updates <i class="fas fa-check-circle" style="margin-left:8px;"></i></button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        function scrollToSec(id) {
            document.getElementById(id).scrollIntoView({behavior: 'smooth'});
            document.querySelectorAll('.snav-item').forEach(x => x.classList.remove('active'));
            event.target.closest('.snav-item').classList.add('active');
        }

        function previewImg(input, targetId) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) { document.getElementById(targetId).src = e.target.result; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // ── Settings Category Picker ────────────────────────────────
        function sFilterCats(macro, btnObj) {
            document.querySelectorAll('.s-macro-btn').forEach(b => b.classList.remove('active'));
            if (btnObj) btnObj.classList.add('active');
            document.querySelectorAll('.s-cat-item').forEach(item => {
                const type = item.getAttribute('data-type');
                const pro  = item.getAttribute('data-pro');
                let show = false;
                if (macro === 'QOON' && type === 'Top' && pro === 'Normal') show = true;
                if (macro === 'Kenz' && type === 'Small') show = true;
                if (macro === 'Pro'  && pro === 'Pro') show = true;
                item.style.display = show ? 'flex' : 'none';
            });
        }

        function sSelectCat(el, id) {
            document.querySelectorAll('.s-cat-item').forEach(c => c.classList.remove('s-cat-selected'));
            el.classList.add('s-cat-selected');
            document.getElementById('settingsCatId').value = id;
        }

        // Auto-show the tab containing the current selected category
        (function initSettingsCatFilter() {
            const selected = document.querySelector('.s-cat-item.s-cat-selected');
            if (!selected) { sFilterCats('QOON', null); return; }
            const type = selected.getAttribute('data-type');
            const pro  = selected.getAttribute('data-pro');
            let macro = 'QOON';
            if (type === 'Small') macro = 'Kenz';
            else if (pro === 'Pro') macro = 'Pro';
            const btns = document.querySelectorAll('.s-macro-btn');
            btns.forEach(b => b.classList.remove('active'));
            if (macro === 'QOON') btns[0].classList.add('active');
            if (macro === 'Kenz') btns[1].classList.add('active');
            if (macro === 'Pro')  btns[2].classList.add('active');
            sFilterCats(macro, null);
        })();

        const initialLat = <?= $SHOP_DATA['ShopLat'] ?: 33.5731 ?>;
        const initialLng = <?= $SHOP_DATA['ShopLongt'] ?: -7.5898 ?>;
        const map = L.map('map-picker').setView([initialLat, initialLng], 14);
        
        // Using CartoDB Positron for a cleaner, pro look
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB'
        }).addTo(map);

        const marker = L.marker([initialLat, initialLng], {draggable: true}).addTo(map);
        marker.on('dragend', function(e) {
            const pos = marker.getLatLng();
            document.getElementById('latInput').value = pos.lat;
            document.getElementById('lngInput').value = pos.lng;
        });
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            document.getElementById('latInput').value = e.latlng.lat;
            document.getElementById('lngInput').value = e.latlng.lng;
        });

        window.onload = function() {
            setTimeout(() => {
                const sk = document.getElementById('skeleton-settings');
                const rl = document.getElementById('real-settings');
                if(sk && rl) {
                    sk.style.display = 'none';
                    rl.style.display = 'block';
                    // Leaflet must be forced to redraw once its container becomes visible
                    setTimeout(() => { map.invalidateSize(); }, 50);
                }
            }, 500);
        }

        // ─── Plan Selection Logic ───────────────────────────────────────
        let selectedPlan = null;
        let selectedPrice = 0;
        const walletBalance = <?= json_encode($shopBalance) ?>;
        const currentPlan = <?= json_encode($currentPlanName) ?>;

        function selectTier(el) {
            const plan = el.getAttribute('data-plan');
            const price = parseFloat(el.getAttribute('data-price'));

            // Deselect all
            document.querySelectorAll('.tier-col').forEach(c => {
                c.style.outline = 'none';
                c.style.boxShadow = 'none';
            });

            // Highlight selected
            el.style.outline = '3px solid var(--brand-purple)';
            el.style.boxShadow = '0 0 0 4px rgba(107,78,230,0.1)';

            selectedPlan = plan;
            selectedPrice = price;

            const cta = document.getElementById('plan-cta');
            cta.style.display = 'flex';

            const title = document.getElementById('plan-cta-title');
            const sub = document.getElementById('plan-cta-sub');
            const btnLabel = document.getElementById('plan-btn-label');
            const btn = document.getElementById('plan-subscribe-btn');

            if (plan === currentPlan) {
                title.textContent = '✓ You are already on ' + plan;
                sub.textContent = 'This is your current active plan.';
                btn.style.display = 'none';
            } else if (price === 0) {
                title.textContent = 'Switch to Free Tier';
                sub.textContent = 'No charge — this plan is free forever.';
                btn.style.display = 'inline-flex';
                btn.style.background = '#6B7280';
                btn.style.boxShadow = 'none';
                btnLabel.textContent = 'Activate Free Tier';
            } else if (walletBalance >= price) {
                title.textContent = 'Activate ' + plan;
                sub.textContent = price + ' MAD will be deducted from your wallet (Balance: ' + walletBalance.toFixed(2) + ' MAD)';
                btn.style.display = 'inline-flex';
                btn.style.background = 'var(--brand-purple)';
                btn.style.boxShadow = '0 8px 20px rgba(107,78,230,0.3)';
                btnLabel.textContent = 'Pay ' + price + ' MAD & Activate';
            } else {
                title.textContent = 'Insufficient Wallet Balance';
                sub.textContent = 'You need ' + price + ' MAD but only have ' + walletBalance.toFixed(2) + ' MAD. Please top up.';
                btn.style.display = 'inline-flex';
                btn.style.background = '#EF4444';
                btn.style.boxShadow = 'none';
                btnLabel.textContent = 'Top Up Wallet';
            }
        }

        function subscribePlan() {
            if (!selectedPlan) return;
            if (walletBalance < selectedPrice && selectedPrice > 0) {
                alert('Please top up your wallet first.');
                return;
            }

            const btn = document.getElementById('plan-subscribe-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="margin-right:8px;"></i> Processing...';

            const fd = new FormData();
            fd.append('plan', selectedPlan);
            fd.append('price', selectedPrice);

            fetch('subscribe_plan.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('plan-cta-title').textContent = '✓ ' + data.msg;
                    document.getElementById('plan-cta-sub').textContent = 'New balance: ' + parseFloat(data.new_balance).toFixed(2) + ' MAD';
                    btn.style.display = 'none';
                    // Reload after 1.5s to refresh current plan badge
                    setTimeout(() => location.reload(), 1500);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-bolt" style="margin-right:8px;"></i> <span>Retry</span>';
                    document.getElementById('plan-cta-title').textContent = '✗ ' + data.msg;
                    document.getElementById('plan-cta-sub').textContent = '';
                }
            });
        }
    </script>
</body>
</html>
