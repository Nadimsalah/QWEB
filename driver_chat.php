<?php
require_once 'conn.php';

// AJAX Order Details Fetch (Real-time sync)
if(isset($_GET['ajax_details'])) {
    $orderId = (int)$_GET['orderId'];
    $res = mysqli_query($con, "SELECT OrderDetails, OrderPrice, Method FROM Orders WHERE OrderID=$orderId");
    if($row = mysqli_fetch_assoc($res)) {
        header('Content-Type: application/json');
        echo json_encode([
            'details' => $row['OrderDetails'],
            'price' => $row['OrderPrice'],
            'method' => $row['Method']
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_status_update'])) {
    $newStatus = mysqli_real_escape_string($con, $_POST['new_status']);
    $oId = mysqli_real_escape_string($con, $_POST['order_id']);
    
    // Update the database
    $con->query("UPDATE Orders SET OrderState='$newStatus' WHERE OrderID='$oId'");
    
    if(strtolower($newStatus) === 'delivered') {
        $con->query("UPDATE Orders SET OrderState='Done' WHERE OrderID='$oId'");
        $newStatus = 'Done'; 
    }
    
    // --- Firebase Push (Sync with User App) ---
    $fbUrl = "https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/$oId.json";
    $fbData = ['current_status' => $newStatus, 'updated_at' => time()];
    $chFb = curl_init();
    curl_setopt($chFb, CURLOPT_URL, $fbUrl);
    curl_setopt($chFb, CURLOPT_POST, true);
    curl_setopt($chFb, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chFb, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($chFb, CURLOPT_SSL_VERIFYHOST, 0);  
    curl_setopt($chFb, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chFb, CURLOPT_POSTFIELDS, json_encode($fbData));
    curl_exec($chFb);
    curl_close($chFb);

    echo json_encode(['success' => true]);
    exit;
}

$orderId = isset($_GET['orderId']) ? mysqli_real_escape_string($con, $_GET['orderId']) : '0';
$totalPrice = isset($_GET['tot']) ? htmlspecialchars($_GET['tot']) : '0';

// Default mock values
$shopName = 'QOON Shop';
$shopImgUrl = "https://ui-avatars.com/api/?name=QOON+Shop&background=FFD700&color=000";
$driverName = 'Ahmed R.';
$driverImgUrl = "https://randomuser.me/api/portraits/men/32.jpg";
$fourDigitPin = '----'; 
$expectedCancelPin = str_pad(abs(crc32($orderId . "QOON_CANCEL_TOKEN")) % 10000, 4, '0', STR_PAD_LEFT);
$expectedPickupPin = str_pad(abs(crc32($orderId . "QOON_SHOP_PICKUP_TOKEN")) % 10000, 4, '0', STR_PAD_LEFT);

if ($con && $orderId !== '0') {
    $res = $con->query("SELECT o.*, d.FName, d.LName, d.PersonalPhoto as DriverImg, s.ShopName as SName, s.ShopLogo as SPhoto, u.name as UserName, u.UserPhoto as UserImg 
                        FROM Orders o 
                        LEFT JOIN Drivers d ON o.DelvryId = d.DriverID 
                        LEFT JOIN Shops s ON o.ShopID = s.ShopID 
                        LEFT JOIN Users u ON o.UserID = u.UserID
                        WHERE o.OrderID = '$orderId'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        
        $shopName = !empty($row['SName']) ? $row['SName'] : (!empty($row['DestinationName']) ? $row['DestinationName'] : 'QOON Shop');
        $shopImgUrl = !empty($row['SPhoto']) ? $row['SPhoto'] : "https://ui-avatars.com/api/?name=".urlencode($shopName)."&background=6366f1&color=fff";
        
        if (!empty($row['FName'])) {
            $lName = $row['LName'] ?? '';
            $driverName = trim($row['FName'] . ' ' . mb_substr($lName, 0, 1) . '.');
        } else {
            $driverName = 'Waiting for Driver';
        }
        
        if (!empty($row['DriverImg']) && strpos($row['DriverImg'], 'http') !== false) {
            $driverImgUrl = $row['DriverImg'];
        } else if (!empty($row['DriverImg']) && strlen($row['DriverImg']) > 5) {
            $driverImgUrl = "https://dash.qoon.app/assets/images/users/" . $row['DriverImg'];
        } else {
            $driverImgUrl = "https://ui-avatars.com/api/?name=".urlencode($driverName)."&background=6366f1&color=fff";
        }
        
        $userName = !empty($row['UserName']) ? $row['UserName'] : 'Customer';
        $userImgUrl = (!empty($row['UserImg']) && strpos($row['UserImg'], 'http') !== false) ? $row['UserImg'] : "https://ui-avatars.com/api/?name=".urlencode($userName)."&background=0ea5e9&color=fff";
        
        if (isset($row['FourDigit']) && $row['FourDigit'] !== '' && $row['FourDigit'] !== '0') {
            $fourDigitPin = str_pad($row['FourDigit'], 4, '0', STR_PAD_LEFT);
        } else {
            $fourDigitPin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $con->query("UPDATE Orders SET FourDigit='$fourDigitPin' WHERE OrderID='$orderId'");
        }
        
        $actualDriverId = $row['DelvryId'] ?? '0';
        $actualShopId = $row['ShopID'] ?? '0';
        $rawStatus = $row['OrderState'] ?? 'waiting';
        $pMethod = $row['Method'] ?? 'CASH';
        if($pMethod == 'CASH') $paymentMethodText = 'Cash';
        else if($pMethod == 'CARD') $paymentMethodText = 'Card';
        else $paymentMethodText = 'QOON Pay';
        
        $userFees = floatval($row['PlatformFee'] ?? 0);
        $productsFee = floatval($row['OrderPriceFromShop'] ?? 0);
        $deliveryFee = floatval($row['OrderPrice'] ?? 0);
        
        $tPrice = $productsFee + $deliveryFee + $userFees;
        if ($tPrice > 0) $totalPrice = $tPrice;
        
        $orderDetailsStr = $row['OrderDetails'] ?? '';
        $shopLat = isset($row['DestnationLat']) && is_numeric($row['DestnationLat']) ? $row['DestnationLat'] : 33.5750;
        $shopLng = isset($row['DestnationLongt']) && is_numeric($row['DestnationLongt']) ? $row['DestnationLongt'] : -7.5850;
        $userLat = isset($row['UserLat']) && is_numeric($row['UserLat']) ? $row['UserLat'] : 33.5650;
        $userLng = isset($row['UserLongt']) && is_numeric($row['UserLongt']) ? $row['UserLongt'] : -7.5950;
    }
}

$orderStatusText = 'Order placed';
$stepIndex = 1;

if (isset($rawStatus)) {
    $r = strtolower(trim($rawStatus));
    if (in_array($r, ['cancelled', 'canceled', 'returned', 'refunded'])) { $orderStatusText = 'Cancelled'; $stepIndex = 6; }
    else if (in_array($r, ['done', 'finish', 'rated', 'order delivered', 'delivered'])) { $orderStatusText = 'Delivered'; $stepIndex = 6; }
    else if (in_array($r, ['found', 'come to take it', 'on way', 'on the way', 'arrived'])) { $orderStatusText = 'On Way'; $stepIndex = 5; }
    else if (in_array($r, ['order pickup', 'pickup', 'picked', 'picked up', 'ready', 'arrived at shop'])) { $orderStatusText = 'Ready'; $stepIndex = 4; }
    else if (in_array($r, ['prepared', 'order processed', 'processed', 'preparing'])) { $orderStatusText = 'Preparing'; $stepIndex = 3; }
    else if (in_array($r, ['accept', 'accepted', 'yes', 'order confirmed', 'confirmed', 'doing', 'heading to shop'])) { $orderStatusText = 'Heading'; $stepIndex = 2; }
    else { $orderStatusText = 'Placed'; $stepIndex = 1; }
}
$isCancelled = in_array(strtolower($rawStatus ?? ''), ['cancelled', 'canceled', 'returned', 'refunded']);
$barWidth = $isCancelled ? "100%" : (($stepIndex / 6) * 100) . "%";
$expectedCancelPin = str_pad(abs(crc32($orderId . "QOON_CANCEL_TOKEN")) % 10000, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Chat · QOON Express</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-light: #818cf8;
            --accent: #0ea5e9;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.02);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.08);
            --glass: rgba(255, 255, 255, 0.8);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; -webkit-tap-highlight-color: transparent; }
        body { background: var(--bg); height: 100vh; display: flex; overflow: hidden; color: var(--text); }

        /* Sidebar Main */
        #sidebar {
            width: 420px;
            height: 100vh;
            background: var(--card);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            box-shadow: 4px 0 20px rgba(0,0,0,0.02);
            position: relative;
        }

        /* Unified Top Dashboard */
        .top-dashboard {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            display: flex; flex-direction: column; z-index: 10;
            box-shadow: 0 4px 20px rgba(99,102,241,0.25);
        }
        .header-row { padding: 14px 16px; display: flex; justify-content: space-between; align-items: center; }
        .header-info { display: flex; align-items: center; gap: 12px; }
        .btn-back {
            width: 36px; height: 36px; border-radius: 12px;
            background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            display: flex; align-items: center; justify-content: center;
            color: #fff; text-decoration: none; transition: 0.2s;
        }
        .btn-back:hover { background: rgba(255,255,255,0.35); transform: translateX(-2px); }
        .header-title { font-weight: 800; font-size: 15px; color: #fff; }

        .status-pill {
            padding: 3px 8px; border-radius: 99px; font-size: 9px; font-weight: 800;
            text-transform: uppercase; background: rgba(255,255,255,0.25);
            color: #fff; display: flex; align-items: center; gap: 4px;
            margin-top: 4px; width: fit-content; backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .collect-amount { text-align: right; line-height: 1.1; }
        .collect-label { font-size: 9px; font-weight: 800; color: rgba(255,255,255,0.75); text-transform: uppercase; }
        .collect-val { font-size: 22px; font-weight: 900; color: #fff; }
        .collect-currency { font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.85); }

        .nav-actions { padding: 10px 16px 14px; display: flex; gap: 8px; }
        .btn-nav-compact {
            flex: 1; height: 40px; border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.35);
            background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);
            font-size: 12px; font-weight: 700; color: #fff;
            display: flex; align-items: center; justify-content: center; gap: 7px;
            cursor: pointer; transition: all 0.2s;
        }
        .btn-nav-compact:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
        .btn-nav-compact i { font-size: 13px; opacity: 0.9; }

        .action-row { padding: 10px 16px 14px; display: flex; gap: 8px; align-items: center; }
        .pin-badge {
            font-size: 13px; font-weight: 900; letter-spacing: 2px;
            padding: 7px 14px; border-radius: 10px;
            background: rgba(255,255,255,0.2); color: #fff;
            display: flex; align-items: center; gap: 7px;
            border: 1.5px dashed rgba(255,255,255,0.5); backdrop-filter: blur(4px);
        }
        .pin-badge.cancel { background: rgba(239,68,68,0.2); border-color: rgba(239,68,68,0.5); }

        .btn-status {
            flex: 1; height: 38px; border-radius: 12px;
            font-size: 12px; font-weight: 800; border: none; cursor: pointer;
            transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px;
        }
        .btn-onway { background: #fff; color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-onway:hover, .btn-onway.active-state { transform: translateY(-1px); background: #e0e7ff; box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
        .btn-delivered { background: rgba(255,255,255,0.2); color: #fff; border: 1.5px solid rgba(255,255,255,0.4); }
        .btn-delivered:hover { background: rgba(255,255,255,0.3); }


        /* Chat Container */
        .chat-view {
            flex: 1; overflow-y: auto; padding: 16px 14px 80px; display: flex; flex-direction: column; gap: 10px;
            background: #f8fafc; scroll-behavior: smooth;
        }
        .chat-view::-webkit-scrollbar { width: 3px; }
        .chat-view::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .msg-row { display: flex; gap: 8px; max-width: 88%; }
        .msg-row.me { align-self: flex-end; flex-direction: row-reverse; }

        .msg-avatar { width: 30px; height: 30px; border-radius: 10px; object-fit: cover; flex-shrink: 0; box-shadow: var(--shadow-sm); }
        .msg-body { display: flex; flex-direction: column; gap: 3px; }
        .msg-sender { font-size: 10px; font-weight: 700; color: var(--text-muted); padding: 0 6px; }
        .me .msg-sender { text-align: right; }

        .bubble {
            padding: 10px 14px; border-radius: 18px; font-size: 13.5px; line-height: 1.5;
            background: #fff; color: var(--text); border: 1px solid var(--border);
            border-top-left-radius: 4px; box-shadow: var(--shadow-sm);
        }
        .me .bubble {
            background: linear-gradient(135deg, #6366f1 0%, #818cf8 100%);
            color: #fff; border: none; border-top-left-radius: 18px; border-top-right-radius: 4px;
            box-shadow: 0 4px 14px rgba(99,102,241,0.22);
        }

        /* Order Items Card */
        .order-summary-card {
            background: #fff; border: 1.5px solid var(--primary); border-radius: 16px; padding: 12px 14px;
            width: 100%; max-width: 260px; box-shadow: 0 4px 16px rgba(99,102,241,0.1);
        }
        .summary-header { display: flex; align-items: center; gap: 8px; color: var(--primary); font-weight: 800; font-size: 11px; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .items-list { width: 100%; border-collapse: collapse; }
        .items-list td { padding: 5px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
        .items-list tr:last-child td { border-bottom: none; }
        .item-qty { font-weight: 800; color: var(--primary); background: #ede9fe; padding: 1px 6px; border-radius: 5px; margin-right: 6px; font-size: 11px; }
        .summary-footer { margin-top: 10px; padding-top: 8px; border-top: 1px dashed #c7d2fe; display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }
        .total-value { font-weight: 900; color: var(--success); font-size: 15px; }

        /* Premium Input Bar Rebuild */
        .bottom-bar {
            position: absolute; bottom: 0; left: 0; width: 100%; z-index: 500;
            padding: 12px 14px 16px; border-top: 1px solid rgba(226, 232, 240, 0.8);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.04);
            display: flex;
        }
        .input-group { 
            display: flex; gap: 10px; align-items: center; position: relative; width: 100%;
        }
        
        .btn-plus {
            width: 44px; height: 44px; border-radius: 50%; flex-shrink: 0;
            background: #f1f5f9; color: var(--primary);
            border: 1px solid #e2e8f0; cursor: pointer; font-size: 20px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; align-items: center; justify-content: center; padding: 0;
        }
        .btn-plus:hover { background: #e2e8f0; transform: rotate(90deg); color: var(--text); }
        .btn-plus i { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; line-height: 1; }
        
        .chat-input-wrapper {
            flex: 1; min-width: 0; background: #f8fafc;
            border-radius: 22px; border: 1.5px solid #e2e8f0;
            display: flex; align-items: center; padding: 3px 4px 3px 16px;
            transition: all 0.2s; min-height: 44px;
        }
        .chat-input-wrapper:focus-within {
            background: #ffffff; border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.1);
        }
        
        .chat-input {
            flex: 1; border: none; background: transparent;
            font-size: 15px; color: var(--text); padding: 0;
            outline: none; font-family: 'Outfit', sans-serif;
            min-width: 0;
        }
        .chat-input::placeholder { color: #94a3b8; font-weight: 500; }
        
        .btn-send {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0; padding: 0;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: #fff; border: none; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; font-size: 14px;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
            margin-left: 8px;
        }
        html[dir="rtl"] .chat-input-wrapper { padding: 4px 16px 4px 4px; }
        html[dir="rtl"] .btn-send { margin-left: 0; margin-right: 8px; }
        .btn-send:hover { transform: scale(1.05); box-shadow: 0 6px 14px rgba(99, 102, 241, 0.4); }
        .btn-send i { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; line-height: 1; transform: translateX(-1px); }
        html[dir="rtl"] .btn-send i { transform: translateX(1px) scaleX(-1); }

        /* Attach Menu */
        .menu-overlay {
            position: absolute; bottom: 54px; left: 0;
            background: #fff; border: 1px solid var(--border); border-radius: 18px;
            padding: 8px; min-width: 200px; box-shadow: 0 10px 40px rgba(0,0,0,0.12);
            display: none; z-index: 200; animation: slideUpMenu 0.2s ease;
        }
        .menu-overlay.active { display: block; }
        @keyframes slideUpMenu { from { transform: translateY(8px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .menu-item {
            display: flex; align-items: center; gap: 12px; padding: 11px 14px;
            color: var(--text); font-size: 13.5px; font-weight: 600;
            border-radius: 12px; cursor: pointer; transition: 0.15s;
        }
        .menu-item:hover { background: #f8fafc; }
        .menu-item i { width: 18px; text-align: center; color: var(--primary); font-size: 14px; }

        /* Chat Closed Banner */
        .chat-closed-banner {
            padding: 14px 20px; border-top: 1px solid var(--border);
            background: #f8fafc; display: none;
            align-items: center; justify-content: center; gap: 10px;
            font-size: 13px; font-weight: 700; color: var(--text-muted);
            flex-shrink: 0;
        }
        .chat-closed-banner i { font-size: 16px; }

        .modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.4); backdrop-filter: blur(8px); z-index: 5000; display: none; align-items: center; justify-content: center; padding: 20px; animation: fadeIn 0.2s; }
        .modal-overlay.active { display: flex; }
        .modal-card { background: #fff; border-radius: 24px; padding: 24px; width: 100%; max-width: 360px; text-align: center; box-shadow: var(--shadow-lg); animation: slideIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideIn { from { transform: translateY(20px) scale(0.95); opacity: 0; } to { transform: translateY(0) scale(1); opacity: 1; } }

        /* Map (Desktop) */
        #map-container { flex: 1; height: 100vh; position: relative; background: #e2e8f0; }
        #map { position: absolute; inset: 0; z-index: 1; }
        
        .floating-map-ctrl { position: absolute; top: 16px; left: 16px; right: 16px; z-index: 10; pointer-events: none; display: flex; justify-content: space-between; }
        .map-btn { pointer-events: auto; background: #fff; border: none; padding: 10px 20px; border-radius: 99px; font-weight: 800; font-size: 13px; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .map-btn-primary { background: var(--primary); color: #fff; }

        /* Mobile specific overrides */
        @media (max-width: 768px) {
            #sidebar { width: 100%; }
            #map-container { position: fixed; inset: 0; transform: translateY(100%); transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); z-index: 2000; }
            body.show-map #map-container { transform: translateY(0); }
            .btn-map-toggle { width: 36px; height: 36px; border-radius: 12px; background: #f1f5f9; border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; margin-left: 8px; }
        }
        @media (min-width: 769px) { .btn-map-toggle { display: none; } .floating-map-ctrl { display: none; } }
    </style>
</head>
<body>

    <div id="sidebar">
        <!-- Unified Top Dashboard -->
        <div class="top-dashboard">
            <!-- Header Row: Title, Status, Collect -->
            <div class="header-row">
                <div class="header-info">
                    <a href="driver_dashboard.php" class="btn-back"><i class="fa-solid fa-chevron-left"></i></a>
                    <div>
                        <div class="header-title">Order #<?= $orderId ?></div>
                        <div class="status-pill">
                            <i class="fa-solid fa-circle" style="font-size: 6px;"></i>
                            <span><?= $orderStatusText ?></span>
                        </div>
                    </div>
                </div>
                <div class="collect-amount">
                    <div class="collect-label">Collect (<?= $paymentMethodText ?>)</div>
                    <div class="collect-val"><?= $totalPrice ?> <span class="collect-currency">MAD</span></div>
                </div>
            </div>

            <!-- Navigation Row -->
            <div class="nav-actions">
                <button class="btn-nav-compact" onclick="openNavOptions(<?= $shopLat ?>, <?= $shopLng ?>, 'Shop')">
                    <i class="fa-solid fa-shop"></i> To Shop
                </button>
                <button class="btn-nav-compact" onclick="openNavOptions(<?= $userLat ?>, <?= $userLng ?>, 'Customer')">
                    <i class="fa-solid fa-house"></i> To Customer
                </button>
            </div>

            <!-- Action Buttons & PIN -->
            <div class="action-row" id="action-row-container">
                <div id="wait-msg" style="display: none; flex: 1; text-align: center; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.8); padding: 8px; background: rgba(255,255,255,0.15); border-radius: 10px; backdrop-filter: blur(4px);"><i class="fa-solid fa-circle-notch fa-spin"></i> Waiting for shop...</div>
                
                <div id="final-actions" style="display: none; flex: 1; gap: 8px;">
                    <button id="btn-status-onway" class="btn-status btn-onway" onclick="confirmStatus('On Way')"><i class="fa-solid fa-motorcycle"></i> On Way</button>
                    <button id="btn-status-delivered" class="btn-status btn-delivered" onclick="confirmStatus('Delivered')"><i class="fa-solid fa-check-double"></i> Delivered</button>
                </div>

                <?php if($isCancelled): ?>
                    <div class="pin-badge cancel"><i class="fa-solid fa-key"></i> <?= $expectedCancelPin ?></div>
                <?php elseif($stepIndex < 4): ?>
                    <div class="pin-badge"><i class="fa-solid fa-key"></i> <?= $expectedPickupPin ?></div>
                <?php endif; ?>
            </div>
        </div>


        <!-- Chat History -->
        <div class="chat-view" id="chat-list">
            <!-- Order Items (System First Message) -->
            <div style="display:flex; justify-content:center; align-self:stretch; width:100%; margin:16px 0; padding:0 4px;">
                <div class="order-summary-card">
                    <div class="summary-header">
                        <i class="fa-solid fa-receipt"></i> Order Summary
                    </div>
                    <table class="items-list">
                        <tbody>
                            <?php 
                                $items = explode('|', $orderDetailsStr);
                                foreach($items as $item): 
                                    $parts = explode('x', $item);
                                    $name = trim($parts[0] ?? 'Unknown');
                                    $qty = trim($parts[1] ?? '1');
                                    if(empty($name)) continue;
                            ?>
                            <tr>
                                <td><span class="item-qty">x<?= htmlspecialchars($qty) ?></span> <?= htmlspecialchars($name) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="summary-footer">
                        <span class="total-label">TOTAL TO PAY</span>
                        <span class="total-value"><?= $totalPrice ?> MAD</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Input -->
        <div class="bottom-bar" id="chat-bottom-bar">
            <div class="input-group">
                <button class="btn-plus" onclick="toggleMenu()"><i class="fa-solid fa-plus"></i></button>
                <div class="menu-overlay" id="attach-menu">
                    <div class="menu-item" onclick="triggerPhoto()"><i class="fa-regular fa-image"></i> Photo Proof</div>
                    <div class="menu-item" onclick="sendCurrentLocation()"><i class="fa-solid fa-location-dot"></i> Share Location</div>
                    <hr style="border: none; border-top: 1px solid var(--border); margin: 4px 0;">
                    <div class="menu-item" style="color: var(--danger);" onclick="openCancelModal()"><i class="fa-solid fa-ban"></i> Cancel Order</div>
                </div>
                <div class="chat-input-wrapper">
                    <input type="text" id="chat-input" class="chat-input" dir="auto" placeholder="Type a message..." onkeypress="if(event.key==='Enter')sendMessage()">
                    <button class="btn-send" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <!-- Chat Closed Banner (shown when delivered/done/cancelled) -->
        <div class="chat-closed-banner" id="chat-closed-banner" style="flex-direction: column;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-lock" id="chat-closed-icon"></i>
                <span id="chat-closed-text">This order is closed. No new messages.</span>
            </div>
            <div id="return-pin-box" style="display: none; margin-top: 12px; background: rgba(255,255,255,0.9); padding: 12px; border-radius: 12px; border: 1px solid rgba(220,38,38,0.2); width: 100%; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                <div style="font-size: 11px; font-weight: 800; color: #dc2626; text-transform: uppercase; margin-bottom: 4px;">Return PIN for Shop</div>
                <div style="font-size: 26px; font-weight: 900; letter-spacing: 6px; color: #0f172a;"><?= $expectedCancelPin ?></div>
            </div>
        </div>
    </div> <!-- END OF SIDEBAR -->

    <div id="map-container">
        <div id="map"></div>
        <div class="floating-map-ctrl" id="mobile-map-ui">
            <button class="map-btn" onclick="toggleMap()"><i class="fa-solid fa-chevron-down"></i> Chat</button>
            <button class="map-btn map-btn-primary" onclick="openNavOptions(<?= $userLat ?>, <?= $userLng ?>, 'Customer')"><i class="fa-solid fa-compass"></i> Navigate</button>
        </div>
    </div>

    <!-- Modals -->
    <!-- Cancel Order Modal -->
    <div class="modal-overlay" id="cancel-modal">
        <div class="modal-card" style="max-width: 340px;">
            <div style="width: 56px; height: 56px; background: #fee2e2; color: #dc2626; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 16px; box-shadow: 0 6px 16px rgba(220,38,38,0.2);">
                <i class="fa-solid fa-ban"></i>
            </div>
            <h3 style="margin-bottom: 6px; font-size: 18px; font-weight: 800; color: #0f172a; text-align: center;">Cancel Order</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.4; text-align: center;">Are you sure you want to cancel this order? This action cannot be undone and will notify the customer and seller immediately.</p>
            
            <button onclick="confirmCancelOrder()" style="width: 100%; height: 48px; border-radius: 14px; background: #ef4444; color: #fff; border: none; font-size: 15px; font-weight: 800; cursor: pointer; margin-bottom: 10px; font-family: 'Outfit', sans-serif; transition: 0.2s; box-shadow: 0 4px 14px rgba(239,68,68,0.3);">
                <i class="fa-solid fa-triangle-exclamation"></i> Yes, Cancel Order
            </button>
            <button onclick="closeModal()" style="width: 100%; height: 42px; border-radius: 12px; background: #f1f5f9; color: #64748b; border: none; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'Outfit', sans-serif;">
                No, Go Back
            </button>
        </div>
    </div>

    <div class="modal-overlay" id="nav-modal">
        <div class="modal-card">
            <h3 style="margin-bottom: 16px;">Navigate to <span id="nav-target-name"></span></h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button class="map-btn map-btn-primary" style="width: 100%; height: 48px; border-radius: 14px;" onclick="navigateApp('google')">Google Maps</button>
                <button class="map-btn" style="width: 100%; height: 48px; border-radius: 14px; background: #33ccff; color: #000;" onclick="navigateApp('waze')">Waze</button>
            </div>
            <button class="map-btn" style="width: 100%; margin-top: 12px; background: #f1f5f9; border: none;" onclick="closeModal()">Close</button>
        </div>
    </div>

    <!-- PIN Entry Modal -->
    <div class="modal-overlay" id="pin-modal">
        <div class="modal-card" style="max-width: 340px;">
            <div style="width: 56px; height: 56px; background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 16px; box-shadow: 0 6px 16px rgba(99,102,241,0.3);">
                <i class="fa-solid fa-shield-check"></i>
            </div>
            <h3 style="margin-bottom: 6px; font-size: 18px; font-weight: 800; color: #0f172a;">Delivery PIN</h3>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.4;">Ask the customer for their 4-digit security code to complete the delivery.</p>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px;">
                <input type="tel" maxlength="1" id="p1" onkeyup="moveFocus(this, 'p2')"
                    style="width: 100%; height: 58px; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; text-align: center; font-size: 24px; font-weight: 900; color: #6366f1; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.background='#fff'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
                <input type="tel" maxlength="1" id="p2" onkeyup="moveFocus(this, 'p3')"
                    style="width: 100%; height: 58px; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; text-align: center; font-size: 24px; font-weight: 900; color: #6366f1; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.background='#fff'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
                <input type="tel" maxlength="1" id="p3" onkeyup="moveFocus(this, 'p4')"
                    style="width: 100%; height: 58px; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; text-align: center; font-size: 24px; font-weight: 900; color: #6366f1; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.background='#fff'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
                <input type="tel" maxlength="1" id="p4" onkeyup="verifyPin()"
                    style="width: 100%; height: 58px; border-radius: 14px; border: 2px solid #e2e8f0; background: #f8fafc; text-align: center; font-size: 24px; font-weight: 900; color: #6366f1; outline: none; transition: 0.2s; font-family: 'Outfit', sans-serif;"
                    onfocus="this.style.borderColor='#6366f1'; this.style.background='#fff'; this.style.boxShadow='0 0 0 3px rgba(99,102,241,0.15)'"
                    onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'; this.style.boxShadow='none'">
            </div>

            <div id="pin-error" style="color: #ef4444; font-size: 12px; font-weight: 700; margin-bottom: 14px; display: none; background: #fef2f2; padding: 8px 12px; border-radius: 8px; border: 1px solid #fecaca;">
                <i class="fa-solid fa-circle-exclamation"></i> Incorrect PIN. Please try again.
            </div>

            <button onclick="verifyPin()" style="width: 100%; height: 48px; border-radius: 14px; background: linear-gradient(135deg, #6366f1, #818cf8); color: #fff; border: none; font-size: 15px; font-weight: 800; cursor: pointer; margin-bottom: 10px; font-family: 'Outfit', sans-serif; box-shadow: 0 4px 14px rgba(99,102,241,0.3); transition: 0.2s;">
                <i class="fa-solid fa-check"></i> Confirm Delivery
            </button>
            <button onclick="closeModal()" style="width: 100%; height: 42px; border-radius: 12px; background: #f1f5f9; color: #64748b; border: none; font-size: 14px; font-weight: 700; cursor: pointer; font-family: 'Outfit', sans-serif;">
                Cancel
            </button>
        </div>
    </div>

    <!-- Alert Modal for Cancellation Flow -->
    <div class="modal-overlay" id="custom-alert-modal">
        <div class="modal-card" style="max-width: 340px;">
            <div id="custom-alert-icon" style="width: 56px; height: 56px; background: #fee2e2; color: #dc2626; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin: 0 auto 16px; box-shadow: 0 6px 16px rgba(220,38,38,0.2);">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <h3 id="custom-alert-title" style="margin-bottom: 6px; font-size: 18px; font-weight: 800; color: #0f172a; text-align: center;">Alert</h3>
            <p id="custom-alert-msg" style="font-size: 13px; color: #64748b; margin-bottom: 24px; line-height: 1.4; text-align: center;">Message</p>
            
            <button id="custom-alert-btn" onclick="closeModal()" style="width: 100%; height: 48px; border-radius: 14px; background: #0f172a; color: #fff; border: none; font-size: 15px; font-weight: 800; cursor: pointer; margin-bottom: 10px; font-family: 'Outfit', sans-serif; transition: 0.2s; box-shadow: 0 4px 14px rgba(15,23,42,0.3);">
                Okay
            </button>
        </div>
    </div>

    <input type="file" id="photo-input" accept="image/*" style="display:none;" onchange="uploadPhoto(this)">

    <!-- Core Scripts -->
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.10/firebase-database-compat.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <script>
        const firebaseConfig = { databaseURL: "https://jibler-37339-default-rtdb.firebaseio.com" };
        firebase.initializeApp(firebaseConfig);
        const db = firebase.database();
        const chatRef = db.ref('Messages/<?= $orderId ?>');
        const trackerRef = db.ref('OrderTrackers/<?= $orderId ?>/current_status');

        let currentStatus = '<?= $rawStatus ?>';
        let stepIndex = <?= $stepIndex ?>;

        // Map setup
        const map = L.map('map', { zoomControl: false }).setView([<?= $shopLat ?>, <?= $shopLng ?>], 14);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png').addTo(map);

        const shopIcon = L.divIcon({ className: '', html: `<div style="background: #FFD700; width: 32px; height: 32px; border-radius: 50%; border: 3px solid #fff; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; color: #000;"><i class="fa-solid fa-shop" style="font-size: 12px;"></i></div>`, iconSize: [32, 32], iconAnchor: [16, 32] });
        const userIcon = L.divIcon({ className: '', html: `<div style="background: var(--primary); width: 32px; height: 32px; border-radius: 50%; border: 3px solid #fff; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; color: #fff;"><i class="fa-solid fa-house" style="font-size: 12px;"></i></div>`, iconSize: [32, 32], iconAnchor: [16, 32] });
        const driverIcon = L.divIcon({ className: '', html: `<div style="background: var(--accent); width: 32px; height: 32px; border-radius: 50%; border: 3px solid #fff; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; color: #fff;"><i class="fa-solid fa-motorcycle" style="font-size: 12px;"></i></div>`, iconSize: [32, 32], iconAnchor: [16, 32] });

        L.marker([<?= $shopLat ?>, <?= $shopLng ?>], {icon: shopIcon}).addTo(map);
        L.marker([<?= $userLat ?>, <?= $userLng ?>], {icon: userIcon}).addTo(map);
        const driverMarker = L.marker([<?= $shopLat ?>, <?= $shopLng ?>], {icon: driverIcon}).addTo(map);

        function toggleMap() {
            document.body.classList.toggle('show-map');
            document.getElementById('mobile-map-ui').style.display = document.body.classList.contains('show-map') ? 'flex' : 'none';
            setTimeout(() => map.invalidateSize(), 400);
        }

        // Logic
        function sendMessage() {
            const input = document.getElementById('chat-input');
            const msg = input.value.trim();
            if (!msg) return;
            chatRef.push({ CreatedTime: Date.now(), MessageType: 'words', message: msg, sender: 'driver', id: '<?= $actualDriverId ?>' });
            input.value = '';
        }

        function triggerPhoto() { toggleMenu(); document.getElementById('photo-input').click(); }
        function uploadPhoto(input) {
            if (!input.files || !input.files[0]) {
                if (typeof isCancellingFlow !== 'undefined' && isCancellingFlow) { 
                    alert('Proof photo is required to cancel!'); 
                    isCancellingFlow = false; 
                }
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                const fd = new FormData(); fd.append("photochat", e.target.result.split(',')[1]);
                fetch("uploadImageChat.php", { method: 'POST', body: fd }).then(r => r.json()).then(data => {
                    if (data.success) {
                        chatRef.push({ CreatedTime: Date.now(), MessageType: 'Image', message: data.data, sender: 'driver', id: '<?= $actualDriverId ?>' });
                        if (typeof isCancellingFlow !== 'undefined' && isCancellingFlow) {
                            updateStatus('Cancelled');
                            isCancellingFlow = false;
                        }
                    } else if (typeof isCancellingFlow !== 'undefined' && isCancellingFlow) {
                        alert('Upload failed. Try again.');
                        isCancellingFlow = false;
                    }
                });
            };
            reader.readAsDataURL(input.files[0]);
        }

        function sendCurrentLocation() {
            toggleMenu();
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition(pos => {
                chatRef.push({ CreatedTime: Date.now(), MessageType: 'Location', message: `Live Location: ${pos.coords.latitude},${pos.coords.longitude}`, lat: pos.coords.latitude, lng: pos.coords.longitude, sender: 'driver', id: '<?= $actualDriverId ?>' });
            });
        }

        chatRef.on('child_added', snap => {
            const data = snap.val();
            const list = document.getElementById('chat-list');
            const isMe = data.sender === 'driver';
            
            let content = '';
            
            // --- STATUS UPDATE CARD (new format) ---
            if (data.MessageType === 'StatusUpdate') {
                const emoji = data.statusEmoji || '📦';
                const label = data.statusLabel || data.message || 'Status Updated';
                const color = data.statusColor || '#6366f1';
                const time  = data.CreatedTime ? new Date(data.CreatedTime).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'}) : '';
                list.insertAdjacentHTML('beforeend', `
                    <div style="display:flex; justify-content:center; align-self:stretch; width:100%; margin:8px 0; padding:0 4px;">
                        <div style="display:flex;align-items:center;gap:10px;background:#fff;border:1px solid ${color}22;border-left:3px solid ${color};border-radius:14px;padding:12px 16px;width:100%;box-shadow:0 2px 12px ${color}14;">
                            <div style="font-size:24px;line-height:1;flex-shrink:0;">${emoji}</div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:${color};letter-spacing:0.6px;margin-bottom:3px;">Order Update</div>
                                <div style="font-size:13px;font-weight:700;color:#0f172a;">${label}</div>
                                ${time ? `<div style="font-size:10px;color:#94a3b8;margin-top:2px;">${time}</div>` : ''}
                            </div>
                        </div>
                    </div>`);
                list.scrollTop = list.scrollHeight;
                return;
            }

            // --- SYSTEM CARDS (Order Summary, etc) ---
            if (data.message && String(data.message).includes('order-summary-card')) {
                list.insertAdjacentHTML('beforeend', `
                    <div style="display:flex; justify-content:center; align-self:stretch; width:100%; margin:16px 0; padding:0 4px;">
                        ${data.message}
                    </div>`);
                list.scrollTop = list.scrollHeight;
                return;
            }

            // --- LEGACY STATUS MESSAGE (backward compat) ---
            if (data.message && String(data.message).startsWith('Order Status Updated:')) {
                const label = String(data.message).replace('Order Status Updated:', '').trim();
                list.insertAdjacentHTML('beforeend', `
                    <div style="display:flex; justify-content:center; align-self:stretch; width:100%; margin:8px 0; padding:0 4px;">
                        <div style="display:flex;align-items:center;gap:10px;background:#fff;border:1px solid #6366f122;border-left:3px solid #6366f1;border-radius:14px;padding:12px 16px;width:100%;box-shadow:0 2px 12px #6366f114;">
                            <div style="font-size:24px;line-height:1;flex-shrink:0;">📦</div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:#6366f1;letter-spacing:0.6px;margin-bottom:3px;">Order Update</div>
                                <div style="font-size:13px;font-weight:700;color:#0f172a;">${label}</div>
                            </div>
                        </div>
                    </div>`);
                list.scrollTop = list.scrollHeight;
                return;
            }

            // --- REGULAR MESSAGES ---
            if (data.MessageType === 'Image') {
                content = `<img src="${data.message}" style="width: 100%; max-width: 200px; border-radius: 12px; margin-top: 4px; box-shadow: var(--shadow-sm);">`;
            } else if (data.MessageType === 'Location') {
                let lat = data.lat || null; let lng = data.lng || null;
                if(!lat || !lng) { const p = String(data.message).match(/(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)/); if(p){ lat=p[1]; lng=p[2]; } }
                if(lat && lng) {
                    content = `<div class="info-card" onclick="openNavOptions(${lat}, ${lng}, 'Shared Pin')" style="width:100%;max-width:240px;background:#f0f9ff;border-color:var(--accent);margin-top:4px;">
                        <div class="card-content">
                            <div class="card-label" style="color:var(--accent);">Shared Pin</div>
                            <div class="card-main" style="font-size:11px;">Tap to navigate</div>
                        </div>
                        <i class="fa-solid fa-location-arrow" style="color:var(--accent);"></i>
                    </div>`;
                } else {
                    content = `<div class="bubble"><i class="fa-solid fa-location-dot"></i> ${data.message}</div>`;
                }
            } else {
                content = `<div class="bubble">${data.message}</div>`;
            }

            let avatar = isMe ? '<?= $driverImgUrl ?>' : (data.sender==='seller'||data.sender==='shop'||data.sender==='vendor' ? '<?= $shopImgUrl ?>' : '<?= $userImgUrl ?>');
            let name = isMe ? 'You' : (data.sender==='seller'||data.sender==='shop'||data.sender==='vendor' ? '<?= addslashes($shopName) ?>' : '<?= addslashes($userName) ?>');

            const html = `<div class="msg-row ${isMe?'me':''}">
                <img src="${avatar}" class="msg-avatar">
                <div class="msg-body">
                    <span class="msg-sender">${name}</span>
                    ${content}
                </div>
            </div>`;
            list.insertAdjacentHTML('beforeend', html);
            list.scrollTop = list.scrollHeight;
        });


        // UI Helpers
        let isCancellingFlow = false;
        function toggleMenu() { document.getElementById('attach-menu').classList.toggle('active'); }
        function openCancelModal() { toggleMenu(); document.getElementById('cancel-modal').classList.add('active'); }
        
        function showCustomAlert(title, msg, iconClass, iconBg, iconColor, callback) {
            document.getElementById('custom-alert-title').innerText = title;
            document.getElementById('custom-alert-msg').innerText = msg;
            const iconDiv = document.getElementById('custom-alert-icon');
            iconDiv.innerHTML = `<i class="${iconClass}"></i>`;
            iconDiv.style.background = iconBg;
            iconDiv.style.color = iconColor;
            const btn = document.getElementById('custom-alert-btn');
            btn.onclick = () => {
                closeModal();
                if (callback) callback();
            };
            document.getElementById('custom-alert-modal').classList.add('active');
        }

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // metres
            const p1 = lat1 * Math.PI/180;
            const p2 = lat2 * Math.PI/180;
            const dp = (lat2-lat1) * Math.PI/180;
            const dl = (lon2-lon1) * Math.PI/180;
            const a = Math.sin(dp/2) * Math.sin(dp/2) + Math.cos(p1) * Math.cos(p2) * Math.sin(dl/2) * Math.sin(dl/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        function confirmCancelOrder() {
            closeModal();
            isCancellingFlow = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const driverLat = pos.coords.latitude;
                    const driverLng = pos.coords.longitude;
                    const customerLat = <?= $userLat ?>;
                    const customerLng = <?= $userLng ?>;
                    
                    const dist = getDistance(driverLat, driverLng, customerLat, customerLng);
                    
                    if (dist > 500) { // 500 meters threshold
                        isCancellingFlow = false;
                        showCustomAlert(
                            "Too Far From Customer", 
                            `You are approximately ${Math.round(dist)} meters away from the customer. You must be at the customer's location to cancel the order!`, 
                            "fa-solid fa-location-dot", "#fee2e2", "#dc2626"
                        );
                        return;
                    }

                    chatRef.push({ CreatedTime: Date.now(), MessageType: 'Location', message: `Cancellation Location: ${driverLat},${driverLng}`, lat: driverLat, lng: driverLng, sender: 'driver', id: '<?= $actualDriverId ?>' });
                    
                    showCustomAlert(
                        "Upload Proof", 
                        "Location verified! Please upload a photo of the proof to complete cancellation.", 
                        "fa-solid fa-camera", "#e0e7ff", "#4f46e5", 
                        () => { document.getElementById('photo-input').click(); }
                    );
                }, err => {
                    isCancellingFlow = false;
                    showCustomAlert("Location Required", "You must enable location to cancel the order!", "fa-solid fa-location-crosshairs", "#fee2e2", "#dc2626");
                });
            } else {
                isCancellingFlow = false;
                showCustomAlert("Error", "Location not supported. Cannot cancel.", "fa-solid fa-triangle-exclamation", "#fee2e2", "#dc2626");
            }
        }
        function closeModal() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active')); }
        let nLat, nLng;
        function openNavOptions(lat, lng, label) { nLat=lat; nLng=lng; document.getElementById('nav-target-name').innerText=label; document.getElementById('nav-modal').classList.add('active'); }
        function navigateApp(app) {
            const url = app==='google' ? `https://www.google.com/maps/dir/?api=1&destination=${nLat},${nLng}` : `https://waze.com/ul?ll=${nLat},${nLng}&navigate=yes`;
            window.open(url, '_blank'); closeModal();
        }

        function confirmStatus(status) {
            if(status==='Delivered') { document.getElementById('pin-modal').classList.add('active'); return; }
            updateStatus(status);
        }

        function verifyPin() {
            const pin = document.getElementById('p1').value + document.getElementById('p2').value + document.getElementById('p3').value + document.getElementById('p4').value;
            if(pin === '<?= $fourDigitPin ?>') { updateStatus('Delivered'); closeModal(); } 
            else { document.getElementById('pin-error').style.display='block'; document.querySelectorAll('.pin-input').forEach(i=>i.value=''); document.getElementById('p1').focus(); }
        }

        function updateStatus(s) {
            const fd = new FormData(); fd.append('ajax_status_update', 1); fd.append('new_status', s); fd.append('order_id', '<?= $orderId ?>');
            fetch('driver_chat.php', { method: 'POST', body: fd }).then(() => location.reload());
        }

        function syncUI(s) {
            const r = String(s || '').toLowerCase().trim();
            const btnRow = document.getElementById('final-actions');
            const waitMsg = document.getElementById('wait-msg');
            const inputBar = document.getElementById('chat-bottom-bar');
            const closedBanner = document.getElementById('chat-closed-banner');

            const shopProcessing = ['waiting', 'order placed', 'placed', 'confirmed', 'accepted', 'accept', 'yes', 'doing', 'heading to shop', 'prepared', 'preparing', 'processed', 'order processed'].includes(r);
            const pickupReady = ['order pickup', 'pickup', 'picked', 'picked up', 'ready', 'arrived at shop', 'on way', 'on the way', 'found', 'come to take it', 'arrived'].includes(r);
            const isDelivered = ['done', 'delivered', 'finish', 'rated', 'order delivered'].includes(r);
            const isCancelled = ['cancelled', 'canceled', 'returned', 'refunded'].includes(r);

            // Toggle input vs closed banner
            if (isDelivered) {
                if (inputBar) inputBar.style.display = 'none';
                if (closedBanner) {
                    closedBanner.style.display = 'flex';
                    const textEl = document.getElementById('chat-closed-text');
                    const iconEl = document.getElementById('chat-closed-icon');
                    const pinBox = document.getElementById('return-pin-box');
                    
                    if (textEl) textEl.textContent = '🎉 Order delivered. Chat is closed.';
                    if (iconEl) iconEl.style.color = 'var(--success)';
                    if (pinBox) pinBox.style.display = 'none';
                }
                if (btnRow) btnRow.style.display = 'none';
                if (waitMsg) waitMsg.style.display = 'none';
            } else if (isCancelled) {
                if (inputBar) inputBar.style.display = 'none';
                if (closedBanner) {
                    closedBanner.style.display = 'flex';
                    const textEl = document.getElementById('chat-closed-text');
                    const iconEl = document.getElementById('chat-closed-icon');
                    const pinBox = document.getElementById('return-pin-box');
                    
                    if (textEl) textEl.textContent = '🚫 Order cancelled. Please return to shop.';
                    if (iconEl) iconEl.style.color = 'var(--danger)';
                    if (pinBox) pinBox.style.display = 'block';
                }
                if (btnRow) btnRow.style.display = 'none';
                if (waitMsg) waitMsg.style.display = 'none';
            } else {
                if (inputBar) inputBar.style.display = 'flex';
                if (closedBanner) closedBanner.style.display = 'none';
                if (pickupReady) {
                    if (waitMsg) waitMsg.style.display = 'none';
                    if (btnRow) { btnRow.style.display = 'flex'; }
                    if (['on way', 'on the way', 'found', 'come to take it', 'arrived'].includes(r)) {
                        document.getElementById('btn-status-onway')?.classList.add('active-state');
                    }
                } else if (shopProcessing) {
                    if (btnRow) btnRow.style.display = 'none';
                    if (waitMsg) { waitMsg.style.display = 'block'; waitMsg.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Waiting for shop to confirm...'; }
                } else {
                    if (btnRow) btnRow.style.display = 'none';
                    if (waitMsg) waitMsg.style.display = 'none';
                }
            }
        }

        // Real-time Firebase status listener — updates buttons instantly when shop changes order
        trackerRef.on('value', snap => {
            const status = snap.val();
            if (status) {
                currentStatus = status;
                syncUI(status);

                // Update status pill text
                const pill = document.querySelector('.status-pill span');
                if (pill) {
                    const labels = {
                        'order pickup': 'Ready', 'pickup': 'Ready', 'ready': 'Ready', 'arrived at shop': 'Ready',
                        'on way': 'On Way', 'on the way': 'On Way', 'found': 'On Way',
                        'done': 'Delivered', 'delivered': 'Delivered',
                        'cancelled': 'Cancelled', 'canceled': 'Cancelled',
                        'prepared': 'Preparing', 'preparing': 'Preparing',
                        'accepted': 'Heading', 'heading to shop': 'Heading',
                    };
                    const r = String(status).toLowerCase().trim();
                    pill.textContent = labels[r] || status;
                }

                // Animate progress bar
                const barMap = { 'accepted': 33, 'heading to shop': 33, 'prepared': 50, 'preparing': 50, 'order pickup': 66, 'pickup': 66, 'ready': 66, 'on way': 83, 'on the way': 83, 'done': 100, 'delivered': 100, 'cancelled': 100 };
                const r2 = String(status).toLowerCase().trim();
                const bar = document.getElementById('prog-bar');
                if (bar && barMap[r2] !== undefined) bar.style.width = barMap[r2] + '%';
            }
        });

        syncUI('<?= $rawStatus ?>');
        function moveFocus(el, nextId) { if(el.value.length === 1) document.getElementById(nextId)?.focus(); }
    </script>
</body>
</html>
