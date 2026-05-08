<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];
$shopNameEscaped = $con->real_escape_string($_SESSION['SellerName']);

// 1. Core Analytics
$prodQuery = $con->query("SELECT COUNT(*) as c FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID WHERE ShopsCategory.ShopID = $sellerID");
$totalProducts = ($prodQuery) ? $prodQuery->fetch_assoc()['c'] : 0;

$orderQuery = $con->query("SELECT COUNT(*) as c FROM Orders WHERE ShopID = $sellerID");
$totalOrders = ($orderQuery) ? $orderQuery->fetch_assoc()['c'] : 0;

$revQuery = $con->query("SELECT SUM(OrderPrice) as totalRev FROM Orders WHERE ShopID = $sellerID AND OrderState IN ('Done', 'Rated')");
$totalRevenue = ($revQuery && $revRow = $revQuery->fetch_assoc()) ? (float)$revRow['totalRev'] : 0;

$recentOrdersSql = "SELECT Orders.OrderID, Orders.OrderPrice, Orders.OrderState, Orders.CreatedAtOrders, Users.name as BuyerName, Users.UserPhoto as BuyerPhoto FROM Orders LEFT JOIN Users ON Orders.UserID = Users.UserID WHERE ShopID = $sellerID ORDER BY Orders.OrderID DESC LIMIT 5";
$recentOrders = [];
if ($roRes = $con->query($recentOrdersSql)) {
    while ($ro = $roRes->fetch_assoc()) {
        $recentOrders[] = $ro;
    }
}

