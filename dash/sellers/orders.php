<?php
require_once 'init.php';

$shopNameEscaped = $con->real_escape_string($_SESSION['SellerName']);
$stateFilter = isset($_GET['state']) ? $con->real_escape_string($_GET['state']) : 'All';
$sourceFilter = isset($_GET['source']) ? $con->real_escape_string($_GET['source']) : 'All';

$where = "ShopID = $sellerID";
if ($stateFilter !== 'All') {
    if ($stateFilter === 'Done') {
        $where .= " AND OrderState IN ('Done', 'Rated')";
    } else {
        $where .= " AND OrderState = '$stateFilter'";
    }
}

if ($sourceFilter === 'Web') {
    $where .= " AND OrderSource = 'WebStore'";
} elseif ($sourceFilter === 'App') {
    $where .= " AND OrderSource != 'WebStore'";
}

// Search Filter
$searchQuery = isset($_GET['q']) ? $con->real_escape_string($_GET['q']) : '';
if (!empty($searchQuery)) {
    $where .= " AND (Orders.OrderID LIKE '%$searchQuery%' 
                 OR Orders.UserName LIKE '%$searchQuery%' 
                 OR Users.name LIKE '%$searchQuery%' 
                 OR Drivers.FName LIKE '%$searchQuery%')";
}

// Fetch Orders with detailed info
$sql = "SELECT Orders.OrderID, Orders.CreatedAtOrders, Orders.OrderPrice, Orders.OrderState, 
               Orders.UserName as ManualBuyer, Orders.UserPhone as ManualPhone, Orders.OrderSource,
               Orders.UserLat, Orders.UserLongt, Orders.DelvryId, Orders.CancelLat, Orders.CancelLng, Orders.CancelPhoto,
               Orders.DeliveryLat, Orders.DeliveryLng, Orders.DeliveryPhoto,
               Users.name as BuyerName, Users.UserPhoto as BuyerPhoto, Users.PhoneNumber as DbPhone,
               Drivers.FName as DriverName, Drivers.PersonalPhoto as DriverPhoto, Drivers.DriverPhone,
               (SELECT COALESCE(SUM(od.Quantity * f.FoodPrice), 0) FROM OrderDetailsOrder od JOIN Foods f ON od.FoodID = f.FoodID WHERE od.OrderID = Orders.OrderID) AS RealProductPrice
        FROM Orders 
        LEFT JOIN Users ON Orders.UserID = Users.UserID 
        LEFT JOIN Drivers ON Orders.DelvryId = Drivers.DriverID 
        WHERE $where 
        ORDER BY Orders.OrderID DESC LIMIT 50";

$res = $con->query($sql);
$orders = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $orders[] = $row;
    }
}

