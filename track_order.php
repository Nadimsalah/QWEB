<?php
require_once 'conn.php';
$orderId = isset($_GET['orderId']) ? mysqli_real_escape_string($con, $_GET['orderId']) : '0';
$totalPrice = isset($_GET['tot']) ? htmlspecialchars($_GET['tot']) : '0';

// Default mock values
$shopName = 'QOON Shop';
$shopImgUrl = "https://ui-avatars.com/api/?name=QOON+Shop&background=FFD700&color=000";
$driverName = 'Ahmed R.';
$driverImgUrl = "https://randomuser.me/api/portraits/men/32.jpg";
$fourDigitPin = rand(1000, 9999);

if ($con && $orderId !== '0') {
    $res = $con->query("SELECT o.*, d.FName, d.LName, d.PersonalPhoto as DriverImg, s.ShopName as SName, s.ShopLogo as SPhoto 
                        FROM Orders o 
                        LEFT JOIN Drivers d ON o.DelvryId = d.DriverID 
                        LEFT JOIN Shops s ON o.ShopID = s.ShopID 
                        WHERE o.OrderID = '$orderId'");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        
        $shopName = !empty($row['SName']) ? $row['SName'] : (!empty($row['DestinationName']) ? $row['DestinationName'] : 'QOON Shop');
        if (!empty($row['SPhoto'])) {
            // Check if ShopLogo is valid string
            $shopImgUrl = $row['SPhoto'];
        } else {
            $shopImgUrl = "https://ui-avatars.com/api/?name=".urlencode($shopName)."&background=FFD700&color=000";
        }
        
        if (!empty($row['FName'])) {
            $lName = $row['LName'] ?? '';
            $driverName = trim($row['FName'] . ' ' . mb_substr($lName, 0, 1) . '.');
        } else {
            $driverName = 'Waiting for Driver';
        }
        if (!empty($row['DriverImg']) && strpos($row['DriverImg'], 'http') !== false) {
            $driverImgUrl = $row['DriverImg'];
        } else if (!empty($row['FName'])) {
            $driverImgUrl = "https://ui-avatars.com/api/?name=".urlencode($driverName)."&background=random";
        }
        
        if (!empty($row['FourDigit'])) {
            $fourDigitPin = $row['FourDigit'];
        }
        
        $actualDriverId = $row['DelvryId'] ?? '0';
        $actualShopId = $row['ShopID'] ?? '0';
        
        $rawStatus = $row['OrderState'] ?? 'waiting';
        $pMethod = $row['Method'] ?? 'CASH';
        if($pMethod == 'CASH') $paymentMethodText = 'Cash on Delivery';
        else if($pMethod == 'CARD') $paymentMethodText = 'Bank Card';
        else $paymentMethodText = 'QOON Pay';
        
        $userFees = floatval($row['PlatformFee'] ?? 0);
        
        $productsFee = floatval($row['OrderPriceFromShop'] ?? 0);
        $deliveryFee = floatval($row['OrderPrice'] ?? 0);

        $tPrice = $productsFee + $deliveryFee + $userFees;
        if ($tPrice > 0) {
            $totalPrice = $tPrice;
        }
        
        $orderDetailsStr = $row['OrderDetails'] ?? '';
        
        $shopLat = isset($row['DestnationLat']) && is_numeric($row['DestnationLat']) ? $row['DestnationLat'] : 33.5750;
        $shopLng = isset($row['DestnationLongt']) && is_numeric($row['DestnationLongt']) ? $row['DestnationLongt'] : -7.5850;
        
        $userLat = isset($row['UserLat']) && is_numeric($row['UserLat']) ? $row['UserLat'] : 33.5650;
        $userLng = isset($row['UserLongt']) && is_numeric($row['UserLongt']) ? $row['UserLongt'] : -7.5950;
    }
}
if(isset($_GET['ajax_status'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $rawStatus ?? 'waiting']);
    exit;
}

$orderStatusText = 'Order placed';
$stepIndex = 1;