// 7-day chart data
$chartData = [];
$chartLabels = [];
for ($i=6; $i>=0; $i--) {
    $dateObj = new DateTime("-$i days");
    $dString = $dateObj->format('Y-m-d');
    $chartLabels[] = $dateObj->format('M d');
    
    $dq = "SELECT SUM(OrderPrice) as dRev FROM Orders WHERE ShopID = $sellerID AND OrderState IN ('Done', 'Rated') AND DATE(CreatedAtOrders) = '$dString'";
    $dRes = $con->query($dq);
    $chartData[] = ($dRes && $dRow = $dRes->fetch_assoc()) ? (float)$dRow['dRev'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Hub | QOON Partner</title>
    <!-- Modern Typeface -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            /* Elegant / Soft Purple Theme Palette */
            --bg-master: #F3F5FA; 
            --bg-surface: #FFFFFF;
            
            --text-strong: #111827;
            --text-base: #374151;
            --text-muted: #9CA3AF;
            
            --brand-purple: #6B4EE6;
            --brand-purple-light: #EBE8FA;
            --brand-purple-grad: linear-gradient(135deg, #7C5CFF 0%, #5235E8 100%);
            
            --accent-pink: #F9E7F6;
            --accent-pink-text: #D63384;
            
            --accent-blue: #E8F4F8;
            --accent-blue-text: #0DCAF0;
            
            --danger-text: #EE5D50;
            
            --radius-lg: 24px;
            --radius-md: 16px;
            --radius-sm: 12px;
            
            --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.03);
            --shadow-hover: 0 15px 50px rgba(107, 78, 230, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            background-color: var(--bg-master); 
            color: var(--text-base); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
            -webkit-font-smoothing: antialiased; 
            font-family: 'Poppins', sans-serif;
            padding: 0;
        }

        .app-envelope { 
            width: 100%; height: 100%; 
            display: flex; 
            background: var(--bg-surface);
            overflow: hidden;
        }

        /* CSS Centralized in sidebar.php */

        /* ====== MAIN COMPONENT ====== */
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; overflow-x: hidden; background: #FAFAFB; border-radius: 0; }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; display: flex; flex-direction: column; gap: 30px; }
        
        /* Top Navigation Header */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; }
        
        .search-bar { 
            background: var(--bg-surface); border-radius: 30px; padding: 12px 24px; 
            display: flex; align-items: center; gap: 12px; width: 400px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }
        .search-bar input { border: none; outline: none; background: transparent; width: 100%; font-family: 'Inter', sans-serif; font-size: 13px; color: var(--text-strong); }
        .search-bar i { color: var(--text-muted); }
        
        .user-nav { display: flex; align-items: center; gap: 20px; }
        .nav-btn { width: 40px; height: 40px; border-radius: 50%; background: var(--bg-surface); display: flex; align-items: center; justify-content: center; color: var(--text-strong); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); cursor: pointer; transition: 0.2s;}
        .nav-btn:hover { background: var(--brand-purple-light); color: var(--brand-purple); }
        
        .profile-btn { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .profile-badge { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--bg-surface); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: var(--brand-purple-light); display: flex; align-items: center; justify-content: center; font-weight: 700; color: var(--brand-purple); }
        .profile-name { font-size: 14px; font-weight: 600; color: var(--text-strong); }

        /* Welcome Banner */
        .welcome-banner { 
            background: var(--brand-purple-grad); 
            border-radius: var(--radius-lg); padding: 40px; color: #FFF; 
            display: flex; align-items: center; justify-content: space-between; 
            position: relative; overflow: hidden; height: 200px;
            gap: 20px;
        }
        .wb-content { flex: 1; }
        .wb-subtitle { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; opacity: 0.8; }
        .wb-title { font-size: 24px; font-weight: 700; line-height: 1.2; max-width: 350px; }
        
        .live-orders-widget {
            background: rgba(255,255,255,0.1); border-radius: 20px; 
            padding: 15px 20px; width: 280px; height: 120px;
            backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden; position: relative;
        }
        .live-label { font-size: 10px; font-weight: 800; text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .live-dot { width: 6px; height: 6px; background: #4ADE80; border-radius: 50%; box-shadow: 0 0 10px #4ADE80; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }
        
        .ticker-wrap { height: 75px; overflow: hidden; position: relative; }
        .ticker-item { 
            display: flex; align-items: center; gap: 12px; margin-bottom: 10px;
            animation: tickerUp 8s infinite linear;
        }
        @keyframes tickerUp {
            0% { transform: translateY(0); }
            100% { transform: translateY(-200%); }
        }
        .ticker-avatar { width: 32px; height: 32px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(255,255,255,0.3); }
        .ticker-info div:first-child { font-size: 12px; font-weight: 700; }
        .ticker-info div:last-child { font-size: 10px; opacity: 0.7; }
        
        /* Star Decoration */
        .welcome-banner::after {
            content: '+'; position: absolute; right: 40px; top: -20px; 
            font-size: 180px; font-weight: 100; color: rgba(255,255,255,0.05); 
            line-height: 1; pointer-events: none; z-index: 0;
        }

        /* Metrics Row */
        .metrics-row { display: flex; gap: 20px; }
        .metric-pill { 
            background: var(--bg-surface); border-radius: var(--radius-lg); 
            padding: 16px 24px; flex: 1; display: flex; align-items: center; gap: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: 0.2s;
        }
        .metric-pill:hover { box-shadow: var(--shadow-hover); transform: translateY(-2px); }
        .mp-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .mp-icon.purple { background: var(--brand-purple-light); color: var(--brand-purple); }
        .mp-icon.pink { background: var(--accent-pink); color: var(--accent-pink-text); }
        .mp-icon.blue { background: var(--accent-blue); color: var(--accent-blue-text); }
        
        .mp-info { flex: 1; }
        .mp-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px; font-family: 'Inter', sans-serif;}
        .mp-value { font-size: 18px; font-weight: 700; color: var(--text-strong); }

        /* Main Content Layout */
        .content-layout { display: grid; grid-template-columns: 2.2fr 1fr; gap: 30px; }
        
        /* Left Column */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .s-title { font-size: 18px; font-weight: 600; color: var(--text-strong); }
        .s-nav { display: flex; gap: 8px; }
        .s-nav-btn { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-surface); display: flex; align-items: center; justify-content: center; font-size: 12px; color: var(--text-muted); box-shadow: 0 2px 4px rgba(0,0,0,0.02); border: none; cursor: pointer; }
        .s-nav-btn:hover { background: var(--brand-purple); color: #FFF; }

        .items-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .item-card { 
            background: var(--bg-surface); border-radius: var(--radius-md); 
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
            position: relative; overflow: hidden;
        }
        
        /* Data Table Stylings */
        .action-link { font-size: 13px; font-weight: 600; color: var(--brand-purple); text-decoration: none; }
        .table-wrap { background: var(--bg-surface); border-radius: var(--radius-md); padding: 20px 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        
        .data-row { display: flex; align-items: center; padding: 16px 0; border-bottom: 1px solid #F3F4F6; }
        .data-row:last-child { border-bottom: none; padding-bottom: 0; }
        .dr-col { flex: 1; font-family: 'Inter', sans-serif; }
        .dr-header { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; padding-bottom: 12px; border-bottom: 1px solid #F3F4F6; display: flex;}
        
        .dr-main { display: flex; align-items: center; gap: 12px; }
        .dr-avatar { width: 36px; height: 36px; border-radius: 50%; background: #F3F4F6; color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px;}
        .dr-title { font-size: 13px; font-weight: 600; color: var(--text-strong); }
        .dr-sub { font-size: 11px; color: var(--text-muted); margin-top: 2px;}
        
        .tag { font-size: 10px; font-weight: 600; padding: 4px 10px; border-radius: 20px; display: inline-block; }
        .tag-purple { background: var(--brand-purple-light); color: var(--brand-purple); }
        .tag-pink { background: var(--accent-pink); color: var(--accent-pink-text); }
        .tag-blue { background: var(--accent-blue); color: var(--accent-blue-text); }

        /* Right Column (Widget Stack) */
        .widget-stack { display: flex; flex-direction: column; gap: 30px; }
        
        .widget { background: var(--bg-surface); border-radius: var(--radius-md); padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .w-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .w-title { font-size: 16px; font-weight: 600; color: var(--text-strong); }
        
        /* Stats Chart Area */
        .chart-avatar-wrap { display: flex; justify-content: center; margin-bottom: 16px; position: relative;}
        .ring { width: 100px; height: 100px; border-radius: 50%; border: 3px solid #F3F4F6; border-top-color: var(--brand-purple); position: relative; display: flex; align-items: center; justify-content: center; }
        .ring img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        
        .greeting { text-align: center; margin-bottom: 24px; }
        .greeting h4 { font-size: 15px; font-weight: 600; color: var(--text-strong); }
        .greeting p { font-size: 11px; color: var(--text-muted); font-family: 'Inter', sans-serif;}

        .bar-chart-container { background: #F9FAFB; border-radius: 16px; padding: 20px; height: 180px; }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 16px; }
            .top-navbar { flex-direction: column; gap: 16px; align-items: flex-start; }
            .user-nav { width: 100%; justify-content: space-between; }
            .content-layout { grid-template-columns: 1fr; gap: 24px; }
            .metrics-row { flex-direction: column; gap: 16px; }
            .items-grid { grid-template-columns: 1fr; gap: 16px; }
            .welcome-banner { flex-direction: column; height: auto; padding: 24px; text-align: center; gap: 24px; }
            .live-orders-widget { width: 100%; }
            .table-wrap { overflow-x: visible; padding: 0; background: transparent; box-shadow: none; }
            .dr-header { display: none !important; }
            .data-row {
                display: grid;
                grid-template-columns: 1fr auto;
                grid-template-areas: 
                    "customer action"
                    "status price";
                gap: 16px;
                align-items: center;
                background: var(--bg-surface);
                padding: 18px;
                border-radius: 16px;
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
                border: 1px solid #F3F4F6;
                margin-bottom: 12px;
                min-width: unset;
                width: 100%;
            }
            .data-row .dr-col:nth-child(1) { grid-area: customer; }
            .data-row .dr-col:nth-child(2) { grid-area: status; }
            .data-row .dr-col:nth-child(3) { grid-area: price; text-align: left; }
            .data-row .dr-col:nth-child(4) { grid-area: action; text-align: right; }
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
        .skeleton-avatar { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
        .skeleton-title { height: 14px; width: 120px; border-radius: 4px; margin-bottom: 6px; }
        .skeleton-subtitle { height: 10px; width: 80px; border-radius: 4px; }
        .skeleton-badge { height: 24px; width: 80px; border-radius: 12px; }
        .skeleton-price { height: 18px; width: 60px; border-radius: 4px; }
        .skeleton-icon { height: 32px; width: 32px; border-radius: 50%; display: inline-block; }


    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <div class="content-wrapper">
                
                <header class="top-navbar">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search orders, products....">
                    </div>
                    
                    <div class="user-nav">
                        <div class="nav-btn"><i class="fas fa-envelope"></i></div>
                        <div class="nav-btn"><i class="fas fa-bell"></i></div>
                        <div class="profile-btn">
                            <?php if (!empty($SHOP_DATA['ShopLogo'])): ?>
                                <img src="<?= htmlspecialchars($SHOP_DATA['ShopLogo']) ?>" class="profile-badge" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['SellerName'] ?? 'S') ?>&background=EBE8FA&color=6B4EE6&bold=true';">
                            <?php else: ?>
                                <div class="profile-badge"><?= substr($_SESSION['SellerName'] ?? 'S', 0, 1) ?></div>
                            <?php endif; ?>
                            <div class="profile-name"><?= htmlspecialchars($_SESSION['SellerName']) ?></div>
                        </div>
                    </div>
                </header>

                <div class="content-layout">
                    <!-- Left Main Area -->
                    <div style="display: flex; flex-direction: column; gap: 30px;">
                        
                        <!-- Main Banner -->
                        <div class="welcome-banner">
                            <div class="wb-content">
                                <div class="wb-subtitle">Store Command Center</div>
                                <div class="wb-title">Track your performance and manage your inventory</div>
                            </div>

                            <div class="live-orders-widget">
                                <div class="live-label">
                                    <div class="live-dot"></div>
                                    Live Orders Feed
                                </div>
                                <div class="ticker-wrap" id="live-ticker-mount">
                                    <div style="font-size:11px; opacity:0.6;">Waiting for orders...</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Metrics -->
                        <div class="metrics-row">
                            <div class="metric-pill">
                                <div class="mp-icon purple"><i class="fas fa-wallet"></i></div>
                                <div class="mp-info">
                                    <div class="mp-label">Gross Revenue</div>
                                    <div class="mp-value"><?= number_format($totalRevenue, 2) ?> MAD</div>
                                </div>
                                <i class="fas fa-ellipsis-v" style="color: var(--text-muted);"></i>
                            </div>
                            
                            <div class="metric-pill">
                                <div class="mp-icon pink"><i class="fas fa-coins"></i></div>
                                <div class="mp-info">
                                    <div class="mp-label">Wallet Balance</div>
                                    <div class="mp-value"><?= number_format($SHOP_DATA['Balance'] ?? 0, 2) ?> MAD</div>
                                </div>
                                <i class="fas fa-ellipsis-v" style="color: var(--text-muted);"></i>
                            </div>
                            
                            <div class="metric-pill">
                                <div class="mp-icon blue"><i class="fas fa-shopping-cart"></i></div>
                                <div class="mp-info">
                                    <div class="mp-label">Total Orders</div>
                                    <div class="mp-value"><?= number_format($totalOrders) ?></div>
                                </div>
                                <i class="fas fa-ellipsis-v" style="color: var(--text-muted);"></i>
                            </div>
                        </div>

                        <!-- Data Table (Coming Orders) -->
                        <div>
                            <div class="section-header">
                                <div class="s-title">Coming Orders</div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div id="loader-dots" style="display:none; color:var(--brand-purple); font-size:12px;"><i class="fas fa-sync fa-spin"></i> Checking...</div>
                                    <a href="orders.php" class="action-link">See all</a>
                                </div>
                            </div>
                            
                            <div class="table-wrap" id="orders-container">
                                <!-- Skeleton Header -->
                                <div class="dr-header">
                                    <div class="dr-col" style="flex: 1.5;">CUSTOMER</div>
                                    <div class="dr-col">STATUS</div>
                                    <div class="dr-col">PRICE</div>
                                    <div class="dr-col" style="text-align: right;">ACTION</div>
                                </div>
                                
                                <!-- Skeleton Rows -->
                                <?php for($i=0; $i<4; $i++): ?>
                                <div class="data-row">
                                    <div class="dr-col" style="flex: 1.5;">
                                        <div class="dr-main">
                                            <div class="skeleton-avatar shimmer-bg"></div>
                                            <div>
                                                <div class="skeleton-title shimmer-bg"></div>
                                                <div class="skeleton-subtitle shimmer-bg"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="dr-col">
                                        <div class="skeleton-badge shimmer-bg"></div>
                                    </div>
                                    <div class="dr-col">
                                        <div class="skeleton-price shimmer-bg"></div>
                                    </div>
                                    <div class="dr-col" style="text-align: right;">
                                        <div class="skeleton-icon shimmer-bg"></div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                    </div>

                    <!-- Right Stack (Stats & Chart) -->
                    <div class="widget-stack">
                        <div class="widget" style="padding-bottom: 20px;">
                            <div class="w-header">
                                <div class="w-title">Statistic</div>
                                <i class="fas fa-ellipsis-v" style="color: var(--text-muted); font-size: 14px;"></i>
                            </div>
                            
                            <div class="chart-avatar-wrap">
                                <div class="ring">
                                    <?php if (!empty($SHOP_DATA['ShopLogo'])): ?>
                                        <img src="<?= htmlspecialchars($SHOP_DATA['ShopLogo']) ?>" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['SellerName'] ?? 'S') ?>&background=EBE8FA&color=6B4EE6&bold=true';">
                                    <?php else: ?>
                                        <div style="width:80px;height:80px;border-radius:50%;background:var(--brand-purple-light);color:var(--brand-purple);display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:700;"><?= substr($_SESSION['SellerName'] ?? 'S', 0, 1) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="greeting">
                                <h4>Good Morning <?= htmlspecialchars($_SESSION['SellerName']) ?> 🔥</h4>
                                <p>Continue managing your store to achieve your target!</p>
                            </div>
                            
                            <!-- Mini Vertical Bar Chart for Sales -->
                            <div class="bar-chart-container">
                                <canvas id="miniRevChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Mini Chart Logic
        const ctx = document.getElementById('miniRevChart').getContext('2d');
        const labels = <?= json_encode(array_reverse(array_slice(array_reverse($chartLabels), 0, 4))) ?>;
        const dataVals = <?= json_encode(array_reverse(array_slice(array_reverse($chartData), 0, 4))) ?>;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: dataVals,
                    backgroundColor: '#7C5CFF',
                    borderRadius: 8,
                    barThickness: 24,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#111827',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: { family: 'Poppins', size: 11 },
                        bodyFont: { family: 'Inter', size: 13, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#F3F4F6', drawBorder: false, borderDash: [5, 5] },
                        border: { display: false },
                        ticks: { font: { family: 'Inter', size: 10 }, color: '#9CA3AF', stepSize: 100 }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        border: { display: false },
                        ticks: { font: { family: 'Inter', size: 10 }, color: '#9CA3AF' }
                    }
                }
            }
        });

        // Dynamic Loading Logic
        async function fetchOrders() {
            const loader = document.getElementById('loader-dots');
            loader.style.display = 'block';
            
            try {
                const response = await fetch('get_recent_orders.php');
                const orders = await response.json();
                
                const container = document.getElementById('orders-container');
                const ticker = document.getElementById('live-ticker-mount');

                if (orders.length === 0) {
                    container.innerHTML = '<div style="padding: 40px; text-align: center; color: var(--text-muted); font-size: 13px;">No coming orders.</div>';
                    ticker.innerHTML = '<div style="font-size:11px; opacity:0.6;">No recent activity.</div>';
                } else {
                    // Populate Main Table
                    let html = `
                        <div class="dr-header">
                            <div class="dr-col" style="flex: 1.5;">CUSTOMER</div>
                            <div class="dr-col">STATUS</div>
                            <div class="dr-col">PRICE</div>
                            <div class="dr-col" style="text-align: right;">ACTION</div>
                        </div>
                    `;
                    
                    // Populate Ticker
                    let tickerHtml = '';

                    orders.forEach(ro => {
                        html += `
                            <div class="data-row animate-in">
                                <div class="dr-col" style="flex: 1.5;">
                                    <div class="dr-main">
                                        <img src="${ro.photo}" onerror="this.onerror=null;this.src='${ro.fallback}';" class="dr-avatar" style="object-fit: cover; border: 1px solid #F3F4F6;">
                                        <div>
                                            <div class="dr-title">${ro.customer}</div>
                                            <div class="dr-sub">${ro.date}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dr-col">
                                    <div class="tag ${ro.tag}"><i class="fas fa-circle" style="font-size: 6px; margin-right: 4px;"></i> ${ro.status}</div>
                                </div>
                                <div class="dr-col">
                                    <div class="dr-title" style="font-family: 'Poppins', sans-serif;">${ro.price}</div>
                                </div>
                                <div class="dr-col" style="text-align: right;">
                                    <a href="orders.php?q=${ro.id}" class="s-nav-btn" style="display:inline-flex; text-decoration:none;"><i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        `;

                        tickerHtml += `
                            <div class="ticker-item">
                                <img src="${ro.photo}" class="ticker-avatar">
                                <div class="ticker-info">
                                    <div>${ro.customer}</div>
                                    <div>${ro.price} • ${ro.status}</div>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    // Double the ticker content to ensure smooth loop
                    ticker.innerHTML = tickerHtml + tickerHtml;
                }
            } catch (err) {
                console.error("Fetch failed", err);
            } finally {
                setTimeout(() => { loader.style.display = 'none'; }, 500);
            }
        }

        // Initial load and interval
        fetchOrders();
        setInterval(fetchOrders, 30000); // Dynamic check every 30s
    </script>
    <style>
        .animate-in { animation: slideInX 0.4s ease-out; }
        @keyframes slideInX { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
        .tag-orange { background: #FFF4E5; color: #FF9800; }
        .tag-green { background: #E6FFFA; color: #319795; }
    </style>
</body>
</html>
