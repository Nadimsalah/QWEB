<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];

// 1. Fetch Transactions from ShopLastTransaction (Sales, Withdrawals)
$txRes = $con->query("SELECT *, 'System' as txSource FROM ShopLastTransaction WHERE ShopID='$sellerID' ORDER BY CreatedAtShopLastTransaction DESC LIMIT 50");
$txs = [];
if($txRes) { while($r = $txRes->fetch_assoc()) { $txs[] = $r; } }

// 2. Fetch Advertising Spend from BoostsByShop
$boostRes = $con->query("SELECT BoostName, BoostPrice, BoostStatus, CreatedAtBoostsByShop as txDate, 'Boost' as txSource, BoostsByShopID as txID FROM BoostsByShop WHERE ShopID='$sellerID' ORDER BY CreatedAtBoostsByShop DESC LIMIT 50");
if($boostRes) {
    while($b = $boostRes->fetch_assoc()) {
        $txs[] = [
            'TransactionName' => "Ad Boost: " . $b['BoostName'],
            'Money' => "-" . $b['BoostPrice'], // Outgoing
            'TransactionStatus' => $b['BoostStatus'],
            'CreatedAtShopLastTransaction' => $b['txDate'],
            'txSource' => 'Boost',
            'OrderID' => '#' . $b['txID']
        ];
    }
}

// 3. Fetch Payout Requests from RequestPay
$reqRes = $con->query("SELECT *, CreatedAtRequestPay as txDate, 'Request' as txSource, RequestPayID as txID FROM RequestPay WHERE ShopID='$sellerID' ORDER BY CreatedAtRequestPay DESC LIMIT 50");
if($reqRes) {
    while($r = $reqRes->fetch_assoc()) {
        $txs[] = [
            'TransactionName' => "Payout Request",
            'Money' => "-" . $r['Money'],
            'TransactionStatus' => $r['RequestPayStatues'],
            'CreatedAtShopLastTransaction' => $r['txDate'],
            'txSource' => 'Request',
            'OrderID' => '#' . $r['txID']
        ];
    }
}

// Sort and Filter Logic
$typeFilter = $_GET['type'] ?? 'All';
$searchQuery = $_GET['q'] ?? '';

// Sort all transactions by date initially
usort($txs, function($a, $b) {
    return strtotime($b['CreatedAtShopLastTransaction']) - strtotime($a['CreatedAtShopLastTransaction']);
});

// Apply Filters in memory
$filteredTxs = [];
foreach($txs as $t) {
    $val = (float)$t['Money'];
    $isMatch = true;
    
    // Type Filter
    if($typeFilter !== 'All') {
        if($typeFilter == 'Sales' && !($t['txSource'] == 'System' && $val > 0)) $isMatch = false;
        if($typeFilter == 'Withdrawals' && !($t['txSource'] == 'System' && $val < 0)) $isMatch = false;
        if($typeFilter == 'Ads' && $t['txSource'] !== 'Boost') $isMatch = false;
        if($typeFilter == 'Payouts' && $t['txSource'] !== 'Request') $isMatch = false;
    }
    
    // Search Filter
    if($isMatch && !empty($searchQuery)) {
        if(stripos($t['TransactionName'], $searchQuery) === false && stripos($t['OrderID'], $searchQuery) === false) {
            $isMatch = false;
        }
    }
    
    if($isMatch) $filteredTxs[] = $t;
}
$txs = $filteredTxs;