if (isset($rawStatus)) {
    $r = strtolower(trim($rawStatus));
    
    // Final States
    if (in_array($r, ['cancelled', 'canceled'])) {
        $orderStatusText = 'Cancelled';
        $stepIndex = 6;
    } else if (in_array($r, ['done', 'finish', 'rated', 'order delivered', 'delivered'])) {
        $orderStatusText = 'Order delivered';
        $stepIndex = 6;
    } 
    // Driver Heading to User
    else if (in_array($r, ['doing', 'found', 'come to take it', 'on way', 'on the way'])) {
        $orderStatusText = 'Come to take it';
        $stepIndex = 5;
    } 
    // Order Picked Up
    else if (in_array($r, ['order pickup', 'pickup', 'picked', 'picked up'])) {
        $orderStatusText = 'Order pickup';
        $stepIndex = 4;
    } 
    // Order Processed / Prepared
    else if (in_array($r, ['prepared', 'order processed', 'processed']) || (isset($row['IsPrepared']) && $row['IsPrepared'] == 'YES')) {
        $orderStatusText = 'Order processed';
        $stepIndex = 3;
    } 
    // Order Confirmed
    else if (in_array($r, ['accept', 'yes', 'order confirmed', 'confirmed'])) {
        $orderStatusText = 'Order confirmed';
        $stepIndex = 2;
    } 
    // Default / Placed
    else {
        $orderStatusText = 'Order placed';
        $stepIndex = 1;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order & Group Chat - QOON</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <style>
        :root {
            --bg-color: #050505;
            --text-main: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.6);
            --glass-bg: rgba(255, 255, 255, 0.08);
            --glass-border: rgba(255, 255, 255, 0.12);
            --accent-glow-1: #4a25e1;
            --accent-glow-2: #2cb5e8;
            --accent-glow-3: #9b2df1;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body { 
            background-color: var(--bg-color); 
            height: 100vh; 
            width: 100vw; 
            display: flex;
            overflow: hidden;
            flex-direction: row;
        }

        /* --- Right Area (Map) - Desktop --- */
        #map-container { flex: 1; height: 100vh; position: relative; background: #000; }
        #map { position: absolute; inset: 0; z-index: 1; }

        /* --- Left Sidebar (Chat & Tracker) - Desktop --- */
        #sidebar {
            width: 450px;
            height: 100vh;
            background: rgba(15, 15, 15, 0.85);
            backdrop-filter: blur(40px);
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: none;
            position: relative;
        }

        /* Top Header */
        .top-bar { 
            padding: 20px; 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
            display: flex; align-items: center; justify-content: space-between;
        }
        .back-btn { 
            width: 40px; height: 40px; border-radius: 50%; 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            color: #fff; display: flex; center; justify-content: center; align-items: center; 
            cursor: pointer; text-decoration: none; transition: 0.2s; font-size: 16px; flex-shrink: 0;
        }
        .order-status { font-size: 13px; color: var(--accent-glow-2); font-weight: 600; display:flex; align-items: center; gap: 6px;}

        /* Tracker Timeline Snippet */
        .tracker-board {
            padding: 20px;
            background: rgba(0,0,0,0.3);
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .progress-bar {
            height: 6px; background: rgba(255,255,255,0.1); border-radius: 99px; overflow: hidden; margin-bottom: 12px;
        }
        .progress-fill {
            height: 100%; width: 33%; background: linear-gradient(90deg, var(--accent-glow-1), var(--accent-glow-2));
            border-radius: 99px; transition: 1s ease-in-out;
        }
        .progress-fill.cancelled { background: #ff4757 !important; box-shadow: 0 0 15px rgba(255, 71, 87, 0.4); }
        .tracker-steps { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.4); text-transform: uppercase;}
        .step.active { color: #fff; }

        /* Group Chat Area */
        .chat-area { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 16px; scroll-behavior: smooth;}
        .chat-area::-webkit-scrollbar { width: 6px; }
        .chat-area::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        .chat-msg { display: flex; gap: 12px; max-width: 90%; }
        .chat-msg.me { align-self: flex-end; flex-direction: row-reverse; }
        
        .chat-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1); flex-shrink: 0;}
        .chat-content { display: flex; flex-direction: column; gap: 4px; }
        .chat-msg.me .chat-content { align-items: flex-end; }
        
        .chat-name { font-size: 11px; font-weight: 700; color: rgba(255,255,255,0.5); display: flex; gap: 6px; align-items: center;}
        
        .chat-bubble { 
            padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.4; color: #fff;
            background: var(--glass-bg); border: 1px solid var(--glass-border); border-top-left-radius: 4px;
        }
        .chat-msg.me .chat-bubble {
            background: linear-gradient(135deg, rgba(74, 37, 225, 0.6), rgba(155, 45, 241, 0.6));
            border-color: rgba(155, 45, 241, 0.5);
            border-top-left-radius: 16px; border-top-right-radius: 4px;
        }
        .chat-msg.driver .chat-name { color: var(--accent-glow-2); }
        .chat-msg.shop .chat-name { color: #FFD700; }

        /* Chat Input Field & Attachments */
        .chat-input-wrapper { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.05); background: rgba(0,0,0,0.5); display: flex; gap: 12px; align-items: center;}
        
        .attach-btn { width: 48px; height: 48px; border-radius: 50%; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 18px; cursor: pointer; transition: 0.2s; flex-shrink: 0;}
        .attach-btn:active { transform: scale(0.9); }
        .attach-btn:hover { color: var(--accent-glow-2); border-color: var(--accent-glow-2); background: rgba(44, 181, 232, 0.1); }

        .chat-input { flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 14px 20px; border-radius: 99px; color: #fff; font-size: 14px; outline: none; transition: 0.2s;}
        .chat-input:focus { border-color: var(--accent-glow-3); background: rgba(255,255,255,0.1);}
        .send-btn { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--accent-glow-1), var(--accent-glow-3)); color: #fff; border: none; display: flex; align-items: center; justify-content: center; font-size: 16px; cursor: pointer; transition: 0.2s; flex-shrink: 0;}
        .send-btn:active { transform: scale(0.9); }

        /* Shared Location Bubble */
        .location-bubble { padding: 0 !important; overflow: hidden; width: 220px; border-radius: 16px; background: var(--glass-bg); border: 1px solid var(--glass-border); border-top-right-radius: 4px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); position: relative;}
        .loc-map-wrapper { position: relative; width: 100%; height: 120px; }
        .loc-map-bg { width: 100%; height: 100%; background: url('https://b.basemaps.cartocdn.com/dark_all/16/31336/26601.png') center/cover; opacity: 0.9; }
        .loc-map-bg-icon { position: absolute; top:50%; left:50%; transform:translate(-50%,-100%); color: var(--accent-glow-3); font-size: 36px; z-index: 2; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5)); }
        .loc-details { padding: 12px 14px; background: rgba(10,10,10,0.85); backdrop-filter: blur(10px);}
        .loc-title { font-weight: 800; font-size: 13px; color: #fff; margin-bottom: 2px;}
        .loc-sub { font-size: 11px; color: var(--accent-glow-2); }

        /* Payment Summary Bar */
        .payment-summary-bar {
            padding: 12px 20px;
            background: rgba(46, 204, 113, 0.05); 
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }
        .pay-icon { width: 36px; height: 36px; border-radius: 10px; background: rgba(46, 204, 113, 0.15); color: #2ecc71; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;}

        /* Attach Options Menu */
        .attach-menu { position: absolute; bottom: 75px; left: 20px; background: rgba(20,20,20,0.95); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 8px; display: flex; flex-direction: column; gap: 4px; z-index: 2000; transform: translateY(10px) scale(0.95); transform-origin: bottom left; opacity: 0; pointer-events: none; transition: 0.2s cubic-bezier(0.2,0.8,0.2,1); box-shadow: 0 10px 30px rgba(0,0,0,0.5);}
        .attach-menu.active { transform: translateY(0) scale(1); opacity: 1; pointer-events: auto; }
        .attach-menu-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 10px; cursor: pointer; transition: 0.2s;}
        .attach-menu-item:hover { background: rgba(255,255,255,0.05); transform: translateX(2px);}
        .attach-menu-item.danger { color: #ff4757; }
        .attach-menu-item.danger:hover { background: rgba(255, 71, 87, 0.1); }

        /* Custom Map Icons */
        .map-icon { width: 40px; height: 40px; border-radius: 50%; border: 3px solid #fff; display: flex; justify-content: center; align-items: center; color: #fff; font-size: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);}
        .map-icon.shop-img { border-color: #FFD700; background: #000; }
        .map-icon.driver-img { border-color: var(--accent-glow-2); background: #000; }
        .map-icon.home { background: var(--accent-glow-3); }

        /* Call Modal */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
            display: flex; justify-content: center; align-items: center; z-index: 5000;
            opacity: 0; pointer-events: none; transition: 0.3s;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        
        .call-modal-content {
            background: var(--bg-color); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px;
            width: 320px; padding: 24px; display: flex; flex-direction: column; gap: 16px;
            transform: translateY(20px); transition: 0.3s cubic-bezier(0.2,0.8,0.2,1);
        }
        .modal-overlay.active .call-modal-content { transform: translateY(0); }

        .call-modal-header { display: flex; justify-content: space-between; align-items: center; }
        .call-modal-header h3 { font-size: 18px; color: #fff; font-weight: 800; margin: 0;}
        .close-modal { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; transition: 0.2s;}
        .close-modal:hover { color: var(--accent-glow-3); }

        .call-option {
            display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 16px;
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); text-decoration: none;
            transition: 0.2s;
        }
        .call-option:hover { background: rgba(255,255,255,0.08); border-color: var(--accent-glow-2); transform: translateY(-2px);}
        .call-option:active { transform: translateY(0); }
        .call-avatar { width: 44px; height: 44px; border-radius: 50%; overflow: hidden; flex-shrink: 0;}
        .call-avatar img { width: 100%; height: 100%; object-fit: cover;}
        .call-details { flex: 1; display: flex; flex-direction: column; gap: 4px; }
        .call-name { font-size: 15px; font-weight: 700; color: #fff; }
        .call-desc { font-size: 12px; color: rgba(255,255,255,0.5); }

        .call-btn {
            background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff;
            width: 36px; height: 36px; border-radius: 50%; display: flex; justify-content: center; align-items: center;
            cursor: pointer; transition: 0.2s; margin-bottom: 4px; /* offset alignment slightly */
        }
        .picker-modal-content {
            background: var(--bg-color); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px;
            width: 360px; padding: 24px; display: flex; flex-direction: column; 
            transform: translateY(20px); transition: 0.3s cubic-bezier(0.2,0.8,0.2,1);
        }
        .modal-overlay.active .picker-modal-content { transform: translateY(0); }
        .picker-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;}
        .picker-modal-header h3 { font-size: 18px; color: #fff; font-weight: 800; margin: 0;}

        /* --- MOBILE OVERRIDES (100% Chat / Modal Map) --- */
        .mobile-map-ui, .open-map-btn { display: none; }

        @media (max-width: 768px) {
            body { display: block; }
            #sidebar { width: 100%; height: 100vh; border-right: none; }
            .top-bar-right { display: flex; align-items: center; gap: 10px; flex-direction: row; }
            .call-btn { margin-bottom: 0;}
            
            /* Hide the default static status to replace it with the Map button */
            .order-status { display: none; }
            
            /* The map view becomes an overlay sliding up */
            #map-container {
                position: fixed; inset: 0; z-index: 3000;
                transform: translateY(100vh); transition: transform 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
            }
            body.show-map #map-container { transform: translateY(0); }

            /* Mobile UI Elements */
            .open-map-btn {
                background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff;
                width: 36px; height: 36px; border-radius: 50%; display: flex; justify-content: center; align-items: center;
                cursor: pointer; transition: 0.2s; padding: 0;
            }
            .open-map-btn:active { background: rgba(44, 181, 232, 0.2); border-color: var(--accent-glow-2); color: var(--accent-glow-2); }
            
            .mobile-map-ui {
                display: flex; justify-content: space-between; align-items: flex-start;
                position: absolute; top: 0; left: 0; width: 100%; z-index: 3100;
                padding: 16px 20px; pointer-events: none;
            }
            .mobile-map-ui > * { pointer-events: auto; }
            
            .close-map-btn {
                background: rgba(10,10,10,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.1);
                color: #fff; padding: 10px 14px; border-radius: 99px; font-weight: 700; font-size: 13px;
                cursor: pointer; display: flex; align-items: center; gap: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            }
            .mobile-map-status {
                background: rgba(10,10,10,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(44, 181, 232, 0.4);
                padding: 10px 14px; border-radius: 14px; text-align: right; box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            }
            .mobile-map-status-title { font-weight: 800; color: #fff; font-size: 13px; margin-bottom: 2px;}
            .mobile-map-status-desc { font-size: 11px; color: var(--accent-glow-2); display: flex; align-items: center; gap: 4px; justify-content: flex-end;}
        }

        /* --- Rating Modal --- */
        .rating-modal-inner {
            background: rgba(15,15,15,0.85); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 24px; padding: 30px; width: 340px; text-align: center; color: white; 
            display: flex; flex-direction: column; gap:16px; box-shadow: 0 10px 40px rgba(0,0,0,0.6);
            transform: scale(0.95); opacity: 0; transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        #rating-modal.active .rating-modal-inner {
            transform: scale(1); opacity: 1;
        }
        .rating-stars { display: flex; justify-content: center; gap: 10px; font-size: 36px; color: rgba(255,255,255,0.1); cursor: pointer; margin: 10px 0; }
        .rating-stars i.active { color: #FFD700; text-shadow: 0 0 16px rgba(255,215,0,0.5); transform: scale(1.1); transition: 0.2s;}
        
        .rating-avatar { width: 88px; height: 88px; border-radius: 50%; border: 3px solid var(--accent-glow-2); object-fit: cover; margin: 0 auto; box-shadow: 0 0 20px rgba(44, 181, 232, 0.4);}
        #rating-step-shop .rating-avatar { border-color: #FFD700; box-shadow: 0 0 20px rgba(255,215,0,0.4); }
    </style>
</head>
<body>

    <!-- Left/Full Sidebar: Chat & Tracker -->
    <div id="sidebar">
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="javascript:void(0)" onclick="goBackToOrders()" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <!-- Shop Logo -->
                    <img src="<?= $shopImgUrl ?>" style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid rgba(255,255,255,0.1); object-fit: cover; box-shadow: 0 4px 10px rgba(0,0,0,0.3);">
                    <div>
                        <div style="font-weight: 800; color: #fff; font-size: 15px;"><?= htmlspecialchars($shopName) ?> <i class="fa-solid fa-circle-check" style="color:var(--accent-glow-2); font-size:12px;"></i></div>
                        <div style="font-size: 11px; color: rgba(255,255,255,0.5);">Order #<?= $orderId ?></div>
                    </div>
                </div>
            </div>
            
            <div class="top-bar-right" style="display: flex; align-items: center; gap: 8px; justify-content: flex-end;">
                <div class="order-status"><i class="fa-solid fa-circle fa-fade" style="font-size: 8px;"></i> <?= htmlspecialchars($orderStatusText) ?></div>
                <button class="call-btn" onclick="openCallPopup()" title="Call Contacts"><i class="fa-solid fa-phone"></i></button>
                <!-- This button is only visible on MOBILE to open the fullscreen map view -->
                <button class="open-map-btn" onclick="toggleMap()" title="Live Map"><i class="fa-solid fa-map-location-dot"></i></button>
            </div>
        </div>

        <!-- Tracker Line -->
        <?php 
            $isCancelled = (strtolower($rawStatus ?? '') == 'cancelled');
            $barWidth = $isCancelled ? "100%" : (($stepIndex / 6) * 100) . "%";
        ?>
        <div class="tracker-board" id="main-tracker-board">
            <div class="progress-bar">
                <div class="progress-fill <?= $isCancelled ? 'cancelled' : '' ?>" id="prog-bar" style="width: <?= $barWidth ?>;"></div>
            </div>
            <div class="tracker-steps" style="font-size: 10px; display: flex; justify-content: space-between;">
                <div class="step <?= $stepIndex >= 1 ? 'active' : '' ?>">Placed</div>
                <div class="step <?= $stepIndex >= 2 ? 'active' : '' ?>">Confirmed</div>
                <div class="step <?= $stepIndex >= 3 ? 'active' : '' ?>">Processed</div>
                <div class="step <?= $stepIndex >= 4 ? 'active' : '' ?>">Pickup</div>
                <div class="step <?= $stepIndex >= 5 ? 'active' : '' ?>">On Way</div>
                <div class="step <?= $stepIndex >= 6 && !$isCancelled ? 'active' : '' ?>">Delivered</div>
            </div>
        </div>

        <!-- Order Security PIN -->
        <div style="padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; background: rgba(155, 45, 241, 0.05); border-bottom: 1px solid rgba(255,255,255,0.05); flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(155, 45, 241, 0.15); color: var(--accent-glow-3); display: flex; align-items: center; justify-content: center; font-size: 16px;"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); font-weight: 600;">Secure Delivery PIN</div>
                    <div style="font-size: 12px; color: var(--accent-glow-3); font-weight: 700;">Give to driver to complete</div>
                </div>
            </div>
            <div style="font-size: 26px; font-weight: 900; letter-spacing: 6px; color: #fff; font-family: monospace; background: rgba(0,0,0,0.5); padding: 4px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);">
                <?= $fourDigitPin ?>
            </div>
        </div>

        <div class="chat-area" id="chatbox">
            <!-- Messages will be injected by Firebase Realtime DB -->
        </div>

        <!-- Payment Reminder Bar -->
        <div class="payment-summary-bar" onclick="openReceiptPopup()" style="cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='rgba(46, 204, 113, 0.1)'" onmouseout="this.style.background='rgba(46, 204, 113, 0.05)'">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div class="pay-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); font-weight: 600;">Payment Method</div>
                    <div style="font-size: 14px; color: #fff; font-weight: 800;"><?= htmlspecialchars($paymentMethodText ?? 'Cash on Delivery') ?></div>
                </div>
            </div>
            <div style="text-align: right; display: flex; align-items: center; gap: 10px;">
                <div>
                    <div style="font-size: 11px; color: rgba(255,255,255,0.5); font-weight: 600;">To Pay</div>
                    <div style="font-size: 16px; color: #2ecc71; font-weight: 800;"><?= $totalPrice ?> MAD</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color: rgba(255,255,255,0.2); font-size: 14px;"></i>
            </div>
        </div>

        <div class="chat-input-wrapper" style="position: relative;">
            <div class="attach-menu" id="attach-menu">
                <div class="attach-menu-item" onclick="triggerImageUpload()">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(44, 181, 232, 0.1); color: var(--accent-glow-2); display: flex; align-items: center; justify-content: center;"><i class="fa-regular fa-image"></i></div> Send Image
                </div>
                <div class="attach-menu-item" onclick="openPickLocation()">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(155, 45, 241, 0.1); color: var(--accent-glow-3); display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-location-crosshairs"></i></div> Send Location
                </div>
                <hr style="border-color: rgba(255,255,255,0.05); margin: 4px 0;">
                <div class="attach-menu-item danger" onclick="cancelOrderMock()">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(255, 71, 87, 0.1); color: #ff4757; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-ban"></i></div> Cancel Order
                </div>
            </div>

            <button class="attach-btn" onclick="toggleAttachMenu()" title="More Options"><i class="fa-solid fa-plus"></i></button>
            <input type="file" id="image-upload" accept="image/*" style="display: none;">
            <input type="text" id="chat-input" class="chat-input" placeholder="Message the group..." onkeypress="handleEnter(event)">
            <button class="send-btn" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
    </div>

    <!-- Right Map Container (Slides up on Mobile) -->
    <div id="map-container">
        
        <!-- UI inside map strictly for Mobile App feel -->
        <div class="mobile-map-ui">
            <button class="close-map-btn" onclick="toggleMap()"><i class="fa-solid fa-chevron-down"></i> Chat</button>
            <div class="mobile-map-status">
                <div class="mobile-map-status-title">Order #<?= $orderId ?></div>
                <div class="mobile-map-status-desc" id="mob-status-label"><i class="fa-solid fa-circle fa-fade" style="font-size: 8px;"></i> <?= htmlspecialchars($orderStatusText) ?></div>
            </div>
        </div>

        <div id="map"></div>
    </div>

    <!-- Call Options Modal -->
    <div id="call-modal" class="modal-overlay">
        <div class="call-modal-content">
            <div class="call-modal-header">
                <h3>Contact Support</h3>
                <button class="close-modal" onclick="closeCallPopup()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <a href="tel:+212600000000" class="call-option">
                <div class="call-avatar"><img src="<?= $shopImgUrl ?>"></div>
                <div class="call-details">
                    <div class="call-name"><?= htmlspecialchars($shopName) ?></div>
                    <div class="call-desc">For order changes or items</div>
                </div>
                <i class="fa-solid fa-phone" style="color:var(--accent-glow-2)"></i>
            </a>

            <a href="tel:+212611111111" class="call-option">
                <div class="call-avatar"><img src="<?= $driverImgUrl ?>" id="driver-call-img"></div>
                <div class="call-details">
                    <div class="call-name"><?= htmlspecialchars($driverName) ?></div>
                    <div class="call-desc">For vehicle location and tracking</div>
                </div>
                <i class="fa-solid fa-phone" style="color:var(--accent-glow-2)"></i>
            </a>
        </div>
    </div>

    <!-- Rating Modal -->
    <div id="rating-modal" class="modal-overlay <?= (in_array(strtolower($rawStatus), ['done', 'finish', 'order delivered'])) ? 'active' : '' ?>" style="z-index: 9999;">
        <!-- Step 1: Shop -->
        <div class="rating-modal-inner" id="rating-step-shop">
            <h2 style="font-size:22px; font-weight:800;">Order Delivered! 🎉</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-top:-6px;">Please rate your experience to help us improve.</p>
            
            <img src="<?= $shopImgUrl ?>" class="rating-avatar" style="margin-top:10px;">
            <div style="font-weight: 800; font-size: 18px;"><?= htmlspecialchars($shopName) ?></div>
            
            <div class="rating-stars" id="shop-stars">
                <i class="fa-solid fa-star" onclick="rateShop(1)"></i>
                <i class="fa-solid fa-star" onclick="rateShop(2)"></i>
                <i class="fa-solid fa-star" onclick="rateShop(3)"></i>
                <i class="fa-solid fa-star" onclick="rateShop(4)"></i>
                <i class="fa-solid fa-star" onclick="rateShop(5)"></i>
            </div>
            
            <button class="send-btn" style="width:100%; height:50px; border-radius:14px; font-weight:800; font-size:15px; margin-top:16px;" onclick="nextRatingStep()">Continue <i class="fa-solid fa-arrow-right" style="margin-left:6px;"></i></button>
        </div>

        <!-- Step 2: Driver -->
        <div class="rating-modal-inner" id="rating-step-driver" style="display:none;">
            <h2 style="font-size:22px; font-weight:800;">Rate your Driver 🛵</h2>
            <p style="color:var(--text-muted); font-size:13px; margin-top:-6px;">How was the delivery by <?= htmlspecialchars($driverName) ?>?</p>
            
            <img src="<?= $driverImgUrl ?>" class="rating-avatar" style="margin-top:10px;">
            <div style="font-weight: 800; font-size: 18px;"><?= htmlspecialchars($driverName) ?></div>
            
            <div class="rating-stars" id="driver-stars">
                <i class="fa-solid fa-star" onclick="rateDriver(1)"></i>
                <i class="fa-solid fa-star" onclick="rateDriver(2)"></i>
                <i class="fa-solid fa-star" onclick="rateDriver(3)"></i>
                <i class="fa-solid fa-star" onclick="rateDriver(4)"></i>
                <i class="fa-solid fa-star" onclick="rateDriver(5)"></i>
            </div>
            
            <button class="send-btn" style="width:100%; height:50px; border-radius:14px; font-weight:800; font-size:15px; margin-top:16px; background: linear-gradient(135deg, #2ecc71, #27ae60); box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);" onclick="submitRatings()">Submit Ratings <i class="fa-solid fa-check-circle" style="margin-left:6px;"></i></button>
        </div>
    </div>

    <!-- Pick Location Modal -->
    <div id="location-picker-modal" class="modal-overlay">
        <div class="picker-modal-content">
            <div class="picker-modal-header">
                <h3>Share Location</h3>
                <button class="close-modal" onclick="closePickerPopup()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div class="picker-map-wrapper" style="position:relative; width: 100%; height: 250px; border-radius: 16px; overflow: hidden; margin-bottom: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div id="pick-map" style="width:100%; height:100%;"></div>
                <!-- CSS Central Pin Overlay that stays fixed while map moves -->
                <div style="position: absolute; top:50%; left:50%; transform: translate(-50%, -100%); z-index: 1000; font-size: 32px; color: var(--accent-glow-3); filter: drop-shadow(0 4px 6px rgba(0,0,0,0.5)); pointer-events: none;">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
            </div>

            <button class="send-btn" style="width: 100%; height: 48px; border-radius: 14px; gap: 8px; flex-shrink:0;" onclick="confirmLocationSend()"><i class="fa-solid fa-paper-plane"></i> Send this location</button>
        </div>
    </div>

    <!-- Cancel Order Modal -->
    <div id="cancel-modal" class="modal-overlay">
        <div class="call-modal-content" id="cancel-modal-inner" style="align-items: center; text-align: center;">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255, 71, 87, 0.1); color: #ff4757; display: flex; align-items: center; justify-content: center; font-size: 28px; margin-bottom: 10px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 style="font-size: 20px; color: #fff; font-weight: 800; margin: 0;">Cancel Order?</h3>
            <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin: 0 0 10px;">This action will notify the driver and shop, and cannot be undone.</p>
            
            <div style="display: flex; gap: 10px; width: 100%;">
                <button class="send-btn" style="flex: 1; height: 44px; border-radius: 12px; font-weight: 700; font-size: 14px; background: rgba(255,255,255,0.1); color: #fff;" onclick="closeCancelModal()">Keep Order</button>
                <button class="send-btn" style="flex: 1; height: 44px; border-radius: 12px; font-weight: 700; font-size: 14px; background: #ff4757; color: #fff; box-shadow: 0 4px 15px rgba(255,71,87,0.3);" onclick="executeCancelOrder()" id="confirm-cancel-btn">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Receipt Details Modal -->
    <div id="receipt-modal" class="modal-overlay">
        <div class="call-modal-content" style="width: 340px;">
            <div class="call-modal-header">
                <h3>Receipt Details</h3>
                <button class="close-modal" onclick="closeReceiptPopup()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            
            <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 16px;">
                <div style="font-size: 13px; color: rgba(255,255,255,0.6); margin-bottom: 8px;">Items Ordered</div>
                <div style="font-size: 14px; color: #fff; font-weight: 600; line-height: 1.5; margin-bottom: 16px;">
                    <?= nl2br(htmlspecialchars($orderDetailsStr ?? '')) ?>
                </div>
                
                <hr style="border: 0; height: 1px; background: rgba(255,255,255,0.1); margin: 12px 0;">
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: rgba(255,255,255,0.8);">
                    <span>Products Fee</span>
                    <span style="font-weight: 700; color: #fff;"><?= $productsFee ?? 0 ?> MAD</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: rgba(255,255,255,0.8);">
                    <span>Delivery Fee</span>
                    <span style="font-weight: 700; color: <?= $deliveryFee > 0 ? '#fff' : '#f1c40f' ?>;"><?= $deliveryFee > 0 ? $deliveryFee . ' MAD' : 'Pending...' ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 16px; font-size: 14px; color: rgba(255,255,255,0.8);">
                    <span>Platform Fee</span>
                    <span style="font-weight: 700; color: #fff;"><?= $userFees ?? 0 ?> MAD</span>
                </div>
                
                <hr style="border: 0; height: 1px; background: rgba(255,255,255,0.1); margin: 12px 0;">
                
                <div style="display: flex; justify-content: space-between; font-size: 16px; color: #2ecc71; font-weight: 900;">
                    <span>Total To Pay</span>
                    <span><?= $totalPrice ?> MAD</span>
                </div>
            </div>
            
            <button class="send-btn" style="width:100%; height:44px; border-radius:12px; font-weight:700; font-size:14px; margin-top:8px;" onclick="closeReceiptPopup()">Got it</button>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    
    <!-- Firebase JS SDK -->
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
    <script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-database.js"></script>
    <script src="assets/js/firebase-auth.js"></script>

    <script>
        // Init Firebase
        
        const db = firebase.database();
        const chatRef = db.ref('Messages/<?= $orderId ?>');

        // --- Smart Back Navigation ---
        function goBackToOrders() {
            const ref = document.referrer;
            if (ref && (ref.includes('index.php') || ref.includes('orders.php') || ref.includes('chat.php'))) {
                history.back();
            } else {
                window.parent.location.href = 'index.php?drawer=orders';
            }
        }
        
        function openReceiptPopup() {
            document.getElementById('receipt-modal').classList.add('active');
        }
        function closeReceiptPopup() {
            document.getElementById('receipt-modal').classList.remove('active');
        }

        // --- Modal Call Logic ---
        function openCallPopup() { 
            // Ensure Driver image stays synced
            document.getElementById('driver-call-img').src = driverImgUrl;
            document.getElementById('call-modal').classList.add('active'); 
        }
        function closeCallPopup() { document.getElementById('call-modal').classList.remove('active'); }

        // --- Rating Modal Logic ---
        let selectedShopRating = 5;
        let selectedDriverRating = 5;

        // Auto-select 5 stars initially if modal is shown
        if ('<?= $rawStatus ?>' === 'Done' || '<?= $rawStatus ?>' === 'FINISH') {
            rateShop(5);
            rateDriver(5);
        }

        function rateShop(val) {
            selectedShopRating = val;
            const stars = document.querySelectorAll('#shop-stars i');
            stars.forEach((s, idx) => {
                if(idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });
        }

        function rateDriver(val) {
            selectedDriverRating = val;
            const stars = document.querySelectorAll('#driver-stars i');
            stars.forEach((s, idx) => {
                if(idx < val) s.classList.add('active');
                else s.classList.remove('active');
            });
        }

        function nextRatingStep() {
            document.getElementById('rating-step-shop').style.display = 'none';
            document.getElementById('rating-step-driver').style.display = 'flex';
        }

        function submitRatings() {
            // Morph the modal into a Success Checkmark!
            document.getElementById('rating-step-driver').innerHTML = `
                <i class="fa-solid fa-circle-check" style="font-size: 70px; color: #2ecc71; margin-bottom: 20px; filter: drop-shadow(0 0 10px rgba(46,204,113,0.5));"></i>
                <h2 style="font-size:24px; font-weight:800;">Thank You!</h2>
                <p style="color:var(--text-muted); font-size:14px; margin-top: 5px;">Your feedback helps keep QOON amazing.</p>
                <button class="send-btn" style="width:100%; height:48px; border-radius:14px; font-weight:800; font-size:14px; margin-top:20px; background: rgba(255,255,255,0.1);" onclick="goBackToOrders()">Back to Orders <i class="fa-solid fa-bag-shopping" style="margin-left:6px;"></i></button>
            `;
            
            // Post ratings silently to background API
            const fd = new FormData();
            fd.append('OrderID', '<?= $orderId ?>');
            fd.append('DriverID', '<?= $actualDriverId ?? '0' ?>');
            fd.append('Rating', selectedDriverRating);
            fd.append('Review', '');
            fd.append('ShopID', '<?= $actualShopId ?? '0' ?>');
            fd.append('RatingShop', selectedShopRating);
            fd.append('ReviewShop', '');

            fetch('UserRateDriver.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(res => console.log("Ratings Saved", res))
            .catch(e => console.error("Rating Error:", e));
        }

        // --- Mobile Map Toggle Logic ---
        function toggleMap() {
            document.body.classList.toggle('show-map');
            if(document.body.classList.contains('show-map')){
                // Ensure leaflet recalibrates since its container was out of view
                setTimeout(() => map.invalidateSize(), 300);
            }
        }

        // --- Map Initialization ---
        const map = L.map('map', { zoomControl: false }).setView([33.5731, -7.5898], 15);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap © CartoDB'
        }).addTo(map);

        // Marker Icons
        const shopImgUrl = `<?= $shopImgUrl ?>`;
        const driverImgUrl = `<?= $driverImgUrl ?>`;
        const driverNameForChat = `<?= htmlspecialchars($driverName) ?>`;
        
        const iconShop = L.divIcon({ className: 'custom-icon', html: `<img src="${shopImgUrl}" class="map-icon shop-img" style="object-fit: cover;">`, iconSize: [40,40], iconAnchor: [20,40] });
        const iconHome = L.divIcon({ className: 'custom-icon', html: `<div class="map-icon home"><i class="fa-solid fa-house"></i></div>`, iconSize: [40,40], iconAnchor: [20,40] });
        const iconDriver = L.divIcon({ className: 'custom-icon', html: `<img src="${driverImgUrl}" class="map-icon driver-img" style="object-fit: cover;">`, iconSize: [40,40], iconAnchor: [20,40] });

        // Coordinates
        const destCoord = [<?= $shopLat ?? 33.5750 ?>, <?= $shopLng ?? -7.5850 ?>]; // Shop
        const homeCoord = [<?= $userLat ?? 33.5650 ?>, <?= $userLng ?? -7.5950 ?>]; // User Home
        let driverCoord = destCoord; // Driver Start (assume near shop until Firebase updates)

        // Add Markers
        const shopMarker = L.marker(destCoord, {icon: iconShop}).addTo(map);
        const homeMarker = L.marker(homeCoord, {icon: iconHome}).addTo(map);
        const driverMarker = L.marker(driverCoord, {icon: iconDriver}).addTo(map);

        // Map FitBounds
        const bounds = L.latLngBounds([destCoord, homeCoord, driverCoord]);
        map.fitBounds(bounds, { padding: [50, 50] });

        // Real-Time Polling & Simulation Logic
        let currentStatus = '<?= addslashes($rawStatus) ?>';
        
        function updateTrackingUI(rawStatus) {
            currentStatus = rawStatus;
            let r = rawStatus.toLowerCase().trim();
            let stepIndex = 1;
            let statusText = 'Order placed';
            let isCancelled = false;

            if (['cancelled', 'canceled'].includes(r)) {
                statusText = 'Cancelled';
                stepIndex = 6;
                isCancelled = true;
            } else if (['order delivered', 'done', 'finish', 'rated', 'delivered'].includes(r)) {
                statusText = 'Order delivered';
                stepIndex = 6;
            } else if (['come to take it', 'doing', 'found', 'on way', 'on the way'].includes(r)) {
                statusText = 'Come to take it';
                stepIndex = 5;
            } else if (['order pickup', 'pickup', 'picked', 'picked up'].includes(r)) {
                statusText = 'Order pickup';
                stepIndex = 4;
            } else if (['order processed', 'prepared', 'processed'].includes(r)) {
                statusText = 'Order processed';
                stepIndex = 3;
            } else if (['order confirmed', 'confirmed', 'accept', 'yes'].includes(r)) {
                statusText = 'Order confirmed';
                stepIndex = 2;
            } else {
                statusText = 'Order placed';
                stepIndex = 1;
            }
            
            // Update Text
            const desktoptxt = document.querySelectorAll('.order-status, .mobile-map-status-desc');
            let iconHtml = isCancelled ? '<i class="fa-solid fa-circle-xmark"></i>' : (stepIndex === 6 ? '<i class="fa-solid fa-check fa-beat"></i>' : '<i class="fa-solid fa-circle fa-fade" style="font-size: 8px;"></i>');
            desktoptxt.forEach(el => el.innerHTML = `${iconHtml} ${statusText}`);
            
            // Update Progress Bar
            const progBar = document.getElementById('prog-bar');
            progBar.style.width = isCancelled ? "100%" : ((stepIndex / 6) * 100) + "%";
            if (isCancelled) progBar.classList.add('cancelled');
            else progBar.classList.remove('cancelled');
            
            // Update Steps
            const steps = document.querySelectorAll('.tracker-steps .step');
            steps.forEach((el, idx) => {
                if (idx < stepIndex) el.classList.add('active');
                else el.classList.remove('active');
            });
            if (isCancelled && steps.length >= 6) steps[5].classList.remove('active'); 

            // If final state, stop polling and show rating
            if (stepIndex === 6 && !isCancelled) {
                driverMarker.setLatLng(homeCoord);
                map.panTo(homeCoord);
                if(r !== 'rated' && !document.getElementById('rating-modal').classList.contains('active')) {
                    document.getElementById('rating-modal').classList.add('active');
                    rateShop(5); rateDriver(5);
                }
                clearInterval(statusPoll);
            } else if (isCancelled) {
                clearInterval(statusPoll);
            }
        }

        const statusPoll = setInterval(() => {
            const isFinalState = (currentStatus.toLowerCase() === 'done' || currentStatus.toLowerCase() === 'finish' || currentStatus.toLowerCase() === 'rated' || currentStatus.toLowerCase() === 'cancelled' || currentStatus.toLowerCase() === 'order delivered');
            
            if (!isFinalState) {
                fetch(`track_order.php?orderId=<?= $orderId ?>&ajax_status=1`)
                .then(r => r.json())
                .then(data => {
                    if (data.status !== currentStatus) {
                        updateTrackingUI(data.status); // <--- Update DOM via API instead of Reloading!
                    }
                    
                    // Realtime driver tracking via Firebase is handled by the listener below.
                }).catch(e => console.error(e));
            } else {
                if (currentStatus.toLowerCase() === 'cancelled') {
                    document.getElementById('prog-bar').classList.add('cancelled');
                }
                clearInterval(statusPoll);
            }
        }, 3000);

        // --- Driver Realtime Firebase Location ---
        <?php if ($actualDriverId !== '0'): ?>
        const driverLocRef = db.ref('Location/<?= $actualDriverId ?>');
        driverLocRef.on('value', snapshot => {
            const data = snapshot.val();
            if (data) {
                const dLat = parseFloat(data.lat || data.latitude);
                const dLng = parseFloat(data.lng || data.longitude);
                if (!isNaN(dLat) && !isNaN(dLng)) {
                    driverCoord = [dLat, dLng];
                    driverMarker.setLatLng(driverCoord);
                }
            }
        });
        <?php endif; ?>

        // --- Chat Functions ---
        chatRef.on('child_added', snapshot => {
            const data = snapshot.val();
            renderMessage(data);
        });

        function renderMessage(data) {
            const chatbox = document.getElementById('chatbox');
            const card = document.createElement('div');
            const isMe = data.sender === 'User';
            card.className = isMe ? 'chat-msg me' : 'chat-msg driver'; // You can separate Shop/Driver classes if you have avatars later
            
            let htmlInner = '';
            let dispName = '';
            let avatarHtml = '';
            
            if (isMe) {
                dispName = 'You';
            } else if (data.sender === 'jibler') {
                dispName = driverNameForChat + ' <i class="fa-solid fa-motorcycle"></i>';
                avatarHtml = `<img src="${driverImgUrl}" class="chat-avatar">`;
            } else if (data.sender === 'Shop') {
                dispName = '<?= htmlspecialchars($shopName) ?> <i class="fa-solid fa-store"></i>';
                avatarHtml = `<img src="${shopImgUrl}" class="chat-avatar">`;
            } else {
                dispName = data.sender;
            }

            let msgPayload = '';
            if (data.MessageType === 'Image' || (data.message && typeof data.message === 'string' && data.message.includes('http') && data.message.includes('png'))) {
                msgPayload = `<div class="chat-bubble" style="padding: 6px; background: var(--glass-bg);"><img src="${data.message}" style="width: 200px; height: 180px; object-fit: cover; border-radius: 12px;"></div>`;
            } else if (data.MessageType === 'Location' || (data.message && typeof data.message === 'string' && data.message.includes('Live location'))) {
                msgPayload = `<div class="location-bubble">
                    <div class="loc-map-wrapper"><div class="loc-map-bg"></div><div class="loc-map-bg-icon"><i class="fa-solid fa-location-dot"></i></div></div>
                    <div class="loc-details"><div class="loc-title">Live location</div><div class="loc-sub">Pin shared</div></div>
                </div>`;
            } else {
                msgPayload = `<div class="chat-bubble">${data.message}</div>`;
            }

            card.innerHTML = `
                ${avatarHtml}
                <div class="chat-content">
                    <div class="chat-name">${dispName}</div>
                    ${msgPayload}
                </div>
            `;
            chatbox.appendChild(card);
            chatbox.scrollTo({ top: chatbox.scrollHeight, behavior: 'smooth' });
        }


        function handleEnter(e) { if(e.key === 'Enter') sendMessage(); }
        
        function sendMessage() {
            const input = document.getElementById('chat-input');
            const msg = input.value.trim();
            if(!msg) return;
            
            input.value = '';
            
            // Push to Firebase Realtime DB!
            chatRef.push({
                CreatedTime: Date.now(),
                MessageType: 'words',
                message: msg,
                sender: 'User'
            });
        }

        // --- WhatsApp Style Pick Location Flow ---
        let pickMapInit = false;
        let pickMap;

        function openPickLocation() {
            document.getElementById('location-picker-modal').classList.add('active');
            
            // Only initialize the second map once
            if(!pickMapInit) {
                pickMap = L.map('pick-map', { zoomControl: false, attributionControl: false }).setView(homeCoord, 16);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(pickMap);
                pickMapInit = true;
            }
            
            // IMPORTANT: Force map to recalibrate tiles since the modal was display:none recently
            setTimeout(() => pickMap.invalidateSize(), 300);
        }

        function closePickerPopup() { 
            document.getElementById('location-picker-modal').classList.remove('active'); 
        }

        function confirmLocationSend() {
            closePickerPopup(); // Close the modal
            document.getElementById('attach-menu').classList.remove('active'); // Close menu
            
            // Push Location type to Firebase
            chatRef.push({
                CreatedTime: Date.now(),
                MessageType: 'Location',
                message: 'Live location shared',
                sender: 'User'
            });
        }

        // --- Attach Menu Functions ---
        function toggleAttachMenu() {
            document.getElementById('attach-menu').classList.toggle('active');
        }
        
        // Close attach menu if clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.chat-input-wrapper')) {
                document.getElementById('attach-menu').classList.remove('active');
            }
        });

        function triggerImageUpload() {
            document.getElementById('attach-menu').classList.remove('active');
            document.getElementById('image-upload').click();
        }

        // Handle Image Upload using Base64 & uploadImageChat.php
        document.getElementById('image-upload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                // Get Base64 without the 'data:image/...;base64,' prefix
                const base64String = e.target.result.split(',')[1];
                
                const fd = new FormData();
                fd.append("photochat", base64String);

                // Instantly show uploading placeholder
                const chatbox = document.getElementById('chatbox');
                const card = document.createElement('div');
                card.className = 'chat-msg me';
                card.id = 'uploading-image-msg';
                card.innerHTML = `
                    <div class="chat-content">
                        <div class="chat-name">You (Uploading...)</div>
                        <div class="chat-bubble" style="padding: 10px; background: var(--glass-bg); opacity: 0.5;">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px; color: var(--accent-glow-2)"></i>
                        </div>
                    </div>
                `;
                chatbox.appendChild(card);
                chatbox.scrollTo({ top: chatbox.scrollHeight, behavior: 'smooth' });

                // Call the actual server API
                fetch("uploadImageChat.php", {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        const imgUrl = data.data; // URL returned from the server DB/Storage
                        document.getElementById('uploading-image-msg').remove();
                        appendRealImageToChat(imgUrl);
                    } else {
                        alert("Failed to upload image.");
                        document.getElementById('uploading-image-msg').remove();
                    }
                }).catch((err) => {
                    console.error(err);
                    alert("Network error. Unable to upload.");
                    document.getElementById('uploading-image-msg').remove();
                });
            };
            reader.readAsDataURL(file);
            
            // Clear input so same file can be uploaded again if needed
            event.target.value = '';
        });

        function appendRealImageToChat(imgUrl) {
            // Push actual image URL to Firebase Realtime DB
            chatRef.push({
                CreatedTime: Date.now(),
                MessageType: 'Image',
                message: imgUrl,
                sender: 'User'
            });
        }

        function closeCancelModal() { document.getElementById('cancel-modal').classList.remove('active'); }

        function cancelOrderMock() {
            document.getElementById('attach-menu').classList.remove('active');
            document.getElementById('cancel-modal').classList.add('active');
        }

        function executeCancelOrder() {
            const btn = document.getElementById('confirm-cancel-btn');
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';
            btn.style.pointerEvents = 'none';
            
            const fd = new FormData();
            fd.append('OrderID', '<?= $orderId ?>');

            fetch('CancelOrderUser.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => { showCancelSuccess(); })
            .catch(err => {
                console.error("Error cancelling order:", err);
                showCancelSuccess(); // fallback
            });
        }
        
        function showCancelSuccess() {
            const inner = document.getElementById('cancel-modal-inner');
            inner.innerHTML = `
                <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(46, 204, 113, 0.1); color: #2ecc71; display: flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 10px;">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h3 style="font-size: 20px; color: #fff; font-weight: 800; margin: 0;">Cancelled</h3>
                <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin: 0 0 10px;">Order has been successfully cancelled.</p>
            `;
            setTimeout(() => { window.parent.location.href = "index.php"; }, 1500);
        }
    </script>
</body>
</html>



