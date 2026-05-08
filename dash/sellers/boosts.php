<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];

// --- SERVER-SIDE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_boost') {
        $name = $con->real_escape_string($_POST['BoostName'] ?? 'New Boost');
        $typeID = $con->real_escape_string($_POST['BoostTypeID'] ?? '1');
        $duration = $con->real_escape_string($_POST['Duration'] ?? '24h');
        $contentID = $con->real_escape_string($_POST['ContentID'] ?? '');
        $price = "0.00"; 
        
        if($duration == '24h') $price = "50.00";
        if($duration == '3d') $price = "150.00";
        if($duration == '7d') $price = "300.00";

        $photo = '';
        if(!empty($contentID)) {
            $pRes = $con->query("SELECT PostPhoto, PostPhoto2, PostPhoto3, PostPhoto4 FROM Posts WHERE PostId='$contentID' LIMIT 1");
            if($pRes && $row = $pRes->fetch_assoc()) {
                $photo = !empty($row['PostPhoto']) ? $row['PostPhoto'] : (!empty($row['PostPhoto2']) ? $row['PostPhoto2'] : (!empty($row['PostPhoto3']) ? $row['PostPhoto3'] : $row['PostPhoto4']));
            }
        }

        $city = $SHOP_DATA['ShopCity'] ?? 'General';

        $sql = "INSERT INTO BoostsByShop (ShopID, BoostName, BoostTypeID, BoostPrice, BoostTimeDuration, BoostPhoto, BoostLinkOrProductID, BoostStatus, BoostCity) 
                VALUES ('$sellerID', '$name', '$typeID', '$price', '$duration', '$photo', '$contentID', 'PENDING', '$city')";
        $con->query($sql);
        header("Location: boosts.php?msg=boost_requested");
        exit;
    }
}

// Fetch existing boosts
$boostsRes = $con->query("SELECT * FROM BoostsByShop WHERE ShopID='$sellerID' ORDER BY BoostsByShopID DESC");
$boosts = []; $runCount = 0; $totalSpend = 0;
if($boostsRes) { 
    while($r = $boostsRes->fetch_assoc()) { 
        $boosts[] = $r; 
        if(strtoupper($r['BoostStatus']) === 'ACTIVE') $runCount++;
        $totalSpend += (float)$r['BoostPrice'];
    } 
}

