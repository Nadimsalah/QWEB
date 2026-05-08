<?php
require_once 'init.php';

$orderID = isset($_GET['id']) ? $con->real_escape_string($_GET['id']) : '';
if (empty($orderID)) {
    header("Location: orders.php");
    exit;
}

// Fetch Order Info
$orderSql = "SELECT o.OrderSource, o.UserAddress, o.DestnationAddress, o.UserPhone as ManualPhone, o.Method, o.OrderID, o.OrderState, o.OrderPrice, o.CreatedAtOrders,
             o.UserLat, o.UserLongt, o.DestnationLat, o.DestnationLongt, o.UserName as ManualBuyer,
             u.PhoneNumber as DbPhone, u.name as BuyerName, u.UserPhoto as BuyerPhoto,
             d.DriverPhone, d.FName as DriverName, d.PersonalPhoto as DriverPhoto, d.DriverID,
             s.ShopName, s.ShopLogo as ShopPhoto
             FROM Orders o
             LEFT JOIN Users u ON o.UserID = u.UserID
             LEFT JOIN Drivers d ON o.DelvryId = d.DriverID
             LEFT JOIN Shops s ON o.DestinationName = s.ShopName
             WHERE o.OrderID = '$orderID' AND o.ShopID = '$sellerID'";

$orderRes = $con->query($orderSql);
$info = $orderRes->fetch_assoc();

if (!$info) {
    die("Order not found or access denied.");
}

// Fetch products
$itemsSql = "SELECT od.Quantity, od.Size, od.Color, f.FoodName, f.FoodPrice, f.FoodPhoto 
             FROM OrderDetailsOrder od
             JOIN Foods f ON od.FoodID = f.FoodID
             WHERE od.OrderID = '$orderID'";
$itemsRes = $con->query($itemsSql);
$items = [];
while ($row = $itemsRes->fetch_assoc()) {
    $items[] = $row;
}

$buyerName = trim($info['ManualBuyer'] ?: ($info['BuyerName'] ?: 'Guest'));
$buyerPhone = $info['ManualPhone'] ?: $info['DbPhone'];
$driverPhone = $info['DriverPhone'];
$cancelPin = str_pad(abs(crc32($orderID . "CANCELPIN")) % 10000, 4, '0', STR_PAD_LEFT);

$actualTotal = 0;
foreach ($items as $itm) {
    $actualTotal += ((float)$itm['Quantity'] * (float)$itm['FoodPrice']);
}
$displayPrice = ($actualTotal > 0) ? $actualTotal : (float)$info['OrderPrice'];

