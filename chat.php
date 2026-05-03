<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);
$userId = $_COOKIE['qoon_user_id'] ?? '';
$isIframe = isset($_GET['iframe']) && $_GET['iframe'] == 1;
$authRequired = false;

if (!$userId) {
    if ($isIframe) {
        $authRequired = true;
    } else {
        header("Location: index.php?auth_required=1");
        exit;
    }
}

$domain = $DomainNamee ?? 'https://qoon.app/dash/';

// Helper for images
function fullUrl($path, $domain) {
    if (!$path || $path === '0' || $path === 'NONE') return '';
    if (strpos($path, 'http') !== false) return preg_replace('#(?<!:)//+#', '/', $path);
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}

// 1. Fetch Orders to show as Shop/Driver Chats
$orderChats = [];
if ($con && !$authRequired) {
    $q = "SELECT 
                o.OrderID, o.OrderState, o.CreatedAtOrders,
                o.OrderPriceFromShop, o.OrderPrice, o.PlatformFee, o.DelvryId,
                COALESCE(s.ShopName, o.DestinationName, 'QOON Order') AS ShopName,
                s.ShopLogo,
                TRIM(CONCAT(COALESCE(d.FName,''), ' ', LEFT(COALESCE(d.LName,''),1))) AS DriverName,
                d.PersonalPhoto AS DriverPhoto
          FROM Orders o
          LEFT JOIN Shops s ON s.ShopID = o.ShopID
          LEFT JOIN Drivers d ON d.DriverID = o.DelvryId
          WHERE o.UserID = '" . $con->real_escape_string($userId) . "'
          ORDER BY o.OrderID DESC LIMIT 30";
    $res = $con->query($q);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $orderChats[] = $row;
        }
    }
}

// 2. Handle Friend Search (flexible phone match)
$searchedFriend = null;
$searchPhone = $_GET['phone'] ?? '';
if ($con && $searchPhone !== '') {
    // Clean the input: remove spaces, dashes, parentheses
    $cleanSearch = preg_replace('/[\s\-\(\)]+/', '', $searchPhone);
    
    // Extract just the digits to handle country code differences (e.g., matching 0612345678 with +212612345678)
    $digitsOnly = preg_replace('/[^0-9]/', '', $cleanSearch);
    
    // We require at least 8 digits to prevent matching too broadly
    if (strlen($digitsOnly) >= 8) {
        // Match numbers that end with the provided digits (ignoring country code if omitted)
        $searchPattern = '%' . ltrim($digitsOnly, '0');
        
        $stmt = $con->prepare("SELECT UserID as id, name as FName, UserPhoto as Photo, PhoneNumber FROM Users WHERE PhoneNumber LIKE ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $searchedFriend = $row;
            }
            $stmt->close();
        }
    }
}