// Fetch content available to boost (Posts/Reels)
$contentRes = $con->query("SELECT PostId, PostText, PostPhoto, Video FROM Posts WHERE ShopID='$sellerID' ORDER BY PostId DESC LIMIT 20");
$contentToBoost = [];
if($contentRes) { while($r = $contentRes->fetch_assoc()) { $contentToBoost[] = $r; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boost Campaigns | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-master: #F3F5FA; --bg-surface: #FFFFFF;
            --text-strong: #111827; --text-base: #374151; --text-muted: #9CA3AF;
            --brand-purple: #6B4EE6; --brand-purple-light: #F3F0FF;
            --brand-purple-grad: linear-gradient(135deg, #7C5CFF 0%, #5235E8 100%);
            --radius-lg: 24px; --radius-md: 16px; --radius-sm: 12px;
            --tag-green: #D1FAE5; --tag-green-text: #059669;
            --tag-orange: #FEF3C7; --tag-orange-text: #D97706;
            --tag-pink: #FEE2E2; --tag-pink-text: #DC2626;
            --tag-blue: #DBEAFE; --tag-blue-text: #2563EB;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-surface); color: var(--text-base); height: 100vh; overflow: hidden; }
        .app-envelope { display: flex; width: 100%; height: 100%; overflow: hidden; }
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background: #FAFAFB; }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; display: flex; flex-direction: column; gap: 30px; }

        /* ====== TOP BAR ====== */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; }
        .search-bar { background: var(--bg-surface); border-radius: 30px; padding: 12px 24px; display: flex; align-items: center; gap: 12px; width: 350px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .search-bar input { border: none; outline: none; background: transparent; width: 100%; font-family: 'Inter', sans-serif; font-size: 13px; color: var(--text-strong); }
        .user-nav { display: flex; align-items: center; gap: 20px; }
        .profile-btn { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .profile-badge { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-surface); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

        /* ====== WELCOME / DASHBOARD HEADER ====== */
        .welcome-banner { 
            background: var(--brand-purple-grad); border-radius: var(--radius-lg); padding: 34px 40px; color: #FFF; 
            display: flex; flex-direction: column; justify-content: center; position: relative; overflow: hidden;
        }
        .wb-subtitle { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; opacity: 0.8; }
        .wb-title { font-size: 26px; font-weight: 700; margin-bottom: 20px; }
        .btn-launch { 
            background: #FFF; color: var(--brand-purple); padding: 12px 28px; border-radius: 30px; 
            font-size: 13px; font-weight: 700; text-decoration: none; border:none;
            display: inline-flex; align-items: center; gap: 8px; width: fit-content; cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1); transition: 0.3s;
        }
        .btn-launch:hover { transform: translateY(-3px); }

        /* ====== METRICS ====== */
        .metrics-row { display: flex; gap: 20px; }
        .metric-pill { 
            background: var(--bg-surface); border-radius: var(--radius-lg); flex: 1;
            padding: 18px 24px; display: flex; align-items: center; gap: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .mp-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .mp-icon.purple { background: var(--brand-purple-light); color: var(--brand-purple); }
        .mp-icon.blue { background: #E8F4F8; color: #0DCAF0; }
        .mp-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
        .mp-value { font-size: 18px; font-weight: 700; color: var(--text-strong); }

        /* ====== PREMIUM TABLE ====== */
        .section-header { display: flex; justify-content: space-between; align-items: flex-end; }
        .s-title { font-size: 20px; font-weight: 700; color: var(--text-strong); }
        .table-wrap { background: var(--bg-surface); border-radius: var(--radius-lg); padding: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .dr-header { display: flex; padding: 20px; border-bottom: 1px solid #F3F4F6; }
        .dr-col { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; flex: 1; }
        .data-row { display: flex; align-items: center; padding: 16px 20px; border-bottom: 1px solid #F3F4F6; transition: 0.2s; }
        .data-row:hover { background: #FAFAFB; }
        .data-row:last-child { border-bottom: none; }
        .dr-main { display: flex; align-items: center; gap: 12px; }
        .dr-avatar { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; background: #F3F4F6; }
        .dr-title { font-size: 14px; font-weight: 700; color: var(--text-strong); }
        .dr-sub { font-size: 11px; color: var(--text-muted); }
        
        .tag { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .tag-green { background: var(--tag-green); color: var(--tag-green-text); }
        .tag-orange { background: var(--tag-orange); color: var(--tag-orange-text); }
        .tag-blue { background: var(--tag-blue); color: var(--tag-blue-text); }
        .tag-pink { background: var(--tag-pink); color: var(--tag-pink-text); }

        /* ====== MODAL ====== */
        dialog { margin: auto; border: none; border-radius: var(--radius-lg); padding: 0; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 500px; background: transparent; }
        dialog::backdrop { background: rgba(107, 78, 230, 0.1); backdrop-filter: blur(8px); }
        .modal-box { padding: 40px; background: #FFF; border-radius: var(--radius-lg); display: flex; flex-direction: column; gap: 20px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .modal-header h3 { font-size: 22px; font-weight: 700; color: var(--text-strong); }
        .close-btn { background: none; border: none; font-size: 20px; color: var(--text-muted); cursor: pointer; }
        
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group label { font-size: 13px; font-weight: 700; color: var(--text-base); }
        .form-control { background: #F3F5FA; border: 1px solid transparent; padding: 14px 18px; border-radius: 12px; font-size: 14px; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--brand-purple); background: #FFF; box-shadow: 0 0 0 4px var(--brand-purple-light); }

        .content-picker { display: flex; gap: 12px; overflow-x: auto; padding: 4px 0 10px; }
        .pick-item { flex: 0 0 90px; height: 90px; border-radius: 12px; overflow: hidden; border: 3px solid transparent; cursor: pointer; transition: 0.2s; position: relative; }
        .pick-item img { width: 100%; height: 100%; object-fit: cover; }
        .pick-item.selected { border-color: var(--brand-purple); transform: scale(1.05); }
        .pick-check { position: absolute; top:5px; right:5px; background: var(--brand-purple); color:#FFF; width:18px; height:18px; border-radius:50%; display:none; align-items:center; justify-content:center; font-size:10px; z-index:10; }
        .pick-item.selected .pick-check { display: flex; }

        .tier-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .tier-card { border: 1px solid #E5E7EB; border-radius: 16px; padding: 16px 10px; text-align: center; cursor: pointer; transition: 0.2s; }
        .tier-card.active { border-color: var(--brand-purple); background: var(--brand-purple-light); }
        .tier-card .t-name { font-size: 10px; font-weight: 700; color: var(--brand-purple); text-transform: uppercase; margin-bottom: 6px; }
        .tier-card .t-price { font-size: 15px; font-weight: 800; color: var(--text-strong); }
        .tier-card .t-msg { font-size: 9px; color: var(--text-muted); margin-top: 4px; }

        .submit-trigger { background: var(--brand-purple-grad); color: #FFF; border: none; padding: 16px; border-radius: 16px; font-weight: 700; font-size: 14px; cursor: pointer; margin-top: 10px; box-shadow: 0 10px 20px rgba(107,78,230,0.3); }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; }
            .top-navbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            .search-bar { width: 100%; align-items: center;}
            .user-nav { width: 100%; justify-content: space-between; }
            .grid-layout { grid-template-columns: 1fr; gap: 20px; }
            .metrics-row { flex-direction: column; }
            .table-wrap { overflow-x: visible; padding: 0; background: transparent; box-shadow: none; }
            .dr-header { display: none !important; }
            .data-row {
                display: grid;
                grid-template-columns: auto 1fr;
                grid-template-areas: 
                    "icon title"
                    "icon sub"
                    "status amount";
                gap: 8px 16px;
                align-items: center;
                background: var(--bg-surface);
                padding: 16px;
                border-radius: 16px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
                border: 1px solid #F3F4F6;
                margin-bottom: 12px;
                min-width: unset;
                width: 100%;
            }
            .data-row .dr-col:nth-child(1) { grid-area: icon; align-self: flex-start; }
            .data-row .dr-col:nth-child(2) { grid-area: title; }
            .data-row .dr-col:nth-child(3) { grid-area: status; margin-top: 8px; }
            .data-row .dr-col:nth-child(4) { grid-area: amount; text-align: right; margin-top: 8px; }
            dialog { max-width: calc(100% - 32px); margin: 16px auto; width: 100%; }
            .modal-box { padding: 20px; }
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

                <!-- Header NavBar -->
                <header class="top-navbar">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search campaigns...">
                    </div>
                    <div class="user-nav">
                        <div class="profile-btn">
                            <?php 
                            $logo = !empty($SHOP_DATA['ShopLogo']) ? htmlspecialchars($SHOP_DATA['ShopLogo']) : "https://ui-avatars.com/api/?name=".urlencode($_SESSION['SellerName'])."&background=EBE8FA&color=6B4EE6&bold=true";
                            ?>
                            <img src="<?= $logo ?>" class="profile-badge">
                            <div class="profile-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                        </div>
                    </div>
                </header>

                <!-- Welcome Banner with Launch Button -->
                <div class="welcome-banner">
                    <div class="wb-subtitle">Ad Manager</div>
                    <div class="wb-title">Reach More Customers in <?= htmlspecialchars($SHOP_DATA['ShopCity'] ?? 'your city') ?></div>
                    <button class="btn-launch" onclick="window.boostModal.showModal()">
                        <i class="fas fa-rocket"></i> Launch New Boost
                    </button>
                    <!-- Visual Sparkle decoration like home page -->
                    <div style="position: absolute; right: 50px; top: 20px; font-size: 150px; color: rgba(255,255,255,0.08); font-weight: 100;">+</div>
                </div>

                <!-- SPA SHIMMER SKELETON -->
                <div id="skeleton-boosts">
                    <!-- Metrics Pilled Row Skeleton -->
                    <div class="metrics-row" style="margin-bottom:30px;">
                        <div class="metric-pill" style="border:none;">
                            <div class="shimmer-bg" style="width:44px; height:44px; border-radius:12px;"></div>
                            <div style="flex:1;">
                                <div class="shimmer-bg" style="height:11px; width:40%; border-radius:4px; margin-bottom:6px;"></div>
                                <div class="shimmer-bg" style="height:18px; width:20%; border-radius:4px;"></div>
                            </div>
                        </div>
                        <div class="metric-pill" style="border:none;">
                            <div class="shimmer-bg" style="width:44px; height:44px; border-radius:12px;"></div>
                            <div style="flex:1;">
                                <div class="shimmer-bg" style="height:11px; width:40%; border-radius:4px; margin-bottom:6px;"></div>
                                <div class="shimmer-bg" style="height:18px; width:20%; border-radius:4px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Data Table Skeleton -->
                    <div>
                        <div class="section-header" style="margin-bottom: 20px;">
                            <div class="shimmer-bg" style="height:24px; width:30%; border-radius:4px;"></div>
                        </div>
                        <div class="table-wrap">
                            <div class="dr-header">
                                <div class="dr-col" style="flex: 1.5;"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px;"></div></div>
                                <div class="dr-col" style="text-align: right;"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px; display:inline-block;"></div></div>
                            </div>
                            <?php for($s=0; $s<3; $s++): ?>
                            <div class="data-row" style="border:none;">
                                <div class="dr-col" style="flex: 1.5;">
                                    <div class="dr-main">
                                        <div class="shimmer-bg dr-avatar"></div>
                                        <div style="width:100%;">
                                            <div class="shimmer-bg" style="height:14px; width:70%; border-radius:4px; margin-bottom:4px;"></div>
                                            <div class="shimmer-bg" style="height:11px; width:40%; border-radius:4px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:20px; width:60%; border-radius:10px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:14px; width:40%; border-radius:4px;"></div></div>
                                <div class="dr-col" style="text-align: right;"><div class="shimmer-bg" style="height:14px; width:50%; border-radius:4px; display:inline-block;"></div></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- REAL CONTENT HIDDEN INITIALLY -->
                <div id="real-boosts" style="display:none;">
                    <!-- Metrics Pilled Row -->
                    <div class="metrics-row" style="margin-bottom:30px;">
                        <div class="metric-pill fade-in-up" style="animation-delay: 0s;">
                            <div class="mp-icon purple">
                                <i class="fas fa-fire-alt"></i>
                            </div>
                            <div class="mp-info">
                                <div class="mp-label">Running Boosts</div>
                                <div class="mp-value"><?= number_format($runCount) ?> Campaigns</div>
                            </div>
                            <i class="fas fa-ellipsis-v" style="color: var(--text-muted);"></i>
                        </div>

                        <div class="metric-pill fade-in-up" style="animation-delay: 0.1s;">
                            <div class="mp-icon blue">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <div class="mp-info">
                                <div class="mp-label">Campaign Spend</div>
                                <div class="mp-value"><?= number_format($totalSpend, 2) ?> MAD</div>
                            </div>
                            <i class="fas fa-ellipsis-v" style="color: var(--text-muted);"></i>
                        </div>
                    </div>

                    <!-- Main Data Table -->
                    <div>
                        <div class="section-header fade-in-up" style="animation-delay: 0.2s; margin-bottom: 20px;">
                            <div class="s-title">Active Advertising Campaigns</div>
                        </div>

                        <div class="table-wrap fade-in-up" style="animation-delay: 0.2s;">
                            <div class="dr-header">
                                <div class="dr-col" style="flex: 1.5;">CAMPAIGN & CONTENT</div>
                                <div class="dr-col">STATUS</div>
                                <div class="dr-col">DURATION</div>
                                <div class="dr-col" style="text-align: right;">SPEND</div>
                            </div>

                            <?php if (empty($boosts)): ?>
                                <div style="padding: 60px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-rocket" style="font-size: 40px; margin-bottom: 20px; opacity: 0.2;"></i>
                                    <div style="font-size: 14px; font-weight: 500;">No campaigns found. Start your first boost today!</div>
                                </div>
                            <?php else: ?>
                                <?php 
                                    $delayC = 0.3;
                                    foreach ($boosts as $b): 
                                    $st = strtoupper($b['BoostStatus']);
                                    $tagClass = 'tag-blue'; // PENDING
                                    $icon = 'fa-clock';
                                    if ($st == 'ACTIVE') { $tagClass = 'tag-green'; $icon = 'fa-check'; }
                                    if ($st == 'EXPIRED' || $st == 'STOPPEDBYSHOP') { $tagClass = 'tag-pink'; $icon = 'fa-times'; }
                                    if ($st == 'INREVIEW') { $tagClass = 'tag-orange'; $icon = 'fa-search'; }
                                ?>
                                    <div class="data-row fade-in-up" style="animation-delay: <?= $delayC ?>s;">
                                        <div class="dr-col" style="flex: 1.5;">
                                            <div class="dr-main">
                                                <img src="<?= htmlspecialchars($b['BoostPhoto'] ?: 'https://ui-avatars.com/api/?name=B&background=EBE8FA') ?>" class="dr-avatar">
                                                <div>
                                                    <div class="dr-title"><?= htmlspecialchars($b['BoostName']) ?></div>
                                                    <div class="dr-sub">Target: <?= htmlspecialchars($b['BoostCity']) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dr-col">
                                            <div class="tag <?= $tagClass ?>">
                                                <i class="fas <?= $icon ?>" style="font-size: 8px;"></i> <?= $st ?>
                                            </div>
                                        </div>
                                        <div class="dr-col">
                                            <div class="dr-title" style="font-weight: 600;"><?= $b['BoostTimeDuration'] ?></div>
                                        </div>
                                        <div class="dr-col" style="text-align: right;">
                                            <div class="dr-title" style="color: var(--brand-purple);"><?= number_format($b['BoostPrice'], 2) ?> MAD</div>
                                        </div>
                                    </div>
                                <?php 
                                    $delayC += 0.05;
                                    endforeach; 
                                ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- CREATE BOOST MODAL -->
    <dialog id="boostModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Launch New Boost</h3>
                <button class="close-btn" onclick="window.boostModal.close()"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" style="display:flex; flex-direction:column; gap:20px;">
                <input type="hidden" name="action" value="add_boost">
                <input type="hidden" name="ContentID" id="contentID_Input">
                <input type="hidden" name="Duration" id="dur_Input" value="24h">
                <input type="hidden" name="BoostTypeID" id="type_Input" value="1">

                <div class="form-group">
                    <label>Internal Campaign Name (for your reference)</label>
                    <input type="text" name="BoostName" class="form-control" placeholder="e.g. Saturday Special Blitz" required>
                </div>

                <div class="form-group">
                    <label>Select Content to Promote</label>
                    <div class="content-picker">
                        <?php foreach($contentToBoost as $cnt): 
                             $pht = !empty($cnt['PostPhoto']) ? $cnt['PostPhoto'] : "https://ui-avatars.com/api/?name=R&background=111827&color=FFFFFF";
                        ?>
                            <div class="pick-item" onclick="selectBoostContent('<?= $cnt['PostId'] ?>', this)">
                                <div class="pick-check"><i class="fas fa-check"></i></div>
                                <img src="<?= htmlspecialchars($pht) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Select Your Reach Level</label>
                    <div class="tier-grid">
                        <div class="tier-card active" onclick="selectTier('24h', '1', this)">
                            <div class="t-name">Hyper Local</div>
                            <div class="t-price">50 MAD</div>
                            <div class="t-msg">24 Hours</div>
                        </div>
                        <div class="tier-card" onclick="selectTier('3d', '2', this)">
                            <div class="t-name">City Wide</div>
                            <div class="t-price">150 MAD</div>
                            <div class="t-msg">3 Days</div>
                        </div>
                        <div class="tier-card" onclick="selectTier('7d', '3', this)">
                            <div class="t-name">Regional</div>
                            <div class="t-price">300 MAD</div>
                            <div class="t-msg">1 Week</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-trigger">Start Boosting 🚀</button>
            </form>
        </div>
    </dialog>

    <script>
        function selectBoostContent(id, el) {
            document.getElementById('contentID_Input').value = id;
            document.querySelectorAll('.pick-item').forEach(x => x.classList.remove('selected'));
            el.classList.add('selected');
        }
        function selectTier(dur, type, el) {
            document.getElementById('dur_Input').value = dur;
            document.getElementById('type_Input').value = type;
            document.querySelectorAll('.tier-card').forEach(x => x.classList.remove('active'));
            el.classList.add('active');
        }

        window.onload = function() {
            // SPA Shimmer Handler
            setTimeout(() => {
                const sk = document.getElementById('skeleton-boosts');
                const rl = document.getElementById('real-boosts');
                if(sk && rl) {
                    sk.style.display = 'none';
                    rl.style.display = 'block';
                }
            }, 500);
        }
    </script>
</body>
</html>