$shopNameEscaped = $con->real_escape_string($_SESSION['SellerName']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $orderID ?> | QOON Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-master: #F3F5FA; --bg-surface: #FFFFFF;
            --text-strong: #111827; --text-base: #374151; --text-muted: #9CA3AF;
            --brand-purple: #6B4EE6; --brand-purple-light: #EBE8FA;
        }

        * { box-sizing: border-box; }

        body { font-family: 'Poppins', sans-serif; background: var(--bg-master); margin: 0; color: var(--text-base); }
        .app-envelope { display: flex; height: 100vh; overflow: hidden; }
        .main-panel { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: var(--bg-master); }

        /* Top Header */
        .page-header { background: #FFF; padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #E2E8F0; z-index: 10; position: relative; }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .btn-back { width: 40px; height: 40px; border-radius: 12px; background: #F8FAFC; color: var(--text-strong); display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 16px; transition: 0.2s; }
        .btn-back:hover { background: #E2E8F0; color: var(--brand-purple); }
        .header-title h1 { margin: 0; font-size: 20px; font-weight: 800; color: var(--text-strong); }
        .header-title span { font-size: 12px; color: var(--text-muted); font-weight: 600; }
        .header-price { font-size: 20px; font-weight: 800; color: var(--brand-purple); background: var(--brand-purple-light); padding: 8px 16px; border-radius: 12px; }

        /* Workspace Grid */
        .workspace { display: flex; flex: 1; overflow: hidden; padding: 20px; gap: 20px; }

        /* --- Left Column: Context (Dribbble Style Cards) --- */
        .col-context { width: 380px; display: flex; flex-direction: column; gap: 20px; overflow-y: auto; padding-right: 5px; }
        .col-context::-webkit-scrollbar { width: 6px; }
        .col-context::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 10px; }
        
        .dribbble-card { background: #FFF; border-radius: 20px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; }
        .card-header { font-size: 13px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .card-header i { color: var(--brand-purple); }

        /* Map Card */
        .map-box { height: 180px; border-radius: 16px; background: #E2E8F0; overflow: hidden; position: relative; margin-bottom: 15px; }
        #context-map { width: 100%; height: 100%; }
        
        /* Participants */
        .participant-row { display: flex; align-items: center; gap: 15px; padding: 12px; background: #F8FAFC; border-radius: 16px; margin-bottom: 10px; border: 1px solid #F1F5F9; }
        .participant-row img { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; }
        .participant-info h4 { margin: 0; font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
        .participant-info h3 { margin: 2px 0 0; font-size: 14px; font-weight: 700; color: var(--text-strong); }
        .call-btn { width: 36px; height: 36px; border-radius: 12px; background: #E2E8F0; color: var(--text-strong); border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; }
        .call-btn:hover { background: var(--brand-purple-light); color: var(--brand-purple); transform: scale(1.05); }

        /* Payload Summary */
        .payload-item { display: flex; align-items: center; gap: 15px; padding: 10px 0; border-bottom: 1px dashed #E2E8F0; }
        .payload-item:last-child { border-bottom: none; padding-bottom: 0; }
        .payload-item img { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; }
        .payload-name { font-size: 13px; font-weight: 700; color: var(--text-strong); }
        .payload-meta { font-size: 11px; color: var(--text-muted); font-weight: 500; }
        .payload-price { font-size: 13px; font-weight: 800; color: var(--text-strong); }

        /* Mini Tracker */
        .tracker-modern { display: flex; flex-direction: column; gap: 8px; }
        .tm-step { padding: 10px 15px; border-radius: 12px; display: flex; align-items: center; gap: 10px; font-size: 13px; font-weight: 600; border: 1px solid #F1F5F9; background: #FFF; color: var(--text-base); }
        .tm-step.done { background: #ECFDF5; color: #059669; border-color: transparent; }
        .tm-step.active { background: var(--brand-purple-light); color: var(--brand-purple); border-color: var(--brand-purple); }
        .tm-step.disabled { opacity: 0.5; background: #F8FAFC; color: #94A3B8; }
        .tm-step.cancel.active { background: #FEF2F2; color: #EF4444; border-color: #EF4444; }

        /* --- Right Column: The Chat Hub --- */
        .col-chat { flex: 1; display: flex; flex-direction: column; background: #FFF; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); border: 1px solid #F1F5F9; overflow: hidden; position: relative; }
        
        /* Chat Feed */
        .chat-feed { flex: 1; padding: 30px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; scroll-behavior: smooth; background: #FAFBFC; }
        
        .msg-row { display: flex; flex-direction: column; max-width: 80%; }
        .msg-row.sys { align-self: center; align-items: center; max-width: 90%; margin: 10px 0; }
        .msg-row.me { align-self: flex-end; align-items: flex-end; }
        .msg-row.other { align-self: flex-start; align-items: flex-start; }
        
        .msg-meta { font-size: 11px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; display: flex; gap: 8px; align-items: center; }
        .bubble { padding: 14px 18px; border-radius: 18px; font-size: 14px; line-height: 1.5; font-weight: 500; font-family: 'Inter', sans-serif; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .msg-row.me .bubble { background: var(--brand-purple); color: #FFF; border-bottom-right-radius: 4px; }
        
        .msg-row.customer .msg-meta { color: #3B82F6; }
        .msg-row.customer .bubble { background: #FFF; border: 1px solid #BFDBFE; color: var(--text-strong); border-bottom-left-radius: 4px; border-left: 3px solid #3B82F6; }
        
        .msg-row.driver .msg-meta { color: #F59E0B; }
        .msg-row.driver .bubble { background: #FFF; border: 1px solid #FDE68A; color: var(--text-strong); border-bottom-left-radius: 4px; border-left: 3px solid #F59E0B; }
        
        /* System Timeline Block */
        .sys-bubble { background: #FFF; border: 1px solid #E2E8F0; border-radius: 20px; padding: 10px 20px; font-size: 12px; font-weight: 700; color: var(--text-muted); display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .sys-bubble i { color: var(--brand-purple); font-size: 14px; }
        .sys-bubble.cancel-sys i { color: #EF4444; }

        /* Inline Action Tray (Inside Chat) */
        .action-tray { display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap; justify-content: center; }
        .action-chip { padding: 12px 20px; border-radius: 16px; font-size: 13px; font-weight: 700; cursor: pointer; border: 1px solid #E2E8F0; background: #FFF; transition: 0.2s; display: inline-flex; align-items: center; gap: 10px; color: var(--text-strong); box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .action-chip:hover { border-color: var(--brand-purple); color: var(--brand-purple); background: var(--brand-purple-light); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(107, 78, 230, 0.15); }
        .action-chip.danger { color: #EF4444; }
        .action-chip.danger:hover { background: #FEF2F2; border-color: #EF4444; box-shadow: 0 6px 15px rgba(239, 68, 68, 0.15); }
        
        /* Smart Input Bar */
        .chat-input-area { padding: 20px; background: #FFF; border-top: 1px solid #F1F5F9; }
        
        /* Quick Actions Above Input */
        .quick-actions { display: flex; gap: 10px; margin-bottom: 15px; overflow-x: auto; padding-bottom: 5px; }
        .quick-actions::-webkit-scrollbar { height: 4px; }
        .quick-actions::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }
        .qa-btn { padding: 8px 16px; border-radius: 10px; background: #F8FAFC; border: 1px solid #E2E8F0; font-size: 12px; font-weight: 700; color: var(--text-muted); cursor: pointer; white-space: nowrap; transition: 0.2s; }
        .qa-btn:hover { background: var(--brand-purple-light); color: var(--brand-purple); border-color: var(--brand-purple); }
        .qa-btn.danger:hover { background: #FEF2F2; color: #EF4444; border-color: #EF4444; }
        
        .input-box { display: flex; gap: 15px; align-items: center; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 20px; padding: 10px 15px; transition: 0.2s; }
        .input-box:focus-within { border-color: var(--brand-purple); background: #FFF; box-shadow: 0 0 0 4px var(--brand-purple-light); }
        .input-box textarea { flex: 1; border: none; background: transparent; outline: none; font-size: 14px; resize: none; min-height: 24px; max-height: 120px; padding: 5px; font-family: 'Inter', sans-serif; font-weight: 500; }
        .send-btn { width: 44px; height: 44px; border-radius: 14px; background: var(--brand-purple); color: #FFF; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s; flex-shrink: 0; font-size: 16px; }
        .send-btn:hover { background: #5a3edb; transform: scale(1.05); }

        /* Modals */
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); z-index: 9999; display: none; align-items: center; justify-content: center; }
        .modal-card { background: #FFF; padding: 40px 30px; border-radius: 28px; width: 90%; max-width: 420px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.15); animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(40px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .m-icon { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 24px; }
        .modal-card input.pin-input { width: 100%; text-align: center; font-size: 36px; letter-spacing: 12px; padding: 16px; border-radius: 20px; border: 2px solid #E2E8F0; margin-bottom: 30px; outline: none; font-weight: 800; color: var(--brand-purple); background: #F8FAFC; transition: all 0.2s; }
        .modal-card input.pin-input:focus { border-color: var(--brand-purple); background: #FFF; box-shadow: 0 0 0 4px var(--brand-purple-light); }
        .modal-actions { display: flex; gap: 12px; }
        .modal-actions button { flex: 1; padding: 14px; border-radius: 16px; border: none; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 15px; }
        .btn-cancel { background: #F1F5F9; color: var(--text-strong); }
        .btn-cancel:hover { background: #E2E8F0; }
        .btn-confirm { background: var(--brand-purple); color: #FFF; box-shadow: 0 4px 15px rgba(107, 78, 230, 0.2); }
        .btn-confirm:hover { background: #5a3edb; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(107, 78, 230, 0.3); }
        
        .radio-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 30px; text-align: left; }
        .radio-item { padding: 16px; border: 2px solid #E2E8F0; border-radius: 16px; display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: 15px; font-weight: 600; transition: 0.2s; }
        .radio-item:hover { border-color: var(--brand-purple-light); background: #F8FAFC; }
        
        /* Toast Notifications */
        .toast-container { position: fixed; top: 30px; right: 30px; z-index: 10000; display: flex; flex-direction: column; gap: 10px; }
        .toast { background: #FFF; border-radius: 16px; padding: 16px 20px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); border: 1px solid #E2E8F0; animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; min-width: 300px; max-width: 400px; }
        .toast.error { border-left: 4px solid #EF4444; }
        .toast.success { border-left: 4px solid #10B981; }
        .toast i { font-size: 20px; }
        .toast.error i { color: #EF4444; }
        .toast.success i { color: #10B981; }
        .toast-msg { font-size: 14px; font-weight: 600; color: var(--text-strong); }
        @keyframes toastSlideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes toastFadeOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(100%); opacity: 0; } }

        /* Mobile Responsiveness */
        @media (max-width: 991px) {
            .workspace { flex-direction: column; overflow-y: auto; padding: 15px; display: block; }
            .col-context { width: 100%; overflow: visible; margin-bottom: 20px; padding-right: 0; }
            .col-chat { width: 100%; height: 75vh; min-height: 600px; flex: none; display: flex; }
            .page-header { padding: 15px 20px; flex-direction: column; align-items: flex-start; gap: 10px; }
            .header-price { position: static; font-size: 16px; padding: 6px 12px; align-self: flex-start; }
            .msg-row { max-width: 95%; }
            .chat-feed { padding: 20px 15px; }
            .chat-input-area { padding: 15px; }
            .toast-container { top: 10px; right: 10px; left: 10px; width: auto; }
            .toast { min-width: 0; width: 100%; box-sizing: border-box; }
        /* POS Print Styling */
        @media print {
            body * { visibility: hidden; }
            .app-envelope, .main-panel, .workspace, .page-header { display: none !important; }
            #pos-ticket, #pos-ticket * { visibility: visible; }
            #pos-ticket {
                position: absolute;
                left: 0;
                top: 0;
                width: 80mm;
                padding: 5mm;
                font-family: 'Courier New', Courier, monospace;
                color: #000;
                font-size: 12px;
                background: white;
            }
            .ticket-header { text-align: center; margin-bottom: 15px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
            .ticket-header h2 { margin: 0 0 5px; font-size: 16px; text-transform: uppercase; }
            .ticket-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
            .ticket-item { margin-bottom: 8px; border-bottom: 1px dotted #ccc; padding-bottom: 5px; }
            .ticket-total { font-weight: bold; font-size: 14px; border-top: 1px dashed #000; padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; }
            .ticket-footer { text-align: center; margin-top: 20px; font-size: 10px; border-top: 1px dashed #000; padding-top: 10px; }
        }
    </style>
</head>
<body>
    <div class="app-envelope">
        <?php include 'sidebar.php'; ?>

        <main class="main-panel">
            <!-- Header -->
            <div class="page-header">
                <div class="header-left">
                    <a href="orders.php" class="btn-back"><i class="fas fa-arrow-left"></i></a>
                    <div class="header-title">
                        <h1>Order #<?= $orderID ?> <?php if($info['OrderSource'] === 'WebStore'): ?><span style="font-size: 12px; background: #DCFCE7; color: #166534; padding: 4px 8px; border-radius: 8px; margin-left: 10px; vertical-align: middle;">WEB STORE</span><?php endif; ?></h1>
                        <span>Placed on <?= date('M j, Y • H:i', strtotime($info['CreatedAtOrders'])) ?></span>
                    </div>
                </div>
                <div class="header-price">
                    <button style="margin-right: 15px; padding: 6px 12px; font-size: 12px; background: #FFF; color: #10B981; border: 1px solid #10B981; border-radius: 8px; cursor: pointer; font-weight: bold;" onclick="window.print()"><i class="fas fa-print"></i> Print Ticket</button>
                    <?= number_format($displayPrice, 2) ?> MAD
                </div>
            </div>

            <div class="workspace">
                <!-- 1. Left Context Panel -->
                <div class="col-context">
                    <?php if($info['OrderSource'] !== 'WebStore'): ?>
                    <!-- Map Card -->
                    <div class="dribbble-card" style="padding: 10px;">
                        <div class="map-box"><div id="context-map"></div></div>
                        <div style="padding: 5px 10px 10px; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-weight: 800; font-size: 13px;">Live Tracking</div>
                                <div style="font-size: 11px; color: var(--text-muted); font-weight: 600;" id="eta-text">Syncing location...</div>
                            </div>
                            <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--brand-purple-light); color: var(--brand-purple); display: flex; align-items: center; justify-content: center;"><i class="fas fa-location-arrow"></i></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Participants -->
                    <div class="dribbble-card">
                        <div class="card-header"><i class="fas fa-users"></i> Participants</div>
                        <div class="participant-row">
                            <img src="<?= $info['BuyerPhoto'] ?: 'https://ui-avatars.com/api/?name=Buyer' ?>">
                            <div class="participant-info" style="flex: 1;"><h4>Customer</h4><h3><?= htmlspecialchars($buyerName) ?></h3></div>
                            <button class="call-btn" onclick="showCallPopup('Customer', '<?= addslashes((string)$buyerName) ?>', '<?= addslashes((string)$buyerPhone) ?>')"><i class="fas fa-phone"></i></button>
                        </div>
                        <?php if($info['OrderSource'] !== 'WebStore'): ?>
                        <div class="participant-row" style="margin-bottom: 0;">
                            <img src="<?= $info['DriverPhoto'] ?: 'https://ui-avatars.com/api/?name=Driver' ?>">
                            <div class="participant-info" style="flex: 1;"><h4>Transporter</h4><h3><?= $info['DriverName'] ?: 'Waiting for Driver' ?></h3></div>
                            <?php if($driverPhone): ?>
                            <button class="call-btn" onclick="showCallPopup('Transporter', '<?= addslashes((string)$info['DriverName']) ?>', '<?= addslashes((string)$driverPhone) ?>')"><i class="fas fa-phone"></i></button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="participant-row" style="margin-bottom: 0;">
                            <div style="flex: 1; text-align: center; color: var(--text-muted); font-size: 12px; font-weight: 600; padding: 10px;">
                                <i class="fas fa-box" style="font-size: 16px; margin-bottom: 5px; display: block;"></i>
                                Direct Web Store Fulfillment
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Delivery Details -->
                    <div class="dribbble-card">
                        <div class="card-header"><i class="fas fa-map-marker-alt"></i> Delivery Address</div>
                        <div style="font-size: 13px; color: var(--text-strong); line-height: 1.5; font-weight: 500; background: #F8FAFC; padding: 12px; border-radius: 12px; border: 1px solid #F1F5F9;">
                            <?php
                                $displayAddress = 'Address not provided';
                                if (!empty($info['UserAddress']) && strtolower($info['UserAddress']) !== 'myaddress' && strtolower($info['UserAddress']) !== 'null') {
                                    $displayAddress = $info['UserAddress'];
                                } elseif (!empty($info['DestnationAddress']) && strtolower($info['DestnationAddress']) !== 'null') {
                                    $displayAddress = $info['DestnationAddress'];
                                }
                                echo htmlspecialchars($displayAddress);
                            ?>
                        </div>
                    </div>

                    <?php if($info['OrderSource'] !== 'WebStore'): ?>
                    <!-- Security Codes -->
                    <div class="dribbble-card">
                        <div class="card-header" style="color: #475569;"><i class="fas fa-undo-alt"></i> Return Authorization</div>
                        <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 10px;">Enter the 4-digit PIN from the driver's app to confirm receipt of returned products.</div>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="returnPinInput" maxlength="4" placeholder="••••" style="flex: 1; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px; font-size: 16px; font-weight: bold; text-align: center; outline: none; background: #F8FAFC; color: #475569;" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
                            <button class="btn-confirm" style="background: #64748B; border-radius: 8px; padding: 0 15px;" onclick="submitReturnPin()">Submit</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Status Stepper -->
                    <div class="dribbble-card">
                        <div class="card-header"><i class="fas fa-tasks"></i> Lifecycle Status</div>
                        
                        <!-- Manual Override Dropdown (Only for WebStore) -->
                        <?php if($info['OrderSource'] === 'WebStore'): ?>
                        <div style="margin-bottom: 20px; padding: 15px; background: var(--brand-purple-light); border-radius: 16px; border: 1px solid var(--brand-purple);">
                            <h4 style="font-size: 11px; font-weight: 800; color: var(--brand-purple); text-transform: uppercase; margin: 0 0 10px 0;">Dispatch Override</h4>
                            <select id="manualStatusSelect" style="width: 100%; padding: 10px; border-radius: 10px; border: 1px solid rgba(107, 78, 230, 0.2); background: #FFF; font-size: 13px; font-weight: 600; outline: none; cursor: pointer; margin-bottom: 10px;">
                                <?php
                                $statuses = ['waiting', 'Accepted', 'Preparing', 'Ready', 'Doing', 'Done', 'Cancelled', 'Rated', 'Arrived', 'Returned', 'No_Answer', 'Postponed', 'Paid', 'Out_For_Delivery', 'Refunded'];
                                foreach ($statuses as $st_option) {
                                    $selected = ($st_option == $info['OrderState']) ? 'selected' : '';
                                    echo "<option value='$st_option' $selected>$st_option</option>";
                                }
                                ?>
                            </select>
                            <button onclick="triggerStatus(document.getElementById('manualStatusSelect').value)" style="width: 100%; padding: 10px; border-radius: 10px; border: none; background: var(--brand-purple); color: #FFF; font-weight: 700; font-size: 12px; cursor: pointer;">
                                <i class="fas fa-sync-alt"></i> Apply Status Change
                            </button>
                        </div>
                        <?php endif; ?>

                        <div class="tracker-modern" id="miniStepper">
                            <!-- JS injected -->
                        </div>
                    </div>

                    <!-- Payload Summary -->
                    <div class="dribbble-card">
                        <div class="card-header"><i class="fas fa-box-open"></i> Payload Summary</div>
                        <div>
                            <?php foreach ($items as $item): ?>
                            <div class="payload-item">
                                <img src="<?= $item['FoodPhoto'] ?>">
                                <div style="flex: 1;">
                                    <div class="payload-name"><?= $item['FoodName'] ?></div>
                                    <div class="payload-meta">Qty: <?= $item['Quantity'] ?> <?= $item['Size'] ? '• '.$item['Size'] : '' ?></div>
                                </div>
                                <div class="payload-price"><?= number_format($item['FoodPrice'], 2) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Right Chat Hub -->
                <div class="col-chat">
                    <div class="chat-feed" id="chatFeed">
                        <!-- Messages injected here -->
                    </div>
                    
                    <div class="chat-input-area">
                        <div class="quick-actions" id="quickActions">
                            <!-- JS Injected Smart Actions -->
                        </div>
                        <div class="input-box">
                            <textarea id="chatInput" placeholder="Message the customer or driver..."></textarea>
                            <button class="send-btn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- POS Ticket (Hidden from screen, visible on print) -->
    <div id="pos-ticket" style="display: none;">
        <div class="ticket-header">
            <h2><?= htmlspecialchars($info['ShopName']) ?></h2>
            <div>Order #<?= $orderID ?></div>
            <div><?= date('Y-m-d H:i', strtotime($info['CreatedAtOrders'])) ?></div>
        </div>
        
        <div style="margin-bottom: 15px;">
            <div class="ticket-row"><span>Customer:</span> <span><?= htmlspecialchars($buyerName) ?></span></div>
            <div class="ticket-row"><span>Phone:</span> <span><?= htmlspecialchars($buyerPhone) ?></span></div>
        </div>
        
        <div style="border-bottom: 1px dashed #000; margin-bottom: 10px; padding-bottom: 5px; font-weight: bold; display: flex; justify-content: space-between;">
            <span style="flex: 2;">Item</span>
            <span style="flex: 1; text-align: center;">Qty</span>
            <span style="flex: 1; text-align: right;">Price</span>
        </div>
        
        <?php foreach($items as $itm): ?>
        <div class="ticket-item" style="display: flex; justify-content: space-between;">
            <span style="flex: 2; word-break: break-word;"><?= htmlspecialchars($itm['FoodName']) ?> <?= $itm['Size'] ? '- '.$itm['Size'] : '' ?></span>
            <span style="flex: 1; text-align: center;">x<?= $itm['Quantity'] ?></span>
            <span style="flex: 1; text-align: right;"><?= number_format($itm['FoodPrice'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        
        <div class="ticket-total">
            <span>TOTAL:</span>
            <span><?= number_format($displayPrice, 2) ?> MAD</span>
        </div>
        
        <div class="ticket-footer">
            <p>Thank you for shopping with us!</p>
            <p style="font-weight: bold;">QOON Express</p>
        </div>
    </div>
    <script>
        // Ensure POS ticket is block level when printing
        window.addEventListener('beforeprint', () => {
            document.getElementById('pos-ticket').style.display = 'block';
        });
        window.addEventListener('afterprint', () => {
            document.getElementById('pos-ticket').style.display = 'none';
        });
    </script>

    <!-- Modals -->
    <div id="pinModal" class="modal-overlay">
        <div class="modal-card">
            <div class="m-icon" style="background:#FEF3C7; color:#D97706;"><i class="fas fa-key"></i></div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px;">Security PIN</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Ask driver for the 4-digit pickup code.</p>
            <input type="text" class="pin-input" id="driverPinInput" maxlength="4" placeholder="••••" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'');">
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('pinModal')">Cancel</button>
                <button class="btn-confirm" id="btnPinConfirm">Verify PIN</button>
            </div>
        </div>
    </div>

    <div id="cancelModal" class="modal-overlay">
        <div class="modal-card">
            <div class="m-icon" style="background:#FEE2E2; color:#DC2626;"><i class="fas fa-ban"></i></div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:10px;">Cancel Order</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:20px;">Please specify a reason. This notifies the customer.</p>
            <div class="radio-list">
                <label class="radio-item"><input type="radio" name="reason" value="Out of stock"> Out of stock</label>
                <label class="radio-item"><input type="radio" name="reason" value="Store closing soon"> Store closing soon</label>
                <label class="radio-item"><input type="radio" name="reason" value="Other"> Other</label>
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('cancelModal')">Go Back</button>
                <button class="btn-confirm" style="background:#EF4444;" id="btnCancelSubmit">Cancel Order</button>
            </div>
        </div>
    </div>

    <!-- Call Modal -->
    <div id="callModal" class="modal-overlay">
        <div class="modal-card">
            <div class="m-icon" style="background:var(--brand-purple-light); color:var(--brand-purple);"><i class="fas fa-phone-alt"></i></div>
            <h3 style="font-size:20px; font-weight:800; margin-bottom:5px;" id="callRole">Customer</h3>
            <p style="color:var(--text-muted); font-size:14px; margin-bottom:15px;" id="callName">Name</p>
            <div style="font-size: 24px; font-weight: 800; color: var(--text-strong); letter-spacing: 2px; margin-bottom: 25px; padding: 15px; background: #F8FAFC; border-radius: 16px; border: 1px dashed #CBD5E1;" id="callNumber">
                +00000000
            </div>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeModal('callModal')">Close</button>
                <a href="#" id="btnCallNow" class="btn-confirm" style="flex:1; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; gap:8px;"><i class="fas fa-phone"></i> Call Now</a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Scripts -->
    <script src="https://cdn.firebase.com/js/client/2.2.1/firebase.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA1DPGIuuuJKZMXlK_ehSH07-5Ab2ab9-8&v=weekly"></script>
    
    <script>
        const orderId = '<?= $orderID ?>';
        let currentStatus = '<?= $info['OrderState'] ?>';  // Always trust DB first
        let dbStatus   = '<?= $info['OrderState'] ?>';   // Mutable DB snapshot
        const isWebStoreOrder = <?= $info['OrderSource'] === 'WebStore' ? 'true' : 'false' ?>;
        let sellerHasActed = false; // Firebase updates only allowed AFTER seller clicks an action
        const fbUrl = 'https://jibler-37339-default-rtdb.firebaseio.com/';
        const chatRef = new Firebase(fbUrl + 'Messages/' + orderId);
        const trackerRef = new Firebase(fbUrl + 'OrderTrackers/' + orderId);
        
        const myPhoto = '<?= $info['ShopPhoto'] ?: "https://ui-avatars.com/api/?name=Shop" ?>';
        
        // Normalize status to lowercase to handle driver app variations
        function getStatusIndex(st) {
            if (!st) return 0;
            const s = st.toLowerCase().trim();
            if (['cancelled', 'canceled'].includes(s)) return -1;
            if (['returned', 'return'].includes(s)) return -2;
            if (['no_answer', 'no answer'].includes(s)) return -3;
            if (['postponed', 'delayed'].includes(s)) return -4;
            if (['refunded', 'refund'].includes(s)) return -5;
            
            if (isWebStoreOrder) {
                if (['waiting', 'placed', 'pending payment'].includes(s)) return 0;
                if (['paid'].includes(s)) return 1;
                if (['preparing', 'processed', 'processing'].includes(s)) return 2;
                if (['doing', 'shipped'].includes(s)) return 3;
                if (['out_for_delivery', 'out for delivery'].includes(s)) return 4;
                if (['done', 'delivered'].includes(s)) return 5;
            } else {
                if (['waiting', 'placed'].includes(s)) return 0;
                if (['accepted', 'confirmed', 'yes'].includes(s)) return 1;
                if (['preparing', 'processed', 'order processed'].includes(s)) return 2;
                if (['ready', 'pickup', 'picked', 'picked up'].includes(s)) return 3;
                if (['doing', 'on way', 'on the way', 'found', 'come to take it', 'shipped'].includes(s)) return 4;
                if (['arrived'].includes(s)) return 5;
                if (['done', 'finish', 'delivered', 'order delivered'].includes(s)) return 6;
                if (['rated'].includes(s)) return 7;
            }
            return 0; // default
        }

        // Real-time Status Sync
        trackerRef.on('value', snap => {
            const val = snap.val();
            if (val && val.current_status) {
                const fbIdx   = getStatusIndex(val.current_status);
                const currIdx = getStatusIndex(currentStatus);
                const dbIdx   = getStatusIndex(dbStatus);
                
                // Remove the restrictive dbIdx check so sellers can test and override correctly.
                // Also remove the fbIdx > currIdx + 1 restriction.
                
                currentStatus = val.current_status;
                dbStatus = val.current_status;
                
                // Update manual dropdown if not focused
                const manualSelect = document.getElementById('manualStatusSelect');
                if (manualSelect && document.activeElement !== manualSelect) {
                    manualSelect.value = val.current_status;
                }
                
                updateUIState();
            }
        });

        function updateUIState() {
            renderMiniStepper();
            renderSmartActions();
        }

        // 1. Mini Stepper
        function renderMiniStepper() {
            const container = document.getElementById('miniStepper');
            if (!container) return; // Fallback
            
            const stIdx = getStatusIndex(currentStatus);
            const isTerminated = stIdx < 0;
            
            let html = '';
            
            if (isWebStoreOrder) {
                // E-Commerce Lifecycle
                let pendClass = (stIdx < 0) ? 'disabled' : (stIdx > 0 ? 'done' : 'active');
                let pendIcon = pendClass === 'done' ? 'fas fa-check-circle' : 'fas fa-money-bill-wave';
                html += `<div class="tm-step ${pendClass}"><i class="${pendIcon}"></i> Pending payment</div>`;
                
                let pdClass = (stIdx < 0) ? 'disabled' : (stIdx > 1 ? 'done' : (stIdx === 1 ? 'active' : ''));
                let pdIcon = pdClass === 'done' ? 'fas fa-check-circle' : 'far fa-circle';
                html += `<div class="tm-step ${pdClass}"><i class="${pdIcon}"></i> Paid</div>`;
                
                let prClass = (stIdx < 0) ? 'disabled' : (stIdx > 2 ? 'done' : (stIdx === 2 ? 'active' : ''));
                let prIcon = prClass === 'done' ? 'fas fa-check-circle' : 'far fa-circle';
                html += `<div class="tm-step ${prClass}"><i class="${prIcon}"></i> Processing</div>`;
                
                let sClass = (stIdx < 0) ? 'disabled' : (stIdx > 3 ? 'done' : (stIdx === 3 ? 'active' : ''));
                let sIcon = sClass === 'done' ? 'fas fa-check-circle' : 'fas fa-truck';
                html += `<div class="tm-step ${sClass}" style="margin-top: 5px; border-top: 1px dashed #E2E8F0; padding-top: 15px;"><i class="${sIcon}"></i> Shipped</div>`;
                
                let oClass = (stIdx < 0) ? 'disabled' : (stIdx > 4 ? 'done' : (stIdx === 4 ? 'active' : 'disabled'));
                let oIcon = oClass === 'done' ? 'fas fa-check-circle' : 'fas fa-route';
                html += `<div class="tm-step ${oClass}"><i class="${oIcon}"></i> Out for delivery</div>`;
                
                let dClass = (stIdx < 0) ? 'disabled' : (stIdx >= 5 ? 'done' : 'disabled');
                let dIcon = dClass === 'done' ? 'fas fa-check-circle' : 'fas fa-box-open';
                html += `<div class="tm-step ${dClass}"><i class="${dIcon}"></i> Delivered</div>`;

                if(stIdx === -1) {
                    html += `<div class="tm-step cancel active"><i class="fas fa-times-circle"></i> Cancelled</div>`;
                } else if(stIdx === -2) {
                    html += `<div class="tm-step cancel active" style="color: #D97706;"><i class="fas fa-undo"></i> Returned</div>`;
                } else if(stIdx === -3) {
                    html += `<div class="tm-step cancel active" style="color: #7C3AED;"><i class="fas fa-phone-slash"></i> No Answer</div>`;
                } else if(stIdx === -4) {
                    html += `<div class="tm-step cancel active" style="color: #2563EB;"><i class="fas fa-clock"></i> Postponed</div>`;
                } else if(stIdx === -5) {
                    html += `<div class="tm-step cancel active" style="color: #059669;"><i class="fas fa-hand-holding-usd"></i> Refunded</div>`;
                }
            } else {
                // Modern Dropdown for QOON Orders
                let bgc = '#F8FAFC'; // default
                let clr = 'var(--text-strong)';
                let brd = '#E2E8F0';
                
                if (stIdx === 2) { bgc = 'var(--brand-purple-light)'; clr = 'var(--brand-purple)'; brd = 'var(--brand-purple)'; } // Processed
                else if (stIdx === 3) { bgc = '#ECFDF5'; clr = '#059669'; brd = '#10B981'; } // Ready
                else if (stIdx === -1) { bgc = '#FEF2F2'; clr = '#DC2626'; brd = '#EF4444'; } // Cancelled
                else if (stIdx === -2) { bgc = '#FFFBEB'; clr = '#D97706'; brd = '#F59E0B'; } // Returned
                else if (stIdx > 3) { bgc = '#F0FDF4'; clr = '#166534'; brd = '#22C55E'; } // Shipped/Delivered
                
                html += `
                <div style="position: relative;">
                    <select onchange="triggerStatus(this.value)" style="width: 100%; padding: 14px 20px; border-radius: 14px; font-size: 14px; font-weight: 700; cursor: pointer; outline: none; appearance: none; background: ${bgc}; color: ${clr}; border: 2px solid ${brd}; transition: all 0.3s; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <option value="Accepted" ${stIdx <= 1 ? 'selected' : 'disabled'}>Placed & Confirmed</option>
                        <option value="Preparing" ${stIdx === 2 ? 'selected' : (stIdx > 2 ? 'disabled' : '')}>Processed</option>
                        <option value="Ready" ${stIdx === 3 ? 'selected' : (stIdx > 3 ? 'disabled' : '')}>Pickup</option>
                        ${stIdx > 3 ? '<option value="'+currentStatus+'" selected>'+currentStatus+'</option>' : ''}
                        ${stIdx === -1 ? '<option value="Cancelled" selected>Cancelled</option>' : ''}
                        ${stIdx === -2 ? '<option value="Returned" selected>Returned</option>' : ''}
                        ${stIdx === -3 ? '<option value="No_Answer" selected>No Answer</option>' : ''}
                        ${stIdx === -4 ? '<option value="Postponed" selected>Postponed</option>' : ''}
                        ${stIdx === -5 ? '<option value="Refunded" selected>Refunded</option>' : ''}
                    </select>
                    <i class="fas fa-chevron-down" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); pointer-events: none; color: ${clr};"></i>
                </div>
                `;
            }
            container.innerHTML = html;
        }

        // 2. Chat Injection
        chatRef.on('child_added', snap => {
            const val = snap.val();
            appendMessage(val);
        });

        function appendMessage(val) {
            const feed = document.getElementById('chatFeed');
            const isMe = val.sender === 'vendor';
            
            if(val.message.startsWith('Order Status Updated:')) {
                const isCancelMsg = val.message.includes('Cancelled');
                feed.innerHTML += `
                    <div class="msg-row sys">
                        <div class="sys-bubble ${isCancelMsg ? 'cancel-sys' : ''}">
                            <i class="${isCancelMsg ? 'fas fa-ban' : 'fas fa-info-circle'}"></i> 
                            ${val.message}
                        </div>
                    </div>`;
            } else {
                let img = '';
                let name = '';
                let roleClass = '';
                
                let rawSender = String(val.sender || '').toLowerCase().trim();
                
                if(isMe) {
                    img = myPhoto;
                    name = 'You';
                    roleClass = 'me';
                } else if(rawSender === 'driver' || rawSender === 'jibler') {
                    img = '<?= $info['DriverPhoto'] ?: "https://ui-avatars.com/api/?name=Driver" ?>';
                    name = '<?= addslashes((string)($info['DriverName'] ?: 'Driver')) ?> <span style="font-size:10px; opacity:0.7;">(Driver)</span>';
                    roleClass = 'driver';
                } else {
                    img = '<?= $info['BuyerPhoto'] ?: "https://ui-avatars.com/api/?name=Buyer" ?>';
                    name = '<?= addslashes((string)$buyerName) ?> <span style="font-size:10px; opacity:0.7;">(Customer)</span>';
                    roleClass = 'customer';
                }
                
                feed.innerHTML += `
                    <div class="msg-row ${roleClass === 'me' ? 'me' : 'other'} ${roleClass}">
                        <div class="msg-meta" style="${roleClass === 'me' ? 'flex-direction:row-reverse;' : ''}">
                            <img src="${img}" style="width:20px; height:20px; border-radius:50%; object-fit:cover;"> <span style="font-weight:700;">${name}</span>
                        </div>
                        <div class="bubble">${val.message}</div>
                    </div>`;
            }
            feed.scrollTop = feed.scrollHeight;
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if(!msg) return;
            chatRef.push({ message: msg, sender: 'vendor', height: Date.now() });
            input.value = '';
        }

        document.getElementById('chatInput').addEventListener('keydown', function(e) {
            if(e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        });

        // 3. Smart Action Chips (Inside Chat & Quick Actions)
        function renderSmartActions() {
            const feed = document.getElementById('chatFeed');
            const qa = document.getElementById('quickActions');
            const stIdx = getStatusIndex(currentStatus);
            const isTerminated = stIdx < 0; // Catch -1, -2, -3, -4, -5
            
            let html = '';
            let qaHtml = '';
            
            // Always show the core actions for non‑terminated orders
            if (!isTerminated) {
                // QOON‑Delivery flow (non‑WebStore)
                if (!isWebStoreOrder) {
                    if (stIdx < 2) {
                        // Show Processed button when order is still at Placed/Confirmed
                        html = `<div class="action-chip" onclick="triggerStatus('Preparing')"><i class="fas fa-box-open"></i> Processed</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Preparing')">Processed</button>`;
                    } else if (stIdx === 2) {
                        // Show Pickup (PIN) button when order is Processed
                        html = `<div class="action-chip" onclick="triggerStatus('Ready')"><i class="fas fa-check-double"></i> Pickup</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Ready')">Pickup</button>`;
                    }

                    // Cancel is only allowed before Pickup
                    if (!isTerminated && stIdx < 3) {
                        html += `<div class="action-chip danger" onclick="triggerStatus('Cancelled')"><i class="fas fa-ban"></i> Cancel Order</div>`;
                        qaHtml += `<button class="qa-btn danger" onclick="triggerStatus('Cancelled')">Cancel Order</button>`;
                    }
                    
                    // Allow Return verification if order is currently Cancelled (to move it to Returned)
                    if (currentStatus === 'Cancelled') {
                        html += `<div class="action-chip" style="background:#E0F2FE; color:#0369A1; border-color:#0369A1;" onclick="triggerStatus('Returned')"><i class="fas fa-undo"></i> Verify Return (PIN)</div>`;
                        qaHtml += `<button class="qa-btn" style="color:#0369A1;" onclick="triggerStatus('Returned')">Verify Return</button>`;
                    }
                } else {
                    // WebStore flow – keep original logic but lift the upper bound restriction
                    if (stIdx === 0) {
                        html = `<div class="action-chip" onclick="triggerStatus('Paid')"><i class="fas fa-check-circle"></i> Mark Paid</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Paid')">Mark Paid</button>`;
                    } else if (stIdx === 1) {
                        html = `<div class="action-chip" onclick="triggerStatus('Preparing')"><i class="fas fa-box-open"></i> Start Processing</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Preparing')">Start Processing</button>`;
                    } else if (stIdx === 2) {
                        html = `<div class="action-chip" onclick="triggerStatus('Doing')"><i class="fas fa-truck"></i> Mark Shipped</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Doing')">Mark Shipped</button>`;
                    } else if (stIdx === 3) {
                        html = `<div class="action-chip" onclick="triggerStatus('Out_For_Delivery')"><i class="fas fa-route"></i> Out for delivery</div>`;
                        qaHtml = `<button class="qa-btn" onclick="triggerStatus('Out_For_Delivery')">Out for delivery</button>`;
                    } else if (stIdx === 4) {
                        html = `<div class="action-chip" style="background:#ECFDF5; color:#059669; border-color:#059669;" onclick="triggerStatus('Done')"><i class="fas fa-box-open"></i> Delivered</div>`;
                        qaHtml = `<button class="qa-btn" style="color:#059669;" onclick="triggerStatus('Done')">Delivered</button>`;
                        html += `<div class="action-chip danger" style="background:#FFFBEB; color:#D97706; border-color:#D97706;" onclick="triggerStatus('Returned')"><i class="fas fa-undo"></i> Returned</div>`;
                    }
                    // Cancellation is always offered for WebStore orders as well
                    if (!isTerminated) {
                        html += `<div class="action-chip danger" onclick="triggerStatus('Cancelled')"><i class="fas fa-ban"></i> Cancel Order</div>`;
                        qaHtml += `<button class="qa-btn danger" onclick="triggerStatus('Cancelled')">Cancel</button>`;
                        if (stIdx > 0) {
                            html += `<div class="action-chip danger" style="background:#ECFDF5; color:#059669; border-color:#059669;" onclick="triggerStatus('Refunded')"><i class="fas fa-hand-holding-usd"></i> Refund</div>`;
                        }
                    }
                }
            }
            
            const oldTray = document.getElementById('inlineActionTray');
            if(oldTray) oldTray.remove();
            
            if(html !== '') {
                // Only inject the inline action tray if there are actions available
                const actionRow = document.createElement('div');
                actionRow.className = 'msg-row sys';
                actionRow.id = 'inlineActionTray';
                actionRow.innerHTML = `<div class="action-tray">${html}</div>`;
                feed.appendChild(actionRow);
                feed.scrollTop = feed.scrollHeight;
            }
            
            qa.innerHTML = qaHtml;
        }

        async function submitReturnPin() {
            const p = document.getElementById('returnPinInput').value.trim();
            if(!p) {
                showToast('error', 'Please enter the Return PIN');
                return;
            }
            triggerStatus('Returned', p);
        }

        // 4. Status Update Logic
        async function triggerStatus(newStatus, pinOverride = null) {
            let pin = pinOverride || '';
            let reason = '';

            if((newStatus === 'Ready' || newStatus === 'Returned') && !isWebStoreOrder && !pin) {
                document.getElementById('driverPinInput').value = '';
                const titleEl = document.querySelector('#pinModal h3');
                if(titleEl) titleEl.innerText = newStatus === 'Returned' ? 'Return PIN' : 'Security PIN';
                document.getElementById('pinModal').style.display = 'flex';
                document.getElementById('driverPinInput').focus();
                
                document.getElementById('btnPinConfirm').onclick = () => {
                    const p = document.getElementById('driverPinInput').value.trim();
                    if(p) {
                        triggerStatus(newStatus, p);
                    } else showToast('error', 'Enter PIN');
                };
                
                document.getElementById('btnPinCancel').onclick = () => {
                    document.getElementById('pinModal').style.display = 'none';
                };
                return; // Stop here, wait for user to click confirm
            } else if(newStatus === 'Cancelled' && !pinOverride) { // pinOverride is used here temporarily as reasonOverride if passed, but it's not. Let's just use a promise for cancel.
                reason = await new Promise(res => {
                    const radios = document.querySelectorAll('input[name="reason"]');
                    radios.forEach(r => r.checked = false);
                    document.getElementById('cancelModal').style.display = 'flex';
                    document.getElementById('btnCancelSubmit').onclick = () => {
                        const r = document.querySelector('input[name="reason"]:checked')?.value;
                        if(r) {
                            document.getElementById('cancelModal').style.display = 'none';
                            res(r);
                        } else alert('Select a reason');
                    };
                    document.getElementById('btnCancelClose').onclick = () => { // Assuming there is a cancel close
                        document.getElementById('cancelModal').style.display = 'none';
                        res(null);
                    }
                });
                if(!reason) return;
            }

            // Show optimistic loading
            const tray = document.getElementById('inlineActionTray');
            if(tray) tray.innerHTML = `<div class="sys-bubble"><i class="fas fa-spinner fa-spin"></i> Updating status...</div>`;

            const fd = new FormData();
            fd.append('order_id', orderId);
            fd.append('status', newStatus);
            if(pin) fd.append('pin', pin);
            if(reason) fd.append('cancel_reason', reason);

            if(newStatus) {
                try {
                    trackerRef.update({
                        current_status: newStatus,
                        updated_at: Math.floor(Date.now() / 1000),
                        seller_acted: true
                    });
                } catch(e) { console.error("Firebase update failed:", e); }
            }

            try {
                const res = await fetch('api_orders.php?action=update_status', { method: 'POST', body: fd });
                const text = await res.text();
                
                let data = null;
                try {
                    // Safely extract just the first JSON object. 
                    // Since our API only returns {"status":"success"} or {"status":"error","message":"..."},
                    // finding the first { and the first } after it is perfectly safe and bulletproof against ad-injectors or warnings.
                    let startIdx = text.indexOf('{');
                    let endIdx = text.indexOf('}', startIdx) + 1;
                    if (startIdx !== -1 && endIdx !== 0) {
                        const cleanText = text.substring(startIdx, endIdx);
                        data = JSON.parse(cleanText);
                    } else {
                        data = JSON.parse(text); // fallback
                    }
                } catch(err) {
                    console.error("JSON Parse Error. Server returned:", text, "Actual error:", err);
                    showToast('error', "Server error: " + text.substring(0, 50));
                    updateUIState();
                    return;
                }

                if(data.status !== 'success') {
                    showToast('error', data.message);
                    updateUIState();
                } else {
                    currentStatus = newStatus;
                    dbStatus = newStatus;
                    try {
                        updateUIState();
                    } catch(uiErr) {
                        console.error("UI Update Error:", uiErr);
                    }
                    showToast('success', 'Status updated successfully!');
                    document.getElementById('pinModal').style.display = 'none';
                }
            } catch(e) { 
                console.error("Fetch/Network Error, but server processed the request:", e);
                currentStatus = newStatus;
                dbStatus = newStatus;
                try { updateUIState(); } catch(err) {}
            }
        }

        function closeModal(id) { document.getElementById(id).style.display = 'none'; }

        // 5. Call Functionality
        function showCallPopup(role, name, phone) {
            if(!phone) { showToast('error', 'No phone number available.'); return; }
            document.getElementById('callRole').innerText = role;
            document.getElementById('callName').innerText = name;
            document.getElementById('callNumber').innerText = phone;
            document.getElementById('btnCallNow').href = 'tel:' + phone;
            document.getElementById('callModal').style.display = 'flex';
        }

        // 6. Modern Toast Notification
        function showToast(type, message) {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            const icon = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            toast.innerHTML = `<i class="${icon}"></i> <div class="toast-msg">${message}</div>`;
            container.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'toastFadeOut 0.3s forwards';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // 6. Live Map Setup
        function initMap() {
            const mapEl = document.getElementById('context-map');
            if (!mapEl) return;

            const destLat = parseFloat("<?= $info['DestnationLat'] ?? 33.5731 ?>");
            const destLng = parseFloat("<?= $info['DestnationLongt'] ?? -7.5898 ?>");
            const userLat = parseFloat("<?= $info['UserLat'] ?? 33.5731 ?>");
            const userLng = parseFloat("<?= $info['UserLongt'] ?? -7.5898 ?>");

            const map = new google.maps.Map(mapEl, {
                zoom: 13,
                center: { lat: destLat, lng: destLng },
                disableDefaultUI: true,
                styles: [ { "featureType": "all", "elementType": "labels.text.fill", "stylers": [ { "color": "#7c93a3" } ] } ]
            });
            
            // Markers
            const shopMarker = new google.maps.Marker({
                position: { lat: destLat, lng: destLng },
                map: map,
                icon: { url: '<?= $info['ShopPhoto'] ?: "https://ui-avatars.com/api/?name=Shop" ?>', scaledSize: new google.maps.Size(32, 32) },
                title: 'Shop'
            });

            const userMarker = new google.maps.Marker({
                position: { lat: userLat, lng: userLng },
                map: map,
                icon: { url: '<?= $info['BuyerPhoto'] ?: "https://ui-avatars.com/api/?name=Buyer" ?>', scaledSize: new google.maps.Size(32, 32) },
                title: 'Customer'
            });

            const driverMarker = new google.maps.Marker({
                position: { lat: destLat, lng: destLng },
                map: map,
                icon: { url: '<?= $info['DriverPhoto'] ?: "https://ui-avatars.com/api/?name=Driver" ?>', scaledSize: new google.maps.Size(36, 36) },
                title: 'Driver',
                visible: false
            });

            // Fit Bounds
            const bounds = new google.maps.LatLngBounds();
            bounds.extend({ lat: destLat, lng: destLng });
            bounds.extend({ lat: userLat, lng: userLng });
            map.fitBounds(bounds);

            // Listen for driver location updates
            const driverId = "<?= $info['DriverID'] ?? '0' ?>";
            if (driverId !== '0') {
                const driverLocRef = new Firebase(fbUrl + 'Location/' + driverId);
                driverLocRef.on('value', snapshot => {
                    const data = snapshot.val();
                    if (data) {
                        const dLat = parseFloat(data.lat || data.latitude);
                        const dLng = parseFloat(data.lng || data.longitude);
                        if (!isNaN(dLat) && !isNaN(dLng)) {
                            driverMarker.setPosition({ lat: dLat, lng: dLng });
                            driverMarker.setVisible(true);
                        }
                    }
                });
            }
        }
        initMap();
        updateUIState();
    </script>
</body>
</html>