// Get Commission Percentage
$percQuery = $con->query("SELECT percent FROM OrdersJiblerpercentage LIMIT 1");
$commPerc = ($percQuery && $pRow = $percQuery->fetch_assoc()) ? (float)$pRow['percent'] : 15.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | QOON Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-master: #FFFFFF; --bg-surface: #FFFFFF;
            --text-strong: #111827; --text-base: #4B5563; --text-muted: #9CA3AF;
            --brand-purple: #6366F1; --brand-purple-light: #EEF2FF;
            --accent-green: #10B981; --accent-green-bg: #ECFDF5;
            --radius-xl: 24px; --radius-md: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }
        body { background-color: var(--bg-master); color: var(--text-base); display: flex; height: 100vh; overflow: hidden; font-family: 'Inter', sans-serif; }
        .app-envelope { width: 100%; height: 100%; display: flex; background: var(--bg-surface); overflow: hidden; }

        .main-panel { flex: 1; display: flex; flex-direction: column; overflow-y: auto; background: #FFFFFF; }
        .content-wrapper { padding: 40px; max-width: 1400px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; gap: 40px; }
        
        .header-controls { display: flex; flex-direction: column; gap: 24px; }
        .search-area { display: flex; align-items: center; background: #F9FAFB; border-radius: 12px; border: 1px solid #E5E7EB; padding: 0 16px; transition: 0.2s; }
        .search-area:focus-within { background: #FFF; border-color: var(--brand-purple); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .search-area input { border: none; padding: 12px 8px; outline: none; font-size: 14px; width: 100%; font-family: inherit; background: transparent; }
        .search-area i { color: var(--text-muted); font-size: 14px; }
        .btn-go { background: var(--brand-purple); color: #FFF; border: none; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-go:hover { background: #4F46E5; }
        
        .section-header { margin-bottom: 8px; }
        .s-title { font-size: 32px; font-weight: 800; color: var(--text-strong); letter-spacing: -1px; font-family: 'Poppins', sans-serif; }
        .s-subtitle { font-size: 15px; color: var(--text-muted); margin-top: 6px; font-weight: 500; }

        .filter-nav { display: flex; gap: 8px; overflow-x: auto; padding: 4px; scrollbar-width: none; }
        .filter-nav::-webkit-scrollbar { display: none; }
        .filter-pill { padding: 8px 16px; border-radius: 10px; background: #F3F4F6; color: var(--text-base); font-weight: 600; font-size: 13px; text-decoration: none; transition: 0.2s; border: 1px solid transparent; white-space: nowrap;}
        .filter-pill:hover { background: #E5E7EB; }
        .filter-pill.active { background: var(--text-strong); color: #FFF; }

        /* Table Design */
        .card-table { background: #FFF; border-radius: var(--radius-md); border: 1px solid #E5E7EB; overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 16px 20px; color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 1px; background: #F9FAFB; border-bottom: 1px solid #E5E7EB; }
        td { padding: 20px; border-bottom: 1px solid #F3F4F6; color: var(--text-strong); font-size: 14px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #F9FAFB; }
        
        .buyer-stack, .driver-stack { display: flex; align-items: center; gap: 12px; }
        .avatar { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; background: #F3F4F6; }
        .name-info { display: flex; flex-direction: column; gap: 2px; }
        .name-info .n { font-weight: 700; font-size: 14px; color: var(--text-strong); }
        .name-info .sub { font-size: 12px; color: var(--text-muted); font-weight: 500; }

        /* Status Select & Modern Colors */
        .status-select { 
            padding: 6px 12px; border-radius: 8px; border: 1px solid transparent; 
            font-size: 12px; font-weight: 700; outline: none; 
            cursor: pointer; transition: 0.2s;
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 8px center; padding-right: 28px;
        }

        .st-waiting   { background-color: #FEF3C7; color: #92400E; }
        .st-accepted  { background-color: #DBEAFE; color: #1E40AF; }
        .st-preparing { background-color: #F3E8FF; color: #6B21A8; }
        .st-ready     { background-color: #DCFCE7; color: #166534; }
        .st-cancelled { background-color: #FEE2E2; color: #991B1B; }
        .st-returned  { background-color: #FFEDD5; color: #9A3412; }
        .st-doing     { background-color: #E0F2FE; color: #075985; }
        .st-done      { background-color: #D1FAE5; color: #065F46; }

        .price-val { font-weight: 700; font-family: 'Inter', sans-serif; color: var(--text-strong); }
        .price-qoon { color: #EF4444; font-size: 12px; font-weight: 600; opacity: 0.8; }
        .price-net { color: #059669; font-weight: 800; font-size: 15px; }

        .btn-details { background: #F3F4F6; color: var(--text-strong); border: none; padding: 8px 14px; border-radius: 8px; font-weight: 700; font-size: 12px; cursor: pointer; transition: 0.2s; }
        .btn-details:hover { background: #E5E7EB; }
        
        .tag { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 4px; }
        
        .mobile-only { display: none; }
        
        @media (max-width: 768px) {
            .content-wrapper { padding: 20px; gap: 30px; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 16px; }
            .s-title { font-size: 28px; }
            
            .desktop-only { display: none !important; }
            .mobile-only { display: flex; flex-direction: column; gap: 16px; }
            
            .m-order-card { background: #FFF; border-radius: var(--radius-md); padding: 20px; border: 1px solid #E5E7EB; display: flex; flex-direction: column; gap: 20px; }
            .moc-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #F3F4F6; padding-bottom: 16px; }
            .moc-id { font-weight: 800; font-size: 16px; color: var(--text-strong); display: flex; flex-direction: column; }
            .moc-date { font-size: 12px; color: var(--text-muted); font-weight: 500; margin-top: 2px; }
            
            .moc-users { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
            .mu-box { display: flex; flex-direction: column; gap: 8px; }
            .mu-box .label { font-size: 10px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; }
            .mu-box .user { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--text-strong); }
            .mu-box img { width: 24px; height: 24px; border-radius: 6px; }
            
            .moc-finances { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; padding: 16px; background: #F9FAFB; border-radius: 12px; }
            .moc-finances > div { display: flex; flex-direction: column; gap: 4px; }
            .moc-finances .l { font-size: 9px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
            .moc-finances .v { font-size: 14px; font-weight: 800; color: var(--text-strong); }
            
            .moc-actions { display: flex; flex-direction: column; gap: 12px; }
            .moc-actions .btn-details { width: 100%; padding: 12px; justify-content: center; font-size: 13px; }
        }

        .modern-badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-waiting { background: #FEF3C7; color: #92400E; }
        .badge-accepted { background: #DBEAFE; color: #1E40AF; }
        .badge-preparing { background: #F3E8FF; color: #6B21A8; }
        .badge-ready { background: #FCE7F3; color: #9D174D; }
        .badge-doing { background: #EDE9FE; color: #5B21B6; }
        .badge-arrived { background: #E0E7FF; color: #3730A3; }
        .badge-done { background: #D1FAE5; color: #065F46; }
        .badge-cancelled { background: #FEE2E2; color: #991B1B; }
        .badge-returned { background: #FFEDD5; color: #9A3412; }

        @keyframes shimmerEffect { 0% { background-position: -400px 0; } 100% { background-position: 400px 0; } }
        .shimmer-bg { background: #F3F4F6; background-image: linear-gradient(to right, #F3F4F6 0%, #E5E7EB 20%, #F3F4F6 40%, #F3F4F6 100%); background-repeat: no-repeat; background-size: 800px 100%; animation: shimmerEffect 1.5s infinite linear; }

        .fade-in-up { animation: fadeInUp 0.4s ease forwards; opacity: 0; transform: translateY(10px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <div class="content-wrapper">
                
                <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px;">
                    <div>
                        <div class="s-title">Order Command Center</div>
                        <div class="s-subtitle">Manage fulfillment, tracking, and financial splits.</div>
                    </div>
                    <div style="display: flex; background: #fff; padding: 5px; border-radius: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; gap: 5px;">
                        <a href="?state=<?= urlencode($stateFilter) ?>&source=All&q=<?= urlencode($searchQuery) ?>" 
                           style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; <?= $sourceFilter==='All' ? 'background: #f8fafc; color: var(--brand-purple);' : 'color: var(--text-muted);' ?>">
                            <i class="fas fa-layer-group"></i> All Channels
                        </a>
                        <a href="?state=<?= urlencode($stateFilter) ?>&source=App&q=<?= urlencode($searchQuery) ?>" 
                           style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; <?= $sourceFilter==='App' ? 'background: #eef2ff; color: #4338ca;' : 'color: var(--text-muted);' ?>">
                            <i class="fas fa-mobile-alt"></i> QOON App
                        </a>
                        <a href="?state=<?= urlencode($stateFilter) ?>&source=Web&q=<?= urlencode($searchQuery) ?>" 
                           style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: 0.2s; <?= $sourceFilter==='Web' ? 'background: #ecfdf5; color: #059669;' : 'color: var(--text-muted);' ?>">
                            <i class="fas fa-globe"></i> Web Store
                        </a>
                    </div>
                </div>

                <div class="header-controls" style="flex-direction: column; align-items: stretch; gap: 15px;">
                    <form class="search-area" method="GET" style="width: 100%; max-width: none;">
                        <input type="hidden" name="state" value="<?= htmlspecialchars($stateFilter) ?>">
                        <input type="hidden" name="source" value="<?= htmlspecialchars($sourceFilter) ?>">
                        <i class="fas fa-search"></i>
                        <input type="text" name="q" placeholder="Search by Order ID, Customer Name, or Driver..." value="<?= htmlspecialchars($searchQuery) ?>" style="font-size: 15px; padding: 15px 10px;">
                        <button type="submit" class="btn-go" style="width: 40px; height: 40px;"><i class="fas fa-arrow-right"></i></button>
                    </form>



                    <nav class="filter-nav">
                        <?php 
                            $filters = [
                                ['label'=>'All Statuses', 'val'=>'All'],
                                ['label'=>'New Requests', 'val'=>'waiting'],
                                ['label'=>'Preparing', 'val'=>'Preparing'],
                                ['label'=>'Ready to Pickup', 'val'=>'Ready'],
                                ['label'=>'On the Way', 'val'=>'Doing'],
                                ['label'=>'Completed', 'val'=>'Done'],
                                ['label'=>'Rejected', 'val'=>'Cancelled'],
                                ['label'=>'Returned', 'val'=>'Returned']
                            ];
                            foreach($filters as $f): 
                                $isActive = ($stateFilter === $f['val']) ? 'active' : '';
                        ?>
                            <a href="?state=<?= $f['val'] ?>&source=<?= urlencode($sourceFilter) ?>&q=<?= urlencode($searchQuery) ?>" class="filter-pill <?= $isActive ?>"><?= $f['label'] ?></a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <!-- Skeleton Dual-Layout Loader -->
                <div id="skeleton-orders">
                    <div class="card-table desktop-only">
                        <table>
                            <thead>
                                <tr>
                                    <th>Ref / Date</th>
                                    <th>Stakeholders</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Com. Qoon</th>
                                    <th>Net Income</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for($s=0; $s<6; $s++): ?>
                                <tr>
                                    <td>
                                        <div class="shimmer-bg" style="height:14px; width:60px; border-radius:4px; margin-bottom:6px;"></div>
                                        <div class="shimmer-bg" style="height:10px; width:80px; border-radius:4px;"></div>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:20px;">
                                            <div class="buyer-stack">
                                                <div class="shimmer-bg avatar"></div>
                                                <div class="name-info"><div class="shimmer-bg" style="height:12px; width:70px; border-radius:4px;"></div></div>
                                            </div>
                                            <div class="driver-stack">
                                                <div class="shimmer-bg avatar" style="width:32px; height:32px; border-radius:8px;"></div>
                                                <div class="name-info"><div class="shimmer-bg" style="height:12px; width:70px; border-radius:4px;"></div></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><div class="shimmer-bg" style="height:35px; width:130px; border-radius:10px;"></div></td>
                                    <td><div class="shimmer-bg" style="height:16px; width:50px; border-radius:4px;"></div></td>
                                    <td><div class="shimmer-bg" style="height:16px; width:40px; border-radius:4px;"></div></td>
                                    <td><div class="shimmer-bg" style="height:18px; width:60px; border-radius:4px;"></div></td>
                                    <td><div class="shimmer-bg" style="height:32px; width:90px; border-radius:10px;"></div></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mobile-only">
                        <?php for($s=0; $s<4; $s++): ?>
                        <div class="m-order-card">
                            <div class="moc-header">
                                <div class="shimmer-bg" style="height:16px; width:60px; border-radius:4px;"></div>
                                <div class="shimmer-bg" style="height:12px; width:80px; border-radius:4px;"></div>
                            </div>
                            <div class="moc-users">
                                <div class="mu-box" style="justify-content:flex-start;">
                                    <div class="shimmer-bg" style="width:28px; height:28px; border-radius:50%;"></div>
                                    <div class="shimmer-bg" style="height:12px; width:60px; border-radius:4px;"></div>
                                </div>
                                <div class="shimmer-bg" style="height:12px; width:12px; border-radius:50%;"></div>
                                <div class="mu-box" style="justify-content:flex-end;">
                                    <div class="shimmer-bg" style="height:12px; width:60px; border-radius:4px;"></div>
                                    <div class="shimmer-bg" style="width:28px; height:28px; border-radius:50%;"></div>
                                </div>
                            </div>
                            <div class="moc-finances">
                                <div class="shimmer-bg" style="height:40px; border-radius:10px;"></div>
                                <div class="shimmer-bg" style="height:40px; border-radius:10px;"></div>
                                <div class="shimmer-bg" style="height:40px; border-radius:10px;"></div>
                            </div>
                            <div class="moc-actions">
                                <div class="shimmer-bg" style="height:40px; flex:1; border-radius:10px;"></div>
                                <div class="shimmer-bg" style="height:40px; width:60px; border-radius:12px;"></div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Hidden Real Content -->
                <div id="real-orders" style="display: none;">
                    <div class="card-table desktop-only">
                        <table>
                            <thead>
                                <tr>
                                    <th>Ref / Date</th>
                                    <th>Stakeholders</th>
                                    <th>Status</th>
                                    <th>Price</th>
                                    <th>Com. Qoon</th>
                                    <th>Net Income</th>
                                    <th style="text-align: right; padding-right: 20px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $delayDesktop = 0;
                                foreach ($orders as $row): 
                                    $isWebStore = ($row['OrderSource'] === 'WebStore');
                                    $finalCommPerc = $isWebStore ? 0 : $commPerc;
                                    
                                    $price = (float)$row['RealProductPrice']; // Real products total
                                    if ($price <= 0) {
                                        $price = (float)$row['OrderPrice'];
                                    }
                                    $comm = $price * ($finalCommPerc / 100);
                                    $net = $price - $comm;
                                    
                                    $st = $row['OrderState'];
                                    if ($st === 'Cancelled' || $st === 'Returned') {
                                        $comm = 0;
                                        $net = 0;
                                    }
                                    $isDriverControlled = (!$isWebStore && in_array($st, ['Doing', 'Done', 'Rated', 'Arrived']));
                                    
                                    // Phone Logic
                                    $buyerPhone = trim($row['ManualPhone'] ?: $row['DbPhone']);
                                    $driverPhone = trim($row['DriverPhone']);
                                ?>
                                <tr class="fade-in-up" style="animation-delay: <?= $delayDesktop ?>s;">
                                    <td>
                                        <a href="order_detail.php?id=<?= $row['OrderID'] ?>" style="font-weight: 800; color: inherit; text-decoration: none;">#<?= $row['OrderID'] ?></a>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= date('M j, H:i', strtotime($row['CreatedAtOrders'])) ?></div>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 20px;">
                                            <!-- Customer -->
                                            <div class="buyer-stack">
                                                <?php 
                                                $bName = trim($row['ManualBuyer'] ?: ($row['BuyerName'] ?: 'Guest'));
                                                if ($isWebStore) $bName .= ' <span class="tag tag-green" style="padding: 2px 6px; font-size: 9px; margin-left: 4px;">WEB</span>';
                                                
                                                $bPhoto = $row['BuyerPhoto'];
                                                $fb = "https://ui-avatars.com/api/?name=".urlencode($bName)."&background=EBE8FA&color=6B4EE6&bold=true";
                                                ?>
                                                <img src="<?= $bPhoto ?: $fb ?>" class="avatar" onerror="this.src='<?= $fb ?>'">
                                                <div class="name-info">
                                                    <span class="n"><?= $bName ?></span>
                                                </div>
                                            </div>
                                            <!-- Driver -->
                                            <div class="driver-stack">
                                                <?php 
                                                $dName = $row['DriverName'];
                                                if (!$dName) {
                                                    $dName = in_array(strtolower(trim($st)), ['cancelled', 'canceled', 'returned']) ? 'Unassigned' : 'Waiting...';
                                                }
                                                $dPhoto = $row['DriverPhoto'];
                                                $fbd = "https://ui-avatars.com/api/?name=Driver&background=F1F5F9&color=64748B";
                                                ?>
                                                <img src="<?= $dPhoto ?: $fbd ?>" class="avatar" style="width: 32px; height: 32px; border-radius: 8px;" onerror="this.src='<?= $fbd ?>'">
                                                <div class="name-info">
                                                    <span class="n" style="font-size: 12px;"><?= $dName ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $stLower = strtolower(trim($st));
                                        $badgeText = 'Unknown';
                                        $badgeClass = 'badge-waiting';
                                        if(in_array($stLower, ['waiting', 'placed'])) { $badgeText = 'Placed'; $badgeClass = 'badge-waiting'; }
                                        elseif(in_array($stLower, ['accepted', 'confirmed', 'yes'])) { $badgeText = 'Confirmed'; $badgeClass = 'badge-accepted'; }
                                        elseif(in_array($stLower, ['preparing', 'processed', 'order processed'])) { $badgeText = 'Processed'; $badgeClass = 'badge-preparing'; }
                                        elseif(in_array($stLower, ['ready', 'pickup', 'picked', 'picked up'])) { $badgeText = 'Ready'; $badgeClass = 'badge-ready'; }
                                        elseif(in_array($stLower, ['doing', 'on way', 'on the way', 'found', 'come to take it'])) { $badgeText = 'On Way'; $badgeClass = 'badge-doing'; }
                                        elseif(in_array($stLower, ['arrived'])) { $badgeText = 'Arrived'; $badgeClass = 'badge-arrived'; }
                                        elseif(in_array($stLower, ['done', 'finish', 'delivered', 'order delivered', 'rated'])) { $badgeText = 'Delivered'; $badgeClass = 'badge-done'; }
                                        elseif(in_array($stLower, ['cancelled', 'canceled'])) { $badgeText = 'Cancelled'; $badgeClass = 'badge-cancelled'; }
                                        elseif(in_array($stLower, ['returned'])) { $badgeText = 'Returned'; $badgeClass = 'badge-returned'; }
                                        ?>
                                        <span class="modern-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                    </td>
                                    <td><div class="price-val"><?= number_format($price, 2) ?></div></td>
                                    <td><div class="price-qoon">-<?= number_format($comm, 2) ?></div></td>
                                    <td><div class="price-net"><?= number_format($net, 2) ?> <span style="font-size: 10px;">MAD</span></div></td>
                                    <td style="text-align: right; white-space: nowrap; padding-right: 20px;">
                                         <?php if($st === 'Returned'): ?>
                                            <button class="btn-details" style="background:#f3e8ff; color:#7e22ce; margin-right:5px; border:none; cursor:pointer; font-size: 11px; padding: 6px 10px;" 
                                                onclick="openProofModal('<?= $row['OrderID'] ?>', '<?= $row['DelvryId'] ?>', '<?= $row['UserLat'] ?>', '<?= $row['UserLongt'] ?>', '<?= htmlspecialchars($row['DriverName']??'') ?>', '<?= htmlspecialchars($row['BuyerName']??'') ?>', '<?= htmlspecialchars($row['CancelLat']??'') ?>', '<?= htmlspecialchars($row['CancelLng']??'') ?>', '<?= htmlspecialchars($row['CancelPhoto']??'') ?>', '<?= htmlspecialchars($row['DeliveryLat']??'') ?>', '<?= htmlspecialchars($row['DeliveryLng']??'') ?>', '<?= htmlspecialchars($row['DeliveryPhoto']??'') ?>', '<?= $st ?>')">
                                                <i class="fas fa-shield-alt"></i> Proof
                                            </button>
                                         <?php endif; ?>
                                         <?php if(!in_array(strtolower($st), ['cancelled','canceled','done','rated','returned','delivered'])): ?>
                                         <button class="btn-details" style="background:#FEE2E2; color:#991B1B; margin-right:5px; border:none; cursor:pointer; font-size: 11px; padding: 6px 10px;" 
                                             onclick="cancelOrder('<?= $row['OrderID'] ?>')"
                                             title="Cancel this order">
                                             <i class="fas fa-times-circle"></i> Cancel
                                         </button>
                                         <?php endif; ?>
                                         <a href="order_detail.php?id=<?= $row['OrderID'] ?>" class="btn-details" style="display:inline-flex; text-decoration:none;">View Details</a>
                                    </td>
                                </tr>
                                <?php 
                                    $delayDesktop += 0.05;
                                    endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mobile-only">
                        <?php 
                        $delayMobile = 0;
                        foreach ($orders as $row): 
                            $isWebStore = ($row['OrderSource'] === 'WebStore');
                            $finalCommPerc = $isWebStore ? 0 : $commPerc;
                            
                            $price = (float)$row['RealProductPrice']; // Real products total
                            if ($price <= 0) {
                                $price = (float)$row['OrderPrice'];
                            }
                            $comm = $price * ($finalCommPerc / 100);
                            $net = $price - $comm;
                            $st = $row['OrderState'];
                            
                            if ($st === 'Cancelled' || $st === 'Returned') {
                                $comm = 0;
                                $net = 0;
                            }
                            $isDriverControlled = (!$isWebStore && in_array($st, ['Doing', 'Done', 'Rated', 'Arrived']));
                            
                            $bNameRaw = trim($row['ManualBuyer'] ?: ($row['BuyerName'] ?: 'Guest'));
                            $bName = $isWebStore ? $bNameRaw . ' [WEB]' : $bNameRaw;
                            
                            $fb = "https://ui-avatars.com/api/?name=".urlencode($bNameRaw)."&background=EBE8FA&color=6B4EE6&bold=true";
                            $bPhoto = $row['BuyerPhoto'] ?: $fb;

                            $dName = $row['DriverName'];
                            if (!$dName) {
                                $dName = in_array(strtolower(trim($st)), ['cancelled', 'canceled', 'returned']) ? 'Unassigned' : 'Waiting...';
                            }
                            $fbd = "https://ui-avatars.com/api/?name=Driver&background=F1F5F9&color=64748B";
                            $dPhoto = $row['DriverPhoto'] ?: $fbd;
                        ?>
                        <div class="m-order-card fade-in-up" style="animation-delay: <?= $delayMobile ?>s;">
                            <div class="moc-header">
                                <a href="order_detail.php?id=<?= $row['OrderID'] ?>" class="moc-id" style="color: inherit; text-decoration: none;">#<?= $row['OrderID'] ?></a>
                                <div class="moc-date"><?= date('M j, H:i', strtotime($row['CreatedAtOrders'])) ?></div>
                            </div>
                            
                            <div class="moc-users">
                                <div class="mu-box" style="justify-content: flex-start;">
                                    <img src="<?= $bPhoto ?>" onerror="this.src='<?= $fb ?>'">
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= $bName ?></span>
                                </div>
                                <i class="fas fa-arrow-right" style="color: #CBD5E1; font-size: 12px; margin: 0 4px;"></i>
                                <div class="mu-box" style="justify-content: flex-end;">
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-align: right;"><?= $dName ?></span>
                                    <img src="<?= $dPhoto ?>" onerror="this.src='<?= $fbd ?>'">
                                </div>
                            </div>

                            <div class="moc-finances">
                                <div><span class="l">Price</span> <span class="v"><?= number_format($price, 2) ?></span></div>
                                <div><span class="l">Fee</span> <span class="v" style="color:#EF4444;">-<?= number_format($comm, 2) ?></span></div>
                                <div><span class="l">Net (MAD)</span> <span class="v" style="color:#10B981; font-weight:800;"><?= number_format($net, 2) ?></span></div>
                            </div>

                            <div class="moc-actions" style="display:flex; align-items:center; justify-content:flex-end; gap:10px;">
                                        <?php
                                        $stLower = strtolower(trim($st));
                                        $badgeText = 'Unknown';
                                        $badgeClass = 'badge-waiting';
                                        if(in_array($stLower, ['waiting', 'placed'])) { $badgeText = 'Placed'; $badgeClass = 'badge-waiting'; }
                                        elseif(in_array($stLower, ['accepted', 'confirmed', 'yes'])) { $badgeText = 'Confirmed'; $badgeClass = 'badge-accepted'; }
                                        elseif(in_array($stLower, ['preparing', 'processed', 'order processed'])) { $badgeText = 'Processed'; $badgeClass = 'badge-preparing'; }
                                        elseif(in_array($stLower, ['ready', 'pickup', 'picked', 'picked up'])) { $badgeText = 'Ready'; $badgeClass = 'badge-ready'; }
                                        elseif(in_array($stLower, ['doing', 'on way', 'on the way', 'found', 'come to take it'])) { $badgeText = 'On Way'; $badgeClass = 'badge-doing'; }
                                        elseif(in_array($stLower, ['arrived'])) { $badgeText = 'Arrived'; $badgeClass = 'badge-arrived'; }
                                        elseif(in_array($stLower, ['done', 'finish', 'delivered', 'order delivered', 'rated'])) { $badgeText = 'Delivered'; $badgeClass = 'badge-done'; }
                                        elseif(in_array($stLower, ['cancelled', 'canceled'])) { $badgeText = 'Cancelled'; $badgeClass = 'badge-cancelled'; }
                                        elseif(in_array($stLower, ['returned'])) { $badgeText = 'Returned'; $badgeClass = 'badge-returned'; }
                                        ?>
                                        <span class="modern-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                                         <?php if($st === 'Returned'): ?>
                                            <button class="btn-details" style="background:#f3e8ff; color:#7e22ce; padding: 10px; border-radius: 12px; border:none; cursor:pointer;" 
                                                onclick="openProofModal('<?= $row['OrderID'] ?>', '<?= $row['DelvryId'] ?>', '<?= $row['UserLat'] ?>', '<?= $row['UserLongt'] ?>', '<?= htmlspecialchars($row['DriverName']??'') ?>', '<?= htmlspecialchars($row['BuyerName']??'') ?>', '<?= htmlspecialchars($row['CancelLat']??'') ?>', '<?= htmlspecialchars($row['CancelLng']??'') ?>', '<?= htmlspecialchars($row['CancelPhoto']??'') ?>', '<?= htmlspecialchars($row['DeliveryLat']??'') ?>', '<?= htmlspecialchars($row['DeliveryLng']??'') ?>', '<?= htmlspecialchars($row['DeliveryPhoto']??'') ?>', '<?= $st ?>')">
                                                <i class="fas fa-shield-alt"></i> Proof
                                            </button>
                                         <?php endif; ?>
                                         <?php if(!in_array(strtolower($st), ['cancelled','canceled','done','rated','returned','delivered'])): ?>
                                         <button class="btn-details" style="background:#FEE2E2; color:#991B1B; padding: 10px; border-radius: 12px; border:none; cursor:pointer;" 
                                             onclick="cancelOrder('<?= $row['OrderID'] ?>')">
                                             <i class="fas fa-times-circle"></i> Cancel
                                         </button>
                                         <?php endif; ?>
                                         <a href="order_detail.php?id=<?= $row['OrderID'] ?>" class="btn-details" style="padding: 10px 20px; border-radius: 12px; display:inline-flex; text-decoration:none; text-align:center; flex:1;">Manage Order</a>
                            </div>
                        </div>
                        <?php 
                            $delayMobile += 0.05;
                            endforeach; 
                        ?>
                    </div>
                </div>

            </div>
        </main>
    </div>



    <div id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); z-index:9999; align-items:center; justify-content:center;">
        <div style="background:#FFF; padding:30px; border-radius:24px; width:90%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.2); animation: fadeInUp 0.3s ease;">
            <div style="width:60px; height:60px; border-radius:50%; background:#F3E8FF; color:var(--brand-purple); display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">
                <i class="fas fa-sync-alt"></i>
            </div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px;">Update Order Status</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:25px; line-height:1.5;">Are you sure you want to change this order status? This action will immediately notify the customer.</p>
            <div style="display:flex; gap:10px;">
                <button id="btnConfirmCancel" style="flex:1; padding:12px; border-radius:12px; border:none; background:#F1F5F9; color:var(--text-strong); font-weight:700; cursor:pointer;">Cancel</button>
                <button id="btnConfirmAction" style="flex:1; padding:12px; border-radius:12px; border:none; background:var(--brand-purple); color:#FFF; font-weight:700; cursor:pointer;">Yes, Update</button>
            </div>
        </div>
    </div>

    <div id="pinModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:#FFF; padding:30px; border-radius:24px; width:90%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.2); animation: fadeInUp 0.3s ease;">
            <div style="width:60px; height:60px; border-radius:50%; background:#FEF3C7; color:#D97706; display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">
                <i class="fas fa-key"></i>
            </div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px;">Driver Security PIN</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px; line-height:1.5;">Please ask the driver for their 4-digit security code to confirm pickup.</p>
            <input type="text" id="driverPinInput" maxlength="4" style="width:100%; text-align:center; font-size:32px; letter-spacing:10px; padding:15px; border-radius:16px; border:2px solid #E2E8F0; margin-bottom:25px; outline:none; font-weight:800; color:var(--brand-purple);" placeholder="••••" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            <div style="display:flex; gap:10px;">
                <button id="btnPinCancel" style="flex:1; padding:12px; border-radius:12px; border:none; background:#F1F5F9; color:var(--text-strong); font-weight:700; cursor:pointer;">Cancel</button>
                <button id="btnPinConfirm" style="flex:1; padding:12px; border-radius:12px; border:none; background:var(--brand-purple); color:#FFF; font-weight:700; cursor:pointer;">Verify</button>
            </div>
        </div>
    </div>

    <div id="errorModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); z-index:10001; align-items:center; justify-content:center;">
        <div style="background:#FFF; padding:30px; border-radius:24px; width:90%; max-width:400px; text-align:center; box-shadow:0 20px 40px rgba(0,0,0,0.2); animation: fadeInUp 0.3s ease;">
            <div style="width:60px; height:60px; border-radius:50%; background:#FEF2F2; color:#EF4444; display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">
                <i class="fas fa-times-circle"></i>
            </div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px;">Action Failed</h3>
            <p id="errorModalMsg" style="color:var(--text-muted); font-size:14px; margin-bottom:25px; line-height:1.5;"></p>
            <button onclick="document.getElementById('errorModal').style.display='none'" style="width:100%; padding:12px; border-radius:12px; border:none; background:#EF4444; color:#FFF; font-weight:700; cursor:pointer;">Close</button>
        </div>
    </div>

    <div id="cancelModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); backdrop-filter:blur(5px); z-index:10000; align-items:center; justify-content:center;">
        <div style="background:#FFF; padding:30px; border-radius:24px; width:90%; max-width:400px; box-shadow:0 20px 40px rgba(0,0,0,0.2); animation: fadeInUp 0.3s ease;">
            <div style="width:60px; height:60px; border-radius:50%; background:#FEE2E2; color:#DC2626; display:flex; align-items:center; justify-content:center; font-size:24px; margin:0 auto 20px;">
                <i class="fas fa-ban"></i>
            </div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px; text-align:center;">Cancel Order</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px; line-height:1.5; text-align:center;">Please specify why you are cancelling this order. This reason will be sent to the customer.</p>
            
            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:25px;" id="cancelReasonsList">
                <label style="padding:15px; border:1px solid #E2E8F0; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <input type="radio" name="cancelReason" value="Item out of stock">
                    <span style="font-weight:600;">Item out of stock</span>
                </label>
                <label style="padding:15px; border:1px solid #E2E8F0; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <input type="radio" name="cancelReason" value="Store is closing soon">
                    <span style="font-weight:600;">Store is closing soon</span>
                </label>
                <label style="padding:15px; border:1px solid #E2E8F0; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <input type="radio" name="cancelReason" value="Too busy to fulfill">
                    <span style="font-weight:600;">Too busy to fulfill</span>
                </label>
                <label style="padding:15px; border:1px solid #E2E8F0; border-radius:12px; cursor:pointer; display:flex; align-items:center; gap:10px;">
                    <input type="radio" name="cancelReason" value="Other">
                    <span style="font-weight:600;">Other</span>
                </label>
            </div>

            <div style="display:flex; gap:10px;">
                <button id="btnCancelCancel" style="flex:1; padding:12px; border-radius:12px; border:none; background:#F1F5F9; color:var(--text-strong); font-weight:700; cursor:pointer;">Go Back</button>
                <button id="btnCancelConfirm" style="flex:1; padding:12px; border-radius:12px; border:none; background:#EF4444; color:#FFF; font-weight:700; cursor:pointer;">Cancel Order</button>
            </div>
        </div>
    </div>

    <!-- External Assets -->
    <script src='https://cdn.firebase.com/js/client/2.2.1/firebase.js'></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA1DPGIuuuJKZMXlK_ehSH07-5Ab2ab9-8&v=weekly"></script>

    <style>
        .logistics-card { background: #F8FAFC; border-radius: 12px; padding: 15px; margin-bottom: 20px; display: flex; flex-direction: column; gap: 8px; border-left: 4px solid var(--brand-purple); }
        .log-row { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-base); }
        .log-row i { color: var(--brand-purple); width: 14px; text-align: center; }
        .btn-group-chat { background: #25D366; color: #FFF; border: none; padding: 12px; border-radius: 12px; font-weight: 700; width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; text-decoration: none; cursor: pointer; }
    </style>

    <script>
        let currentMap, currentMarker, firebaseRef;


        
        function showErrorModal(msg) {
            document.getElementById('errorModalMsg').innerText = msg;
            document.getElementById('errorModal').style.display = 'flex';
        }

        function showModernConfirm() {
            return new Promise((resolve) => {
                const modal = document.getElementById('confirmModal');
                modal.style.display = 'flex';
                
                document.getElementById('btnConfirmCancel').onclick = () => {
                    modal.style.display = 'none';
                    resolve(false);
                };
                
                document.getElementById('btnConfirmAction').onclick = () => {
                    modal.style.display = 'none';
                    resolve(true);
                };
            });
        }

        function showPinPrompt() {
            return new Promise((resolve) => {
                const modal = document.getElementById('pinModal');
                const input = document.getElementById('driverPinInput');
                input.value = '';
                modal.style.display = 'flex';
                input.focus();
                
                document.getElementById('btnPinCancel').onclick = () => {
                    modal.style.display = 'none';
                    resolve(null);
                };
                
                document.getElementById('btnPinConfirm').onclick = () => {
                    const val = input.value.trim();
                    if(val.length > 0) {
                        modal.style.display = 'none';
                        resolve(val);
                    } else {
                        alert("Please enter a valid PIN.");
                    }
                };
            });
        }

        function showCancelPrompt() {
            return new Promise((resolve) => {
                const modal = document.getElementById('cancelModal');
                const radios = document.querySelectorAll('input[name="cancelReason"]');
                radios.forEach(r => r.checked = false);
                modal.style.display = 'flex';
                
                document.getElementById('btnCancelCancel').onclick = () => {
                    modal.style.display = 'none';
                    resolve(null);
                };
                
                document.getElementById('btnCancelConfirm').onclick = () => {
                    let selected = null;
                    radios.forEach(r => { if(r.checked) selected = r.value; });
                    if(selected) {
                        modal.style.display = 'none';
                        resolve(selected);
                    } else {
                        showErrorModal("Please select a cancellation reason.");
                    }
                };
            });
        }

        async function cancelOrder(orderId) {
            const reason = await showCancelPrompt();
            if (reason === null) return;
            
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', 'Cancelled');
            formData.append('cancel_reason', reason);

            try {
                const res = await fetch('api_orders.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    location.reload();
                } else {
                    showErrorModal(data.message || 'Could not cancel order.');
                }
            } catch (err) {
                showErrorModal('Network error. Please try again.');
            }
        }

        async function handleSelectChange(selectEl, orderId) {
            const originalVal = selectEl.getAttribute('data-original');
            const newVal = selectEl.value;
            applyStatusColor(selectEl); // temp apply

            const success = await updateStatus(orderId, newVal);
            if (!success) {
                // Revert to original if cancelled or failed
                selectEl.value = originalVal;
                applyStatusColor(selectEl);
            }
        }

        async function updateStatus(orderId, newStatus, suppliedPin = null) {
            let driverPin = suppliedPin;
            let cancelReason = null;

            if (newStatus === 'Ready' || newStatus === 'Returned') {
                if (!driverPin) {
                    driverPin = await showPinPrompt();
                }
                if (driverPin === null) {
                    return false;
                }
            } else if (newStatus === 'Cancelled') {
                cancelReason = await showCancelPrompt();
                if (cancelReason === null) {
                    return false;
                }
            } else {
                const isConfirmed = await showModernConfirm();
                if (!isConfirmed) {
                    return false;
                }
            }
            
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus);
            if (driverPin) formData.append('pin', driverPin);
            if (cancelReason) formData.append('cancel_reason', cancelReason);

            try {
                const res = await fetch('api_orders.php?action=update_status', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.status === 'success') {
                    // Success toast or just reload
                    location.reload(); 
                    return true;
                } else {
                    showErrorModal(data.message);
                    return false;
                }
            } catch (err) {
                showErrorModal("Network error updating status.");
                return false;
            }
        }

        function applyStatusColor(select) {
            // Remove old classes
            select.classList.remove('st-waiting', 'st-accepted', 'st-preparing', 'st-ready', 'st-cancelled');
            // Add new class
            select.classList.add('st-' + select.value.toLowerCase());
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.status-select').forEach(applyStatusColor);
            
            // SPA Shimmer Transition
            setTimeout(() => {
                const skeleton = document.getElementById('skeleton-orders');
                const real = document.getElementById('real-orders');
                if(skeleton && real) {
                    skeleton.style.display = 'none';
                    real.style.display = 'block';
                }
                
                <?php if (!empty($searchQuery) && count($orders) === 1): ?>
                // Fix: Instead of opening the popup, redirect directly to the order details page
                window.location.href = 'order_detail.php?id=<?= $orders[0]['OrderID'] ?>';
                <?php endif; ?>
            }, 500); 
        });


    </script>
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <style>
        .proof-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); backdrop-filter: blur(5px);
            z-index: 9999; display: none; align-items: center; justify-content: center;
        }
        .proof-modal-overlay.active { display: flex; }
        .proof-modal-box {
            background: #fff; width: 90%; max-width: 600px; border-radius: 16px;
            overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: fadeInScaleProof 0.3s ease;
        }
        .proof-modal-header {
            padding: 20px; background: #fafafa; border-bottom: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center;
        }
        .proof-modal-title { font-weight: 800; font-size: 18px; color: #111; }
        .proof-close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #666; }
        .proof-modal-body { padding: 20px; max-height: 70vh; overflow-y: auto; }
        .proof-img-container { margin-bottom: 20px; text-align: center; background: #f9f9f9; border-radius: 12px; padding: 10px; }
        .proof-img-container img { max-width: 100%; border-radius: 8px; max-height: 300px; object-fit: cover; }
        .proof-map-container { height: 250px; border-radius: 12px; overflow: hidden; border: 1px solid #ddd; background: #e5e5e5; }
        
        @keyframes fadeInScaleProof { from{ opacity:0; transform:scale(0.95); } to{ opacity:1; transform:scale(1); } }
    </style>

    <!-- Proof Modal -->
    <div class="proof-modal-overlay" id="proofModal">
        <div class="proof-modal-box">
            <div class="proof-modal-header">
                <div class="proof-modal-title"><i class="fas fa-camera"></i> Delivery/Return Proof</div>
                <button class="proof-close-btn" onclick="document.getElementById('proofModal').classList.remove('active')"><i class="fas fa-times"></i></button>
            </div>
            <div class="proof-modal-body">
                <div class="proof-img-container" id="proofImgWrapper">
                    <i class="fas fa-spinner fa-spin" style="color: #ccc; font-size: 30px; padding: 20px;"></i>
                    <div style="font-size:12px; color:#888; margin-top:5px;">Scanning communications for image...</div>
                </div>
                <div style="font-weight:700; margin-bottom:8px; font-size:14px; color:#444;">
                    <i class="fas fa-map-marker-alt"></i> Location Telemetry
                </div>
                <div class="proof-map-container" id="proofMap"></div>
            </div>
            <div id="proofModalFooter" style="padding: 15px 20px; background: #fdfdfd; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; display: none;">
                <button id="btnVerifyReturn" class="btn-details" style="background: #2cb5e8; color: #fff; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i> Verify Return & Accept PIN
                </button>
            </div>
        </div>
    </div>

    <script>
    let proofMapInstance = null;

    function openProofModal(orderId, driverId, userLat, userLng, driverName, userName, dbCancelLat, dbCancelLng, dbCancelPhoto, dbDeliveryLat, dbDeliveryLng, dbDeliveryPhoto, currentStatus) {
        console.log("Proof Modal Data:", {orderId, driverId, userLat, userLng, driverName, userName, dbCancelLat, dbCancelLng, dbCancelPhoto, dbDeliveryLat, dbDeliveryLng, dbDeliveryPhoto, currentStatus});
        const modal = document.getElementById('proofModal');
        const footer = document.getElementById('proofModalFooter');
        const verifyBtn = document.getElementById('btnVerifyReturn');

        if(currentStatus === 'Cancelled') {
            footer.style.display = 'flex';
            verifyBtn.onclick = async () => {
                const pin = await showPinPrompt();
                if(pin) {
                    const success = await updateStatus(orderId, 'Returned', pin);
                    if(success) {
                        modal.classList.remove('active');
                        location.reload();
                    }
                }
            };
        } else {
            footer.style.display = 'none';
        }
        modal.classList.add('active');
        const imgWrapper = document.getElementById('proofImgWrapper');
        
        // Reset state
        imgWrapper.innerHTML = `<i class="fas fa-spinner fa-spin" style="color: #ccc; font-size: 30px; padding: 20px;"></i><div style="font-size:12px; color:#888; margin-top:5px;">Scanning communications for image...</div>`;
        
        // 1. Fetch data from MySQL first, then fallback to Firebase OrderTrackers
        let primaryPhoto = (dbCancelPhoto && dbCancelPhoto.trim() !== '') ? dbCancelPhoto : ((dbDeliveryPhoto && dbDeliveryPhoto.trim() !== '') ? dbDeliveryPhoto : null);
        let primaryLat = (dbCancelLat && dbCancelLat !== '0' && dbCancelLat !== '') ? parseFloat(dbCancelLat) : ((dbDeliveryLat && dbDeliveryLat !== '0' && dbDeliveryLat !== '') ? parseFloat(dbDeliveryLat) : null);
        let primaryLng = (dbCancelLng && dbCancelLng !== '0' && dbCancelLng !== '') ? parseFloat(dbCancelLng) : ((dbDeliveryLng && dbDeliveryLng !== '0' && dbDeliveryLng !== '') ? parseFloat(dbDeliveryLng) : null);

        fetch(`https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/${orderId}.json`)
        .then(r => r.json())
        .then(trackerData => {
            // IMAGE HANDLING
            if(primaryPhoto) {
                let captureType = dbCancelPhoto ? "Cancellation" : "Delivery";
                imgWrapper.innerHTML = `<img src="${primaryPhoto}" alt="Proof Image">
                                        <div style="font-size:11px; color:#666; margin-top:6px;">Captured by Driver at ${captureType} (Database)</div>`;
            } else if(trackerData && trackerData.cancel_photo) {
                imgWrapper.innerHTML = `<img src="${trackerData.cancel_photo}" alt="Proof Image">
                                        <div style="font-size:11px; color:#666; margin-top:6px;">Captured by Driver at Cancellation (Live)</div>`;
            } else {
                // Fallback: Fetch Chat Image from Firebase
                fetch(`https://jibler-37339-default-rtdb.firebaseio.com/Messages/${orderId}.json`)
                    .then(r => r.json())
                    .then(data => {
                        let proofImgUrl = null;
                        if(data) {
                            const msgs = Object.values(data);
                            for(let i = msgs.length - 1; i >= 0; i--) {
                                let m = msgs[i];
                                let isImage = (m.MessageType === 'Image' || (m.message && m.message.includes('http') && (m.message.includes('.png') || m.message.includes('.jpg') || m.message.includes('.jpeg'))));
                                if(isImage && m.sender && m.sender.toLowerCase() === 'driver') {
                                    proofImgUrl = m.message;
                                    break;
                                }
                            }
                        }
                        if(proofImgUrl) {
                            imgWrapper.innerHTML = `<img src="${proofImgUrl}" alt="Proof Image">
                                                    <div style="font-size:11px; color:#666; margin-top:6px;">Captured by Driver (from chat)</div>`;
                        } else {
                            imgWrapper.innerHTML = `<i class="fas fa-image" style="color: #eee; font-size: 40px; padding: 20px;"></i>
                                                    <div style="font-size:12px; color:#888;">No visual proof submitted by driver.</div>`;
                        }
                    }).catch(() => {
                        imgWrapper.innerHTML = `<div style="font-size:12px; color:#d9534f;">Failed to retrieve visual proof data.</div>`;
                    });
            }

            // LOCATION HANDLING
            let cLat = parseFloat(userLat);
            let cLng = parseFloat(userLng);
            if(isNaN(cLat) || isNaN(cLng) || (cLat === 0 && cLng === 0)) {
                cLat = 33.5731; cLng = -7.5898;
            }

            setTimeout(() => {
                if(!proofMapInstance) {
                    proofMapInstance = L.map('proofMap', { zoomControl: false }).setView([cLat, cLng], 15);
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(proofMapInstance);
                } else {
                    proofMapInstance.off();
                    proofMapInstance.remove();
                    proofMapInstance = L.map('proofMap', { zoomControl: false }).setView([cLat, cLng], 15);
                    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(proofMapInstance);
                }
                
                const iconUser = L.divIcon({ className: 'custom-icon', html: `<div style="background:#623cea; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; font-size: 14px;"><i class="fas fa-home"></i></div>`, iconSize: [30,30], iconAnchor: [15,30] });
                L.marker([cLat, cLng], {icon: iconUser}).bindPopup(`<b>User Location:</b> ${userName}`).addTo(proofMapInstance);

                if(driverId && driverId != '0') {
                    // Check if we have cancellation coordinates
                    let hasCancelCoords = false;
                    let dLat = null; let dLng = null;
                    if(primaryLat !== null && primaryLng !== null) {
                        dLat = primaryLat; dLng = primaryLng;
                        hasCancelCoords = true;
                    } else if(trackerData && trackerData.cancel_lat && trackerData.cancel_lng && trackerData.cancel_lat != 0) {
                        dLat = parseFloat(trackerData.cancel_lat);
                        dLng = parseFloat(trackerData.cancel_lng);
                        hasCancelCoords = true;
                    }

                    if(hasCancelCoords) {
                        let markerColor = dbCancelLat ? "#ef4444" : "#10b981";
                        let markerIcon = dbCancelLat ? "fa-times" : "fa-check";
                        let markerLabel = dbCancelLat ? "Cancelled Here" : "Delivered Here";
                        
                        const iconDriver = L.divIcon({ className: 'custom-icon', html: `<div style="background:${markerColor}; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; font-size: 14px;"><i class="fas ${markerIcon}"></i></div>`, iconSize: [30,30], iconAnchor: [15,30] });
                        L.marker([dLat, dLng], {icon: iconDriver}).bindPopup(`<b>${markerLabel}:</b> ${driverName}<br>Exact Proof Coordinates`).addTo(proofMapInstance);
                        
                        const bounds = L.latLngBounds([[cLat, cLng], [dLat, dLng]]);
                        proofMapInstance.fitBounds(bounds, { padding: [30, 30] });
                    } else {
                        // Fallback to live location
                        fetch(`https://jibler-37339-default-rtdb.firebaseio.com/Location/${driverId}.json`)
                        .then(r => r.json())
                        .then(loc => {
                            let dLat = cLat; let dLng = cLng;
                            let hasDriverLoc = false;
                            if(loc && (loc.lat || loc.latitude)) {
                                dLat = parseFloat(loc.lat || loc.latitude);
                                dLng = parseFloat(loc.lng || loc.longitude);
                                hasDriverLoc = true;
                            }
        
                            if(hasDriverLoc && !isNaN(dLat) && !isNaN(dLng)) {
                                const iconDriver = L.divIcon({ className: 'custom-icon', html: `<div style="background:#10b981; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; font-size: 14px;"><i class="fas fa-motorcycle"></i></div>`, iconSize: [30,30], iconAnchor: [15,30] });
                                L.marker([dLat, dLng], {icon: iconDriver}).bindPopup(`<b>Driver:</b> ${driverName}<br>Last Known Position`).addTo(proofMapInstance);
                                
                                const bounds = L.latLngBounds([[cLat, cLng], [dLat, dLng]]);
                                proofMapInstance.fitBounds(bounds, { padding: [30, 30] });
                            }
                        });
                    }
                }
            }, 300);
        });
    }
    </script>
</body>
</html>