// Analytics (based on original data for consistency in totals, or based on filtered? User usually wants overall totals in the banner, so let's keep it overall)
$overviewEarned = 0; $overviewPaid = 0; $overviewAds = 0;
// Fetching all again for overview stats (simple way)
$allTxsRes = $con->query("SELECT Money, 'System' as txSource FROM ShopLastTransaction WHERE ShopID='$sellerID'");
if($allTxsRes) {
    while($r = $allTxsRes->fetch_assoc()) {
        $v = (float)$r['Money'];
        if($v > 0) $overviewEarned += $v; else $overviewPaid += abs($v);
    }
}
$allBoostsRes = $con->query("SELECT BoostPrice FROM BoostsByShop WHERE ShopID='$sellerID'");
if($allBoostsRes) {
    while($r = $allBoostsRes->fetch_assoc()) { $overviewAds += (float)$r['BoostPrice']; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet | QOON Partner</title>
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

        .top-navbar { display: flex; justify-content: space-between; align-items: center; }
        .user-nav { display: flex; align-items: center; gap: 20px; }
        .profile-btn { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .profile-badge { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-surface); }

        /* WALLET BANNER */
        .wallet-banner { 
            background: var(--brand-purple-grad); border-radius: var(--radius-lg); padding: 40px; color: #FFF; 
            display: flex; justify-content: space-between; align-items: center; position: relative; overflow: hidden;
        }
        .wb-info { position: relative; z-index: 2; }
        .wb-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; margin-bottom: 8px; }
        .wb-balance { font-size: 36px; font-weight: 800; }
        .wb-btn { position: relative; z-index: 10; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); padding: 12px 24px; border-radius: 30px; color: #FFF; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.3s; backdrop-filter: blur(10px); }
        .wb-btn:hover { background: #FFF; color: var(--brand-purple); transform: translateY(-2px); }

        .metrics-row { display: flex; gap: 20px; }
        .metric-pill { background: var(--bg-surface); border-radius: var(--radius-lg); flex: 1; padding: 18px 24px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .mp-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .mp-icon.purple { background: var(--brand-purple-light); color: var(--brand-purple); }
        .mp-icon.blue { background: #E8F4F8; color: #0DCAF0; }
        .mp-icon.pink { background: #FCE7F3; color: #DB2777; }
        .mp-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; }
        .mp-value { font-size: 17px; font-weight: 700; color: var(--text-strong); }

        /* LEDGER TABLE */
        .table-wrap { background: var(--bg-surface); border-radius: var(--radius-lg); padding: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .dr-header { display: flex; padding: 20px; border-bottom: 1px solid #F3F4F6; }
        .dr-col { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; flex: 1; }
        .data-row { display: flex; align-items: center; padding: 16px 20px; border-bottom: 1px solid #F3F4F6; transition: 0.2s; }
        .data-row:hover { background: #FAFAFB; }
        .data-row:last-child { border-bottom: none; }
        .dr-main { display: flex; align-items: center; gap: 12px; }
        .dr-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .dr-icon.in { background: var(--tag-green); color: var(--tag-green-text); }
        .dr-icon.out { background: var(--tag-pink); color: var(--tag-pink-text); }
        
        .dr-title { font-size: 14px; font-weight: 700; color: var(--text-strong); }
        .dr-sub { font-size: 11px; color: var(--text-muted); }
        
        .tag { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .tag-green { background: var(--tag-green); color: var(--tag-green-text); }
        .tag-orange { background: var(--tag-orange); color: var(--tag-orange-text); }
        .tag-blue { background: var(--tag-blue); color: var(--tag-blue-text); }
        .tag-pink { background: var(--tag-pink); color: var(--tag-pink-text); }
        
        .amt-pos { color: var(--tag-green-text); font-weight: 800; font-size: 15px; }
        .amt-neg { color: var(--tag-pink-text); font-weight: 800; font-size: 15px; }

        /* Filter UI */
        .filter-bar { display: flex; gap: 15px; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-group { display: flex; gap: 8px; background: #FFF; padding: 6px; border-radius: 14px; border: 1px solid #F1F5F9; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .filter-link { padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .filter-link.active { background: var(--brand-purple); color: #FFF; box-shadow: 0 4px 12px rgba(107, 78, 230, 0.25); }
        .search-box { flex: 1; display: flex; align-items: center; background: #FFF; border-radius: 14px; border: 1px solid #F1F5F9; padding: 0 15px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); min-width: 250px; }
        .search-box input { border: none; padding: 12px 10px; outline: none; font-size: 13px; width: 100%; font-family: inherit; }
        .btn-search { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 14px; }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; }
            .top-navbar { flex-direction: column; gap: 16px; align-items: flex-start; margin-bottom: 20px;}
            .user-nav { width: 100%; justify-content: space-between; }
            .wallet-banner { flex-direction: column; align-items: flex-start; gap: 24px; padding: 24px; }
            .wb-btn { width: 100%; text-align: center; }
            .metrics-row { flex-direction: column; gap: 16px; }
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
            
            .dr-main { display: block; }
            .dr-icon { width: 48px; height: 48px; }
            
            .filter-bar { flex-direction: column; align-items: stretch; gap: 12px;}
            .filter-group { overflow-x: auto; -webkit-overflow-scrolling: touch; justify-content: flex-start;}
            .filter-link { white-space: nowrap; flex-shrink: 0; }
            .modal-card { width: calc(100% - 32px) !important; margin: 16px; padding: 20px !important; }
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

                <header class="top-navbar">
                    <div style="font-size: 20px; font-weight: 700; color: var(--text-strong);">Financial Hub</div>
                    <div class="user-nav">
                        <div class="profile-btn">
                            <img src="<?= !empty($SHOP_DATA['ShopLogo']) ? htmlspecialchars($SHOP_DATA['ShopLogo']) : "https://ui-avatars.com/api/?name=".urlencode($_SESSION['SellerName'])."&background=EBE8FA&color=6B4EE6&bold=true" ?>" class="profile-badge">
                            <div class="profile-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                        </div>
                    </div>
                </header>

                <!-- SPA SHIMMER SKELETON -->
                <div id="skeleton-wallet">
                    <!-- Banner Skeleton -->
                    <div class="wallet-banner" style="margin-bottom:30px; border:none; background:#FFF; box-shadow:0 4px 6px -1px rgba(0,0,0,0.02);">
                        <div class="wb-info" style="width:100%;">
                            <div class="shimmer-bg" style="height:12px; width:150px; border-radius:4px; margin-bottom:12px;"></div>
                            <div class="shimmer-bg" style="height:36px; width:200px; border-radius:6px;"></div>
                        </div>
                        <div class="shimmer-bg" style="width:180px; height:44px; border-radius:30px; position:relative; z-index:10;"></div>
                    </div>

                    <!-- Metrics Pilled Row Skeleton -->
                    <div class="metrics-row" style="margin-bottom:30px;">
                        <?php for($i=0; $i<3; $i++): ?>
                        <div class="metric-pill" style="border:none;">
                            <div class="shimmer-bg" style="width:44px; height:44px; border-radius:12px;"></div>
                            <div style="flex:1;">
                                <div class="shimmer-bg" style="height:11px; width:50%; border-radius:4px; margin-bottom:6px;"></div>
                                <div class="shimmer-bg" style="height:18px; width:30%; border-radius:4px;"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Ledger Table Skeleton -->
                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                            <div class="shimmer-bg" style="height:18px; width:20%; border-radius:4px;"></div>
                            <div class="shimmer-bg" style="height:12px; width:10%; border-radius:4px;"></div>
                        </div>
                        <div class="shimmer-bg" style="height:38px; width:100%; border-radius:14px; margin-bottom:25px;"></div>

                        <div class="table-wrap">
                            <div class="dr-header">
                                <div class="dr-col" style="flex: 2;"><div class="shimmer-bg" style="height:12px; width:40%; border-radius:4px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px;"></div></div>
                                <div class="dr-col" style="text-align: right;"><div class="shimmer-bg" style="height:12px; width:50%; border-radius:4px; display:inline-block;"></div></div>
                            </div>
                            <?php for($s=0; $s<4; $s++): ?>
                            <div class="data-row" style="border:none;">
                                <div class="dr-col" style="flex: 2;">
                                    <div class="dr-main">
                                        <div class="shimmer-bg dr-icon"></div>
                                        <div style="width:100%;">
                                            <div class="shimmer-bg" style="height:14px; width:60%; border-radius:4px; margin-bottom:4px;"></div>
                                            <div class="shimmer-bg" style="height:11px; width:40%; border-radius:4px;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:20px; width:50%; border-radius:10px;"></div></div>
                                <div class="dr-col"><div class="shimmer-bg" style="height:12px; width:60%; border-radius:4px;"></div></div>
                                <div class="dr-col" style="text-align: right;"><div class="shimmer-bg" style="height:15px; width:40%; border-radius:4px; display:inline-block;"></div></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- REAL CONTENT HIDDEN INITIALLY -->
                <div id="real-wallet" style="display:none;">
                    <div class="wallet-banner fade-in-up" style="animation-delay: 0s;">
                        <div class="wb-info">
                            <div class="wb-label">Available Balance <span style="font-size: 8px; opacity: 0.5;">LIVE v2.1</span></div>
                            <div class="wb-balance"><?= number_format($SHOP_DATA['Balance'] ?? 0, 2) ?> <span style="font-size: 20px; opacity: 0.8;">MAD</span></div>
                        </div>
                        <button class="wb-btn" onclick="openPayoutModal()"><i class="fas fa-hand-holding-usd"></i> Launch Payout Request</button>
                        <!-- Background Decoration -->
                        <div style="position: absolute; right: -20px; bottom: -40px; font-size: 200px; color: rgba(255,255,255,0.05); transform: rotate(-15deg); pointer-events: none;"><i class="fas fa-wallet"></i></div>
                    </div>

                    <div class="metrics-row">
                        <div class="metric-pill fade-in-up" style="animation-delay: 0.1s;">
                            <div class="mp-icon purple"><i class="fas fa-arrow-up"></i></div>
                            <div class="mp-info">
                                <div class="mp-label">Total Earned</div>
                                <div class="mp-value"><?= number_format($overviewEarned, 2) ?> MAD</div>
                            </div>
                        </div>
                        <div class="metric-pill fade-in-up" style="animation-delay: 0.15s;">
                            <div class="mp-icon blue"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="mp-info">
                                <div class="mp-label">Total Cashed Out</div>
                                <div class="mp-value"><?= number_format($overviewPaid, 2) ?> MAD</div>
                            </div>
                        </div>
                        <div class="metric-pill fade-in-up" style="animation-delay: 0.2s;">
                            <div class="mp-icon pink"><i class="fas fa-rocket"></i></div>
                            <div class="mp-info">
                                <div class="mp-label">Ad Spending</div>
                                <div class="mp-value"><?= number_format($overviewAds, 2) ?> MAD</div>
                            </div>
                        </div>
                    </div>

                    <div class="fade-in-up" style="animation-delay: 0.3s;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                            <div style="font-size: 18px; font-weight: 700; color: var(--text-strong);">Transaction Ledger</div>
                            <div style="color: var(--text-muted); font-size: 12px; font-weight: 500;">History of activities</div>
                        </div>

                        <form class="filter-bar" method="GET">
                            <div class="filter-group">
                                <?php 
                                    $types = ['All', 'Sales', 'Withdrawals', 'Ads', 'Payouts'];
                                    foreach($types as $t): 
                                        $active = ($typeFilter == $t) ? 'active' : '';
                                ?>
                                    <a href="?type=<?= $t ?>&q=<?= urlencode($searchQuery) ?>" class="filter-link <?= $active ?>"><?= $t ?></a>
                                <?php endforeach; ?>
                            </div>
                            <div class="search-box">
                                <input type="hidden" name="type" value="<?= htmlspecialchars($typeFilter) ?>">
                                <i class="fas fa-search" style="color: var(--text-muted); font-size: 13px;"></i>
                                <input type="text" name="q" placeholder="Search by name or Order ID..." value="<?= htmlspecialchars($searchQuery) ?>">
                                <button type="submit" class="btn-search"><i class="fas fa-arrow-right"></i></button>
                            </div>
                        </form>

                        <div class="table-wrap">
                            <div class="dr-header">
                                <div class="dr-col" style="flex: 2;">TRANSACTION DETAILS</div>
                                <div class="dr-col">STATUS</div>
                                <div class="dr-col">DATE</div>
                                <div class="dr-col" style="text-align: right;">AMOUNT</div>
                            </div>

                            <?php if (empty($txs)): ?>
                                <div style="padding: 60px; text-align: center; color: var(--text-muted);">
                                    <i class="fas fa-receipt" style="font-size: 40px; margin-bottom: 20px; opacity: 0.2;"></i>
                                    <div style="font-size: 14px; font-weight: 500;">No financial movements logged yet.</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($txs as $t): 
                                    $amt = (float)$t['Money'];
                                    $isIn = ($amt >= 0);
                                    $st = strtoupper($t['TransactionStatus'] ?? 'COMPLETED');
                                    $tagClass = ($st == 'COMPLETED' || $st == 'ACTIVE' || $st == 'DONE') ? 'tag-green' : (($st == 'PENDING' || $st == 'INREVIEW') ? 'tag-orange' : 'tag-pink');
                                ?>
                                    <div class="data-row">
                                        <div class="dr-col" style="flex: 2;">
                                            <div class="dr-main">
                                                <div class="dr-icon <?= $isIn ? 'in' : 'out' ?>">
                                                    <i class="fas <?= $isIn ? 'fa-plus' : 'fa-minus' ?>"></i>
                                                </div>
                                                <div>
                                                    <?php 
                                                        $title = htmlspecialchars($t['TransactionName']);
                                                        // If title is just a number and it's a positive system transaction, it's likely a sale
                                                        if(is_numeric($title) && $t['txSource'] == 'System' && $isIn) {
                                                            $title = "Product Sale";
                                                        }
                                                    ?>
                                                    <div class="dr-title"><?= $title ?></div>
                                                    <div class="dr-sub"><?= (strpos($t['OrderID'], '#') === false ? 'Order #' : '') . htmlspecialchars($t['OrderID']) ?> • <?= $t['txSource'] == 'Boost' ? 'Advertising' : 'Shop System' ?></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="dr-col">
                                            <div class="tag <?= $tagClass ?>"><?= $st ?></div>
                                        </div>
                                        <div class="dr-col">
                                            <div class="dr-sub" style="font-weight: 600; color: var(--text-base);"><?= date('M d, Y', strtotime($t['CreatedAtShopLastTransaction'])) ?></div>
                                        </div>
                                        <div class="dr-col" style="text-align: right;">
                                            <div class="<?= $isIn ? 'amt-pos' : 'amt-neg' ?>">
                                                <?= ($isIn ? '+' : '') . number_format($amt, 2) ?> MAD
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <!-- Payout Modal -->
    <div class="modal-overlay" id="payoutModal" onclick="closePayoutModal(event)">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div class="close-modal" onclick="closePayoutModal(event)"><i class="fas fa-times"></i></div>
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 20px; font-weight: 800; color: var(--text-strong);">Withdraw Funds</h3>
                <p style="font-size: 13px; color: var(--text-muted);">Funds will be sent to your registered bank account.</p>
            </div>
            
            <div style="background: var(--brand-purple-light); padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <div style="font-size: 11px; font-weight: 700; color: var(--brand-purple); text-transform: uppercase;">Maximum allowed</div>
                <div style="font-size: 18px; font-weight: 800; color: var(--brand-purple);"><?= number_format($SHOP_DATA['Balance'] ?? 0, 2) ?> MAD</div>
            </div>

            <div class="form-group" style="display: flex; flex-direction: column; gap: 8px;">
                <label style="font-size: 12px; font-weight: 700;">Withdrawal Amount (MAD)</label>
                <input type="number" id="payoutAmount" placeholder="Enter amount..." style="width: 100%; padding: 14px; border-radius: 12px; border: 1px solid #F1F5F9; background: #FAFAFB; outline: none; font-weight: 700;">
            </div>

            <button class="btn-primary" onclick="submitPayout()" id="btnSubmitPayout">Submit Request</button>
        </div>
    </div>

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(8px); z-index: 1000; display: none; align-items: center; justify-content: center; }
        .modal-card { width: 100%; max-width: 400px; background: #FFF; border-radius: 24px; padding: 30px; position: relative; box-shadow: 0 40px 100px rgba(0,0,0,0.2); }
        .close-modal { position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-muted); }
        .btn-primary { 
            width: 100%; background: var(--brand-purple-grad); color: #FFF; border: none; padding: 15px; 
            border-radius: 14px; margin-top: 25px; font-weight: 700; cursor: pointer; transition: 0.3s;
            box-shadow: 0 10px 20px rgba(107, 78, 230, 0.2);
        }
        .btn-primary:active { transform: scale(0.98); }
        
        /* Modern Toast */
        .toast-box {
            position: fixed; top: 30px; right: 30px; z-index: 9999;
            background: #111827; color: #FFF; padding: 16px 24px; border-radius: 16px;
            display: flex; align-items: center; gap: 15px; font-weight: 600; font-size: 14px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2); 
            transform: translateY(-100px); opacity: 0; transition: 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        .toast-box.show { transform: translateY(0); opacity: 1; }
        .toast-icon { width: 24px; height: 24px; border-radius: 50%; background: var(--tag-green-text); color: #FFF; display: flex; align-items: center; justify-content: center; font-size: 12px; }
    </style>

    <div class="toast-box" id="appToast">
        <div class="toast-icon"><i class="fas fa-check"></i></div>
        <span id="toastMsg">Action completed!</span>
    </div>

    <script>
        function showToast(msg, isError = false) {
            const toast = document.getElementById('appToast');
            document.getElementById('toastMsg').innerText = msg;
            toast.querySelector('.toast-icon').style.background = isError ? '#EF4444' : '#059669';
            toast.querySelector('.toast-icon i').className = isError ? 'fas fa-times' : 'fas fa-check';
            toast.classList.add('show');
            setTimeout(() => { toast.classList.remove('show'); }, 3000);
        }

        function openPayoutModal() { document.getElementById('payoutModal').style.display = 'flex'; }
        function closePayoutModal(e) { document.getElementById('payoutModal').style.display = 'none'; }

        async function submitPayout() {
            const amount = document.getElementById('payoutAmount').value;
            const btn = document.getElementById('btnSubmitPayout');
            
            if (!amount || amount <= 0) { showToast("Please enter a valid amount.", true); return; }
            
            btn.disabled = true;
            btn.innerText = 'Processing...';

            const formData = new FormData();
            formData.append('amount', amount);

            try {
                const res = await fetch('api_wallet.php?action=request_payout', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    showToast(data.message);
                    setTimeout(() => { location.reload(); }, 1500);
                } else {
                    showToast(data.message, true);
                    btn.disabled = false;
                    btn.innerText = 'Submit Request';
                }
            } catch (err) {
                showToast("Network error occurred.", true);
                btn.disabled = false;
                btn.innerText = 'Submit Request';
            }
        }

        window.onload = function() {
            // SPA Shimmer Handler
            setTimeout(() => {
                const sk = document.getElementById('skeleton-wallet');
                const rl = document.getElementById('real-wallet');
                if(sk && rl) {
                    sk.style.display = 'none';
                    rl.style.display = 'block';
                }
            }, 500);
        }
    </script>
</body>
</html>