// 3. Close conn
if ($con) {
    mysqli_close($con);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat · QOON</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- ⚡ Apply theme BEFORE paint to prevent flash -->
    <script>
        (function() {
            var t = localStorage.getItem('qoon_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.add('light-mode');
        })();
    </script>
    <style>
        :root {
            --bg-color: #030303;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.5);
            --primary: #f50057;
            --secondary: #2cb5e8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg-color); color: var(--text-main); min-height: 100vh; overflow-x: hidden; }

        /* ─── QOON (UNIVERSE) 3D GALAXY THEME ─── */
        body { background-color: #010008 !important; overflow-x: hidden; }

        #space {
            position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
            z-index: -3; pointer-events: none;
            background: radial-gradient(circle at center, #0a001a 0%, #010008 100%);
        }

        .chat-wrapper {
            max-width: 800px; margin: 40px auto; padding: 0 20px;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .page-header h1 {
            font-size: 42px; font-weight: 800; letter-spacing: -1.5px;
            background: linear-gradient(135deg, #fff, rgba(255,255,255,0.5));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .page-header p {
            color: var(--text-muted); font-size: 16px; margin-top: 8px;
        }

        /* Custom Tabs */
        .chat-tabs {
            display: flex; gap: 10px; margin-bottom: 30px;
            background: rgba(255,255,255,0.03); padding: 6px; border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.05); backdrop-filter: blur(20px);
        }
        .tab-btn {
            flex: 1; padding: 14px 20px; border-radius: 14px; border: none; outline: none;
            background: transparent; color: var(--text-muted); font-size: 15px; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .tab-btn.active {
            background: rgba(255,255,255,0.1); color: #fff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .tab-content { display: none; animation: slideUp 0.4s ease; }
        .tab-content.active { display: block; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Glass Chat Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px; padding: 20px; margin-bottom: 16px;
            transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
            text-decoration: none; display: flex; align-items: center; gap: 16px;
            cursor: pointer; position: relative; overflow: hidden;
        }
        .glass-card:hover {
            transform: translateY(-4px); background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.15); box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .chat-avatar-wrap {
            position: relative; width: 60px; height: 60px; flex-shrink: 0;
        }
        .chat-avatar {
            width: 100%; height: 100%; border-radius: 50%; object-fit: cover;
            background: #111; border: 2px solid rgba(255,255,255,0.1);
        }
        .driver-avatar {
            position: absolute; bottom: -4px; right: -4px; width: 28px; height: 28px;
            border-radius: 50%; border: 2px solid #000; object-fit: cover; background: #2cb5e8;
        }
        
        .chat-info { flex: 1; }
        .chat-title { font-size: 17px; font-weight: 700; color: #fff; margin-bottom: 4px; display: flex; align-items: center; gap: 8px; }
        .chat-subtitle { font-size: 14px; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
        .chat-badge { font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 8px; text-transform: uppercase; }
        
        .badge-active { background: rgba(46, 204, 113, 0.2); color: #2ecc71; }
        .badge-past { background: rgba(255, 255, 255, 0.1); color: var(--text-muted); }

        .chat-action {
            width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,0.05);
            display: flex; align-items: center; justify-content: center; color: #fff;
            transition: all 0.3s; flex-shrink: 0;
        }
        .glass-card:hover .chat-action { background: #fff; color: #000; transform: scale(1.1); }

        /* --- Friend Search Section --- */
        .search-hero {
            background: linear-gradient(135deg, rgba(245, 0, 87, 0.1), rgba(44, 181, 232, 0.1));
            border: 1px solid rgba(255,255,255,0.05); border-radius: 30px; padding: 30px;
            text-align: center; margin-bottom: 20px;
        }
        /* Friend Search */
        .friend-search-wrap {
            padding: 20px 16px 12px;
        }
        .friend-search-box {
            display: flex; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px; padding: 8px 8px 8px 16px;
            transition: border-color 0.3s;
        }
        .friend-search-box:focus-within {
            border-color: var(--secondary);
            box-shadow: 0 0 0 3px rgba(44,181,232,0.12);
        }
        .friend-search-box .search-icon {
            color: var(--text-muted); font-size: 15px; flex-shrink: 0;
        }
        .friend-search-box input {
            flex: 1; background: transparent; border: none; outline: none;
            color: #fff; font-size: 15px; padding: 6px 0;
        }
        .friend-search-box input::placeholder { color: rgba(255,255,255,0.35); }
        #friend-search-btn {
            flex-shrink: 0; background: var(--secondary); color: #fff;
            border: none; border-radius: 12px; padding: 10px 18px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: all 0.2s;
            white-space: nowrap;
        }
        #friend-search-btn:hover { opacity: 0.85; transform: scale(1.03); }
        #friend-search-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Friend Result Card */
        .friend-result-card {
            display: flex; align-items: center; gap: 14px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 18px; padding: 14px 16px; margin: 8px 16px;
            animation: fadeSlideIn 0.3s ease;
        }
        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .friend-result-avatar {
            width: 52px; height: 52px; border-radius: 50%;
            object-fit: cover; flex-shrink: 0;
            border: 2px solid var(--secondary);
        }
        .friend-result-info { flex: 1; min-width: 0; }
        .friend-result-name { font-weight: 700; font-size: 15px; color: #fff; }
        .friend-result-phone { font-size: 13px; color: var(--text-muted); margin-top: 2px; }
        .friend-chat-btn {
            flex-shrink: 0; background: var(--secondary); color: #fff;
            border: none; border-radius: 12px; padding: 10px 18px;
            font-size: 14px; font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 6px; transition: all 0.2s;
        }
        .friend-chat-btn:hover { opacity: 0.85; transform: scale(1.04); }

        /* Friend History List */
        .friend-history-loading {
            text-align: center; padding: 20px; color: rgba(255,255,255,0.3); font-size: 18px;
        }
        .friend-history-label {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.3);
            text-transform: uppercase; letter-spacing: 1.2px;
            padding: 16px 16px 6px;
        }
        .friend-history-card {
            text-decoration: none;
        }
        .chat-date {
            font-size: 11px; color: var(--text-muted); white-space: nowrap; flex-shrink: 0;
        }
        .friend-search-divider {
            font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.3);
            text-transform: uppercase; letter-spacing: 1.2px;
            padding: 16px 0 8px;
        }

        /* Empty States */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 48px; color: rgba(255,255,255,0.1); margin-bottom: 16px; }
        .empty-state p { color: var(--text-muted); font-size: 15px; }

        @media (max-width: 600px) {
            .page-header h1 { font-size: 32px; }
            .chat-avatar-wrap { width: 50px; height: 50px; }
            .chat-title { font-size: 15px; }
        }
        
        /* ── Order Chat Cards ── */
        .order-chat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 22px;
            margin-bottom: 12px;
            text-decoration: none;
            color: inherit;
            transition: all 0.25s cubic-bezier(0.2,0.8,0.2,1);
            position: relative;
        }
        .order-chat-card:hover {
            background: rgba(255,255,255,0.07);
            border-color: rgba(255,255,255,0.15);
            transform: translateY(-3px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.4);
        }

        /* Avatar stack */
        .oc-avatars {
            position: relative;
            width: 58px;
            height: 58px;
            flex-shrink: 0;
        }
        .oc-shop-logo {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.1);
            background: #111;
        }
        .oc-driver-badge {
            position: absolute;
            bottom: -6px;
            right: -6px;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #010008;
            background: #2cb5e8;
        }
        .oc-driver-waiting {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: rgba(255,255,255,0.5);
            background: rgba(255,255,255,0.08);
            border: 2px solid rgba(255,255,255,0.1);
        }

        /* Info */
        .oc-info { flex: 1; min-width: 0; }
        .oc-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 5px;
        }
        .oc-name {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .oc-date {
            font-size: 11px;
            color: rgba(255,255,255,0.35);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .oc-sub {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            font-size: 12px;
            color: rgba(255,255,255,0.45);
        }

        /* Status pill */
        .oc-status {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }
        .oc-active  { background: rgba(44,181,232,0.15); color: #2cb5e8; border: 1px solid rgba(44,181,232,0.3); }
        .oc-done    { background: rgba(52,199,89,0.12);  color: #34c759; border: 1px solid rgba(52,199,89,0.25); }
        .oc-cancelled { background: rgba(255,59,48,0.12); color: #ff3b30; border: 1px solid rgba(255,59,48,0.25); }

        /* Light Mode Overrides */
        html.light-mode body { background-color: #f8f9fa !important; color: #0f1115 !important; }
        html.light-mode #space { display: none !important; }
        html.light-mode .page-header h1 { background: none !important; -webkit-text-fill-color: initial !important; color: #0f1115 !important; }
        html.light-mode .chat-tabs { background: rgba(0,0,0,0.03) !important; border-color: rgba(0,0,0,0.05) !important; }
        html.light-mode .tab-btn { color: rgba(0,0,0,0.5) !important; }
        html.light-mode .tab-btn.active { background: #ffffff !important; color: #0f1115 !important; box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important; }
        html.light-mode .order-chat-card { background: #ffffff !important; border-color: rgba(0,0,0,0.06) !important; }
        html.light-mode .order-chat-card:hover { background: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; box-shadow: 0 16px 40px rgba(0,0,0,0.08) !important; }
        html.light-mode .oc-name { color: #0f1115 !important; }
        html.light-mode .oc-date { color: rgba(0,0,0,0.4) !important; }
        html.light-mode .oc-sub { color: rgba(0,0,0,0.6) !important; }
        html.light-mode .oc-shop-logo { border-color: rgba(0,0,0,0.05) !important; }
        html.light-mode .oc-driver-badge { border-color: #ffffff !important; }
        html.light-mode .oc-active { background: rgba(44,181,232,0.1) !important; color: #1e88e5 !important; }
        html.light-mode .oc-done { background: rgba(52,199,89,0.1) !important; color: #2e7d32 !important; }
        html.light-mode .oc-cancelled { background: rgba(255,59,48,0.1) !important; color: #c62828 !important; }
        html.light-mode .glass-card { background: #ffffff !important; border-color: rgba(0,0,0,0.06) !important; }
        html.light-mode .chat-title { color: #0f1115 !important; }
        html.light-mode .chat-subtitle { color: rgba(0,0,0,0.5) !important; }
        html.light-mode .search-hero { background: linear-gradient(135deg, rgba(245,0,87,0.05), rgba(44,181,232,0.05)) !important; border-color: rgba(0,0,0,0.05) !important; }
        html.light-mode .friend-search-box input { background: #ffffff !important; border-color: rgba(0,0,0,0.1) !important; color: #0f1115 !important; }
        html.light-mode .chat-action { background: rgba(0,0,0,0.05) !important; color: #0f1115 !important; }
        html.light-mode .search-hero h2 { color: #0f1115 !important; }
        html.light-mode .page-header p { color: rgba(0,0,0,0.5) !important; }



        <?php if ($isIframe): ?>
        body { background: transparent !important; }
        .chat-wrapper { margin-top: 10px; padding-bottom: 30px; }
        #space { display: none; }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php if (!$isIframe): ?>
    <canvas id="space"></canvas>
    <?php require_once 'includes/header.php'; ?>
    <?php endif; ?>

    <div class="chat-wrapper">
        <?php if ($authRequired): ?>
        <div class="empty-state" style="padding: 60px 20px; text-align: center;">
            <i class="fa-solid fa-lock" style="font-size: 56px; color: rgba(255,255,255,0.15); margin-bottom: 20px; display:block;"></i>
            <h2 style="font-size: 22px; margin-bottom: 10px;">Sign in Required</h2>
            <p style="color: var(--text-muted); margin-bottom: 28px;">Please sign in to view your messages.</p>
            <a href="javascript:void(0)" onclick="window.parent.location.href='index.php?auth_required=1'"
               style="display: inline-block; padding: 14px 32px; background: #7b00ff; color: #fff; border-radius: 99px; text-decoration: none; font-weight: 700;">
               Sign In Now
            </a>
        </div>
        <?php else: ?>
        <div class="page-header">
            <h1>Messages</h1>
            <p>Connect with shops, drivers, and friends.</p>
        </div>

        <div class="chat-tabs">
            <button class="tab-btn active" onclick="switchTab('all', this)">
                <i class="fa-solid fa-layer-group"></i> All
            </button>
            <button class="tab-btn" onclick="switchTab('orders', this)">
                <i class="fa-solid fa-store"></i> Orders
            </button>
            <button class="tab-btn" onclick="switchTab('friends', this)">
                <i class="fa-solid fa-user-group"></i> Friends
            </button>
        </div>

        <!-- TAB 1: ALL -->
        <div id="tab-all" class="tab-content active">
            <!-- All tab: order chats -->
            <?php if (empty($orderChats)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-comments"></i>
                    <p>No chat history yet.<br>Place an order to start chatting with shops and drivers.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orderChats as $oc):
                    $sName    = $oc['ShopName'] ?? 'Shop';
                    $sLogo    = fullUrl($oc['ShopLogo'] ?? '', $domain) ?: "https://ui-avatars.com/api/?name=" . urlencode($sName) . "&background=1a1a2e&color=fff&size=128";
                    $dName    = trim($oc['DriverName'] ?? '');
                    $dPhoto   = fullUrl($oc['DriverPhoto'] ?? '', $domain);
                    if (!$dPhoto && $dName) $dPhoto = "https://ui-avatars.com/api/?name=" . urlencode($dName) . "&background=2cb5e8&color=fff&size=80";
                    $rawSt    = strtoupper($oc['OrderState'] ?? '');
                    $isDone   = in_array($rawSt, ['DONE','FINISH','RATED','DELIVERED','COMPLETED','CANCELLED','CANCELED','CANCEL','RETURNED','REFUNDED']);
                    $isCancelled = in_array($rawSt, ['CANCELLED','CANCELED','CANCEL','RETURNED','REFUNDED']);
                    $total    = floatval($oc['OrderPriceFromShop'] ?? 0) + floatval($oc['OrderPrice'] ?? 0) + floatval($oc['PlatformFee'] ?? 0);
                    $date     = $oc['CreatedAtOrders'] ? date('M j', strtotime($oc['CreatedAtOrders'])) : '';
                    $hasDriver = !empty($dName) && !empty($oc['DelvryId']) && $oc['DelvryId'] != '0';
                ?>
                <a href="track_order.php?orderId=<?= $oc['OrderID'] ?>&tot=<?= $total ?>" class="order-chat-card">
                    <!-- Avatar Stack: Shop + Driver overlay -->
                    <div class="oc-avatars">
                        <img class="oc-shop-logo" src="<?= htmlspecialchars($sLogo) ?>" alt=""
                             onerror="this.src='https://ui-avatars.com/api/?name=S&background=1a1a2e&color=fff'">
                        <?php if ($hasDriver): ?>
                        <img class="oc-driver-badge" src="<?= htmlspecialchars($dPhoto) ?>" alt=""
                             onerror="this.src='https://ui-avatars.com/api/?name=D&background=2cb5e8&color=fff'">
                        <?php else: ?>
                        <div class="oc-driver-badge oc-driver-waiting">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- Info -->
                    <div class="oc-info">
                        <div class="oc-top">
                            <span class="oc-name"><?= htmlspecialchars($sName) ?></span>
                            <span class="oc-date"><?= $date ?></span>
                        </div>
                        <div class="oc-sub">
                            <?php if ($hasDriver): ?>
                            <i class="fa-solid fa-motorcycle" style="font-size:10px; color:#2cb5e8;"></i>
                            <span><?= htmlspecialchars($dName) ?></span>
                            <span style="opacity:0.3;">·</span>
                            <?php endif; ?>
                            <span>Order #<?= $oc['OrderID'] ?></span>
                            <?php if ($total > 0): ?>
                            <span style="opacity:0.3;">·</span>
                            <span><?= number_format($total, 2) ?> MAD</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- Status pill -->
                    <div class="oc-status <?= $isCancelled ? 'oc-cancelled' : ($isDone ? 'oc-done' : 'oc-active') ?>">
                        <?php if ($isCancelled): ?><i class="fa-solid fa-xmark"></i>
                        <?php elseif ($isDone): ?><i class="fa-solid fa-check"></i>
                        <?php else: ?><i class="fa-solid fa-circle fa-fade" style="font-size:7px;"></i>
                        <?php endif; ?>
                        <?= $isCancelled ? 'Cancelled' : ($isDone ? 'Done' : 'Active') ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 2: ORDERS (Shops & Drivers) -->
        <div id="tab-orders" class="tab-content">
            <!-- Orders tab -->
            <?php if (empty($orderChats)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <p>No order history found.<br>When you place an order, your chat will appear here.</p>
                </div>
            <?php else: ?>
                <?php foreach ($orderChats as $oc):
                    $sName    = $oc['ShopName'] ?? 'Shop';
                    $sLogo    = fullUrl($oc['ShopLogo'] ?? '', $domain) ?: "https://ui-avatars.com/api/?name=" . urlencode($sName) . "&background=1a1a2e&color=fff&size=128";
                    $dName    = trim($oc['DriverName'] ?? '');
                    $dPhoto   = fullUrl($oc['DriverPhoto'] ?? '', $domain);
                    if (!$dPhoto && $dName) $dPhoto = "https://ui-avatars.com/api/?name=" . urlencode($dName) . "&background=2cb5e8&color=fff&size=80";
                    $rawSt    = strtoupper($oc['OrderState'] ?? '');
                    $isDone   = in_array($rawSt, ['DONE','FINISH','RATED','DELIVERED','COMPLETED','CANCELLED','CANCELED','CANCEL','RETURNED','REFUNDED']);
                    $isCancelled = in_array($rawSt, ['CANCELLED','CANCELED','CANCEL','RETURNED','REFUNDED']);
                    $total    = floatval($oc['OrderPriceFromShop'] ?? 0) + floatval($oc['OrderPrice'] ?? 0) + floatval($oc['PlatformFee'] ?? 0);
                    $date     = $oc['CreatedAtOrders'] ? date('M j', strtotime($oc['CreatedAtOrders'])) : '';
                    $hasDriver = !empty($dName) && !empty($oc['DelvryId']) && $oc['DelvryId'] != '0';
                ?>
                <a href="track_order.php?orderId=<?= $oc['OrderID'] ?>&tot=<?= $total ?>" class="order-chat-card">
                    <div class="oc-avatars">
                        <img class="oc-shop-logo" src="<?= htmlspecialchars($sLogo) ?>" alt=""
                             onerror="this.src='https://ui-avatars.com/api/?name=S&background=1a1a2e&color=fff'">
                        <?php if ($hasDriver): ?>
                        <img class="oc-driver-badge" src="<?= htmlspecialchars($dPhoto) ?>" alt=""
                             onerror="this.src='https://ui-avatars.com/api/?name=D&background=2cb5e8&color=fff'">
                        <?php else: ?>
                        <div class="oc-driver-badge oc-driver-waiting">
                            <i class="fa-solid fa-motorcycle"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="oc-info">
                        <div class="oc-top">
                            <span class="oc-name"><?= htmlspecialchars($sName) ?></span>
                            <span class="oc-date"><?= $date ?></span>
                        </div>
                        <div class="oc-sub">
                            <?php if ($hasDriver): ?>
                            <i class="fa-solid fa-motorcycle" style="font-size:10px; color:#2cb5e8;"></i>
                            <span><?= htmlspecialchars($dName) ?></span>
                            <span style="opacity:0.3;">·</span>
                            <?php endif; ?>
                            <span>Order #<?= $oc['OrderID'] ?></span>
                            <?php if ($total > 0): ?>
                            <span style="opacity:0.3;">·</span>
                            <span><?= number_format($total, 2) ?> MAD</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="oc-status <?= $isCancelled ? 'oc-cancelled' : ($isDone ? 'oc-done' : 'oc-active') ?>">
                        <?php if ($isCancelled): ?><i class="fa-solid fa-xmark"></i>
                        <?php elseif ($isDone): ?><i class="fa-solid fa-check"></i>
                        <?php else: ?><i class="fa-solid fa-circle fa-fade" style="font-size:7px;"></i>
                        <?php endif; ?>
                        <?= $isCancelled ? 'Cancelled' : ($isDone ? 'Done' : 'Active') ?>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- TAB 2: FRIENDS -->
        <div id="tab-friends" class="tab-content">

            <!-- Recent Friend Chats (from Firebase) -->
            <div id="friend-history-list">
                <div class="friend-history-loading"><i class="fa-solid fa-circle-notch fa-spin"></i></div>
            </div>

            <!-- Divider + Search -->
            <div class="friend-search-wrap">
                <div class="friend-search-divider">Find a new friend</div>
                <div class="friend-search-box">
                    <i class="fa-solid fa-phone search-icon"></i>
                    <input type="tel" id="friend-phone-input" placeholder="Enter phone number..." autocomplete="off">
                    <button id="friend-search-btn"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
                </div>
            </div>

            <!-- Search Results Area -->
            <div id="friend-search-result"></div>
        </div>
        <?php endif; // end !$authRequired else ?>
    </div>

    <script>
        function switchTab(tabId, btn) {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        }

        function startFriendChat(friendId) {
            window.location.href = 'friend_chat.php?uid=' + friendId + '<?= $isIframe ? "&iframe=1" : "" ?>';
        }

        // ── Firebase: Load Friend Chat History ───────────────────
        <?php if (!$authRequired): ?>
        (function loadFriendHistory() {
            const myId = "<?= addslashes($userId) ?>";
            const historyBox = document.getElementById('friend-history-list');
            if (!historyBox) return;

            // Firebase must be loaded — it is included at bottom of page
            function initHistory() {
                if (typeof firebase === 'undefined' || !firebase.database) {
                    setTimeout(initHistory, 300);
                    return;
                }
                const db = firebase.database();
                // List all FriendChats where myId appears in the key (format: id1_id2)
                db.ref('FriendChats').once('value').then(snapshot => {
                    const chats = [];
                    snapshot.forEach(child => {
                        const key = child.key; // e.g. "1000001_1000009"
                        const parts = key.split('_');
                        if (parts.length === 2 && (parts[0] === myId || parts[1] === myId)) {
                            const friendId = parts[0] === myId ? parts[1] : parts[0];
                            // Get last message
                            let lastMsg = null;
                            let lastTs = 0;
                            child.forEach(msgSnap => {
                                const m = msgSnap.val();
                                if (m && m.timestamp > lastTs) {
                                    lastTs = m.timestamp;
                                    lastMsg = m;
                                }
                            });
                            if (lastMsg) chats.push({ friendId, lastMsg, lastTs });
                        }
                    });

                    // Sort by most recent
                    chats.sort((a, b) => b.lastTs - a.lastTs);

                    if (chats.length === 0) {
                        historyBox.innerHTML = '';
                        return;
                    }

                    // Fetch friend info from server for each
                    const ids = chats.map(c => c.friendId);
                    fetch('chat_user_info.php?ids=' + encodeURIComponent(ids.join(',')))
                        .then(r => r.json())
                        .then(users => {
                            let html = '<div class="friend-history-label">Recent Chats</div>';
                            chats.forEach(chat => {
                                const u = users[chat.friendId] || {};
                                const name = u.name || 'User';
                                const photo = u.photo || `https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6C63FF&color=fff`;
                                const type = chat.lastMsg.type || 'Text';
                                let preview = '';
                                if (type === 'Image') preview = '📷 Photo';
                                else if (type === 'Transfer') preview = '💸 Payment';
                                else preview = (chat.lastMsg.message || '').substring(0, 40);

                                const time = formatHistoryTime(chat.lastTs);
                                html += `
                                    <a href="javascript:void(0)" onclick="startFriendChat('${chat.friendId}')" class="glass-card friend-history-card">
                                        <div class="chat-avatar-wrap">
                                            <img src="${photo}" class="chat-avatar" alt="" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(name)}&background=6C63FF&color=fff'">
                                        </div>
                                        <div class="chat-info">
                                            <div class="chat-title">${name}</div>
                                            <div class="chat-subtitle" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;">${preview}</div>
                                        </div>
                                        <div class="chat-date">${time}</div>
                                    </a>`;
                            });
                            historyBox.innerHTML = html;
                        })
                        .catch(() => { historyBox.innerHTML = ''; });
                }).catch(() => { historyBox.innerHTML = ''; });
            }
            initHistory();

            function formatHistoryTime(ts) {
                const d = new Date(ts);
                const now = new Date();
                const diffDays = Math.floor((now - d) / 86400000);
                if (diffDays === 0) {
                    let h = d.getHours(), m = d.getMinutes();
                    const ap = h >= 12 ? 'PM' : 'AM';
                    h = h % 12 || 12;
                    return `${h}:${m < 10 ? '0'+m : m} ${ap}`;
                }
                if (diffDays === 1) return 'Yesterday';
                return d.toLocaleDateString([], {month: 'short', day: 'numeric'});
            }
        })();
        <?php endif; ?>

        // AJAX Friend Search
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('friend-phone-input');
            const btn = document.getElementById('friend-search-btn');
            const resultBox = document.getElementById('friend-search-result');

            function doSearch() {
                const phone = input ? input.value.trim() : '';
                if (!phone || phone.length < 4) {
                    resultBox.innerHTML = '<div class="empty-state"><i class="fa-solid fa-phone"></i><p>Please enter a phone number to search.</p></div>';
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Searching...';
                resultBox.innerHTML = '';

                fetch('chat_search.php?phone=' + encodeURIComponent(phone))
                    .then(r => r.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search';
                        if (data.found) {
                            resultBox.innerHTML = `
                                <div class="friend-result-card">
                                    <img src="${data.photo}" class="friend-result-avatar" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&background=6C63FF&color=fff'">
                                    <div class="friend-result-info">
                                        <div class="friend-result-name">${data.name}</div>
                                        <div class="friend-result-phone">${data.phone}</div>
                                    </div>
                                    <button class="friend-chat-btn" onclick="startFriendChat('${data.id}')">
                                        <i class="fa-solid fa-comment"></i> Chat
                                    </button>
                                </div>`;
                        } else {
                            resultBox.innerHTML = '<div class="empty-state"><i class="fa-solid fa-user-slash"></i><p>No user found for <strong>' + phone + '</strong>.<br>Check the number and try again.</p></div>';
                        }
                    })
                    .catch(() => {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Search';
                        resultBox.innerHTML = '<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Connection error. Please try again.</p></div>';
                    });
            }

            if (btn) btn.addEventListener('click', doSearch);
            if (input) input.addEventListener('keydown', function(e) { if (e.key === 'Enter') doSearch(); });
        });

        <?php if (!$isIframe): ?>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('space');
            if (!canvas) return;
            const c = canvas.getContext('2d');

            let w = canvas.width = window.innerWidth;
            let h = canvas.height = window.innerHeight;

            window.addEventListener('resize', () => {
                w = canvas.width = window.innerWidth;
                h = canvas.height = window.innerHeight;
            });

            const numStars = 800;
            const stars = [];

            class Star {
                constructor() {
                    this.x = Math.random() * w * 2 - w;
                    this.y = Math.random() * h * 2 - h;
                    this.z = Math.random() * w;
                    this.pz = this.z;
                    this.size = Math.random() * 1.5 + 0.5;
                    this.color = Math.random() > 0.8 ? 'rgba(0, 212, 255, ' : 'rgba(255, 255, 255, ';
                }

                update() {
                    this.z -= 2;
                    if (this.z < 1) {
                        this.z = w;
                        this.x = Math.random() * w * 2 - w;
                        this.y = Math.random() * h * 2 - h;
                        this.pz = this.z;
                    }
                }

                show() {
                    const sx = (this.x) / this.z * w + w / 2;
                    const sy = (this.y) / this.z * w + h / 2;
                    const r = this.size * (w / this.z);
                    const opacity = Math.max(0, 1 - (this.z / w));

                    c.beginPath();
                    c.arc(sx, sy, r, 0, Math.PI * 2);
                    c.fillStyle = this.color + opacity + ')';
                    c.fill();
                }
            }

            for (let i = 0; i < numStars; i++) {
                stars.push(new Star());
            }

            function animate() {
                requestAnimationFrame(animate);
                c.fillStyle = 'rgba(1, 0, 8, 0.2)';
                c.fillRect(0, 0, w, h);
                for (let i = 0; i < stars.length; i++) {
                    stars[i].update();
                    stars[i].show();
                }
            }

            animate();
        });
        <?php endif; ?>
    </script>
    <!-- Firebase SDK for friend history -->
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="assets/js/firebase-auth.js"></script>
</body>
</html>
