<?php
if(isset($_GET['ajax']) && $_GET['ajax'] == 'offers') {
    require_once 'conn.php';
    $orderId = isset($_GET['orderId']) ? mysqli_real_escape_string($con, $_GET['orderId']) : '0';
    $offers = [];
    
    if($con) {
        try {
            // Fetch Driver Commission from Dash
            $commRes = $con->query("SELECT DriverCommesion FROM MoneyStop LIMIT 1");
            $dashboardCommission = 0;
            if($commRes && $commRes->num_rows > 0) {
                $commRow = $commRes->fetch_assoc();
                $dashboardCommission = floatval($commRow['DriverCommesion']);
            }

            // Fetch EXACT bids from Firebase Realtime DB since Zeewana API does not save them in MySQL
            $firebaseUrl = "https://jibler-37339-default-rtdb.firebaseio.com/Offers/$orderId.json";
            
            // Suppress warnings in case Firebase node is completely missing (returns null/404 payload)
            $fbData = @file_get_contents($firebaseUrl);
            
            if($fbData !== false) {
                $fbJson = json_decode($fbData, true);
                
                if(is_array($fbJson)) {
                    // Reverse tracking to show newest offers first
                    $fbJsonReversed = array_reverse($fbJson, true);
                    foreach($fbJsonReversed as $key => $offerNode) {
                        if($key === "OrderStatus" || !is_array($offerNode)) continue;
                        
                        // Parse offer properties from Firebase notification payload
                        $fName = $offerNode['sender'] ?? 'Driver';
                        $driverId = $offerNode['id'] ?? '';
                        $offerVal = floatval($offerNode['Offer'] ?? 0);
                        $rate = $offerNode['rate'] ?? '';
                        $img = $offerNode['driverphoto'] ?? '';
                        
                        if($offerVal > 0) {
                            $offers[] = [
                                'id' => $driverId,
                                'offerKey' => $key,
                                'name' => $fName,
                                'rating' => $rate ? $rate : number_format(rand(45,49)/10, 1),
                                'distance' => number_format(rand(10,30)/10, 1) . ' km',
                                'time' => rand(3, 15) . ' min',
                                'price' => $offerVal, // Driver Bid (already includes commission from addoffer.php)
                                'img' => (!empty($img) && strpos($img, 'http') !== false) ? $img : "https://ui-avatars.com/api/?name=".urlencode($fName)."&background=random"
                            ];
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            $offers[] = ["error" => $e->getMessage()];
        }
    }
    
    // Strict Mode: No fallback data. UI will wait until a real offer is submitted via Driver App for this OrderId.
    
    header('Content-Type: application/json');
    echo json_encode(["offers" => $offers]);
    exit;
}

// Intercept new arrivals (from checkout) that don't have an orderId yet.
// Create a live order in the DB and ping drivers!
if (!isset($_GET['orderId'])) {
    require_once 'conn.php';
    if ($con) {
        $total = isset($_REQUEST['total']) ? floatval($_REQUEST['total']) : 50;
        $platformFee = isset($_REQUEST['platformFee']) ? floatval($_REQUEST['platformFee']) : 0;
        
        // 1. Detect actual Logged-in User or Fallback to Guest
        $userId = $_COOKIE['qoon_user_id'] ?? 9999;
        
        // Ensure guest user exists if needed
        if($userId == 9999) {
            $con->query("INSERT IGNORE INTO Users (UserID, name, PhoneNumber, Email, UserOrdersNum) VALUES (9999, 'QOON Guest', '+212600000000', 'test@qoon.app', 1)");
        }

        // 2. Fetch Shop coordinates
        $postedShopId = isset($_POST['shopId']) ? $con->real_escape_string($_POST['shopId']) : '1';
        $postedShopName = isset($_POST['shopName']) ? $con->real_escape_string($_POST['shopName']) : 'QOON Boutique';
        
        $shopLat = '33.5731';
        $shopLng = '-7.5898';
        $shopLogo = '0';
        $shopRes = $con->query("SELECT ShopLat, ShopLongt, ShopLogo FROM Shops WHERE ShopID = '$postedShopId' LIMIT 1");
        if ($shopRes && $shopRes->num_rows > 0) {
            $sRow = $shopRes->fetch_assoc();
            if(!empty($sRow['ShopLat'])) $shopLat = $sRow['ShopLat'];
            if(!empty($sRow['ShopLongt'])) $shopLng = $sRow['ShopLongt'];
            if(!empty($sRow['ShopLogo'])) $shopLogo = $sRow['ShopLogo'];
        }

        // Fetch User coordinates from the checkout payload (or fallback to shop location)
        $userLat = isset($_POST['addrLat']) && !empty($_POST['addrLat']) ? $con->real_escape_string($_POST['addrLat']) : $shopLat;
        $userLng = isset($_POST['addrLon']) && !empty($_POST['addrLon']) ? $con->real_escape_string($_POST['addrLon']) : $shopLng;

        // 2.5 CLEAR previous pending orders for this specific user to maintain trackability
        $con->query("UPDATE Orders SET OrderState='Cancelled' WHERE UserID='$userId' AND OrderState='waiting'");

        $userName = 'Customer';
        $userPhone = '';
        $userPhoto = '';
        $userRes = $con->query("SELECT name, PhoneNumber, UserPhoto FROM Users WHERE UserID = '$userId' LIMIT 1");
        if ($userRes && $userRes->num_rows > 0) {
            $uRow = $userRes->fetch_assoc();
            if(!empty($uRow['name'])) $userName = $uRow['name'];
            if(!empty($uRow['PhoneNumber'])) $userPhone = $uRow['PhoneNumber'];
            if(!empty($uRow['UserPhoto'])) $userPhoto = $uRow['UserPhoto'];
        }
        
        // Ensure driver always sees a picture even for guests/users without photos
        if(empty($userPhoto)) {
            $userPhoto = "https://ui-avatars.com/api/?name=".urlencode($userName)."&background=random";
        }

        // Parse actual cart mapping from shop.php
        $postedCartStr = isset($_POST['cart']) ? $_POST['cart'] : '[]';
        $postedCart = json_decode($postedCartStr, true);
        if(!is_array($postedCart)) $postedCart = [];

        // Generate text string for the OrderDetails column so it doesn't show old dummy data
        $orderDetailsStr = '';
        if (count($postedCart) > 0) {
            $detailsArr = [];
            foreach($postedCart as $cItem) {
                $qty = isset($cItem['qty']) ? $cItem['qty'] : 1;
                $name = isset($cItem['name']) ? $cItem['name'] : 'Item';
                $detailsArr[] = $qty . ' ' . $name;
            }
            $orderDetailsStr = implode(', ', $detailsArr);
        } else {
            $orderDetailsStr = 'QOON Order';
        }

        // Parse payment method from POST
        $postedPayMethod = isset($_POST['payMethod']) ? $con->real_escape_string($_POST['payMethod']) : 'CASH';
        if ($postedPayMethod === 'COD') $postedPayMethod = 'CASH';

        // 3. Clone a perfectly formed real order (Order 4614 is confirmed structurally valid)
        $cloneRes = $con->query("SELECT * FROM Orders WHERE OrderID = 4614 LIMIT 1");
        if($cloneRes && $cloneRes->num_rows > 0) {
            $clone = $cloneRes->fetch_assoc();
            $keys = [];
            $vals = [];
            foreach($clone as $k => $v) {
                if($k == 'OrderID') continue;
                $keys[] = "`$k`";
                
                if($k == 'OrderState') $v = 'waiting';
                else if($k == 'ShowOrder') $v = 'YES';
                else if($k == 'IsPrepared') $v = 'NO';
                else if($k == 'ShopAccept') $v = 'NO';
                else if($k == 'PaidForDriver') $v = 'NotPaid';
                else if($k == 'OrderPriceFromShop') $v = ($total - $platformFee);
                else if($k == 'DestnationLat') $v = $shopLat;
                else if($k == 'DestnationLongt') $v = $shopLng;
                else if($k == 'UserLat') $v = $userLat;
                else if($k == 'UserLongt') $v = $userLng;
                else if($k == 'ShopID') $v = $postedShopId;
                else if($k == 'DestinationName') $v = $postedShopName;
                else if($k == 'CreatedAtOrders') $v = date('Y-m-d H:i:s');
                else if($k == 'UserID') $v = $userId;
                else if($k == 'OrderPrice') $v = 0;
                else if($k == 'OrderDetails') $v = $orderDetailsStr;
                else if($k == 'DestnationAddress') $v = 'Selected on map';
                else if($k == 'DestnationPhoto') $v = $shopLogo;
                else if($k == 'UserName') $v = $userName;
                else if($k == 'UserPhone') $v = $userPhone;
                else if($k == 'UserPhoto') $v = $userPhoto;
                else if($k == 'PlatformFee') $v = $platformFee;
                else if($k == 'Method') $v = $postedPayMethod;
                else if($k == 'FourDigit') $v = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                if ($v === NULL) {
                    $vals[] = "NULL";
                } else {
                    $vals[] = "'" . $con->real_escape_string($v) . "'";
                }
            }
            
            $sql = "INSERT INTO Orders (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $vals) . ")";
            $con->query($sql);
            $newOrderId = $con->insert_id;
            
            // 4. Inject EXACT mapping from user cart to stop the Driver App from showing an empty order
            if (count($postedCart) > 0) {
                foreach($postedCart as $cItem) {
                    $itemId = isset($cItem['id']) ? $con->real_escape_string($cItem['id']) : '0';
                    $itemName = $con->real_escape_string($cItem['name']);
                    $itemQty = intval($cItem['qty']);
                    $itemSize = (isset($cItem['size']) && $cItem['size']) ? $con->real_escape_string($cItem['size']) : '';
                    $itemColor = (isset($cItem['color']) && $cItem['color']) ? $con->real_escape_string($cItem['color']) : '';
                    
                    $con->query("INSERT INTO OrderDetailsOrder (OrderID, FoodID, Quantity, Size, Color) 
                                 VALUES ('$newOrderId', '$itemId', '$itemQty', '$itemSize', '$itemColor')");
                }
            } else {
                // Fallback to clone if cart payload was somehow empty. Order 4614 has valid items.
                $oldOrderId = 4614;
                $itemsRes = $con->query("SELECT * FROM OrderDetailsOrder WHERE OrderID = '$oldOrderId'");
                if ($itemsRes && $itemsRes->num_rows > 0) {
                    while ($item = $itemsRes->fetch_assoc()) {
                        $foodId = $item['FoodID'];
                        $qty = $item['Quantity'];
                        $size = $item['Size'];
                        $color = $item['Color'];
                        
                        $con->query("INSERT INTO OrderDetailsOrder (OrderID, FoodID, Quantity, Size, Color) 
                                     VALUES ('$newOrderId', '$foodId', '$qty', '$size', '$color')");
                    }
                }
            }
            
            // Broadcast Push Notification to active drivers!
            $res = $con->query("SELECT FirebaseDriverToken FROM Drivers WHERE Online ='Online'");
            if ($res && $res->num_rows > 0) {
                while($row = $res->fetch_assoc()) {
                    if(!empty($row["FirebaseDriverToken"])){
                        $url = 'https://fcm.googleapis.com/fcm/send';
                        $fields = [
                             'to' => $row["FirebaseDriverToken"],
                             'notification' => [
                                 'title' => 'QOON Express 🚨',
                                 'body' => 'New order waiting for your bid!'
                             ]
                        ];
                        $headers = [
                            'Authorization:key=AAAAEDOF67k:APA91bFMPNwvWHetPtqc1i--ztKxrPdSd7ZbTXvrm0LWFV6KHlkw5I-9yOdt6ZtBq1PXo3uVEDcJnFmbAKpNH7tTS9wiKLjAaeLzB0J0KMI6xvsZ5z0C-4Kn98VzSLp_fJs-ibpmOJY2',
                            'Content-Type:application/json'
                        ];
                       $ch = curl_init();
                       curl_setopt($ch, CURLOPT_URL, $url);
                       curl_setopt($ch, CURLOPT_POST, true);
                       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
                       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                       curl_exec($ch);           
                       curl_close($ch);
                    }
                }
            }
            
            // Add to Firebase Realtime Database so Driver UI refreshes
            $fbUrl = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$newOrderId.'.json/';
            $fbData = ['OrderStatus' => "FOUND"];
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
            
            // Ping Shop UI in Firebase Realtime DB
            $shopUrl = 'https://jibler-37339-default-rtdb.firebaseio.com/Shop/'.$postedShopId.'.json';
            $shopData = [
                'CurrentOrder' => date('Y-m-d H:i:s'),
                'UpdatedAt' => date('Y-m-d H:i:s'),
                'ShopID' => $postedShopId
            ];
            $chShop = curl_init();
            curl_setopt($chShop, CURLOPT_URL, $shopUrl);
            curl_setopt($chShop, CURLOPT_POST, true);
            curl_setopt($chShop, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($chShop, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($chShop, CURLOPT_SSL_VERIFYHOST, 0);  
            curl_setopt($chShop, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($chShop, CURLOPT_POSTFIELDS, json_encode($shopData));
            curl_exec($chShop);
            curl_close($chShop);

            // Send FCM Push Notification to Seller App
            $shopTokenRes = $con->query("SELECT ShopFirebaseToken FROM Shops WHERE ShopID = '$postedShopId' LIMIT 1");
            if ($shopTokenRes && $shopTokenRes->num_rows > 0) {
                $shopTokenRow = $shopTokenRes->fetch_assoc();
                if(!empty($shopTokenRow['ShopFirebaseToken'])) {
                    $shopToken = $shopTokenRow['ShopFirebaseToken'];
                    $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
                    $fcmFields = [
                         'to' => $shopToken,
                         'notification' => [
                             'title' => 'New Order 🍔 🛍️',
                             'body' => 'You just received Order #'.$newOrderId.'! Please prepare it.'
                         ]
                    ];
                    $fcmHeaders = [
                        'Authorization:key=AAAAEDOF67k:APA91bFMPNwvWHetPtqc1i--ztKxrPdSd7ZbTXvrm0LWFV6KHlkw5I-9yOdt6ZtBq1PXo3uVEDcJnFmbAKpNH7tTS9wiKLjAaeLzB0J0KMI6xvsZ5z0C-4Kn98VzSLp_fJs-ibpmOJY2',
                        'Content-Type:application/json'
                    ];
                    $chFcm = curl_init();
                    curl_setopt($chFcm, CURLOPT_URL, $fcmUrl);
                    curl_setopt($chFcm, CURLOPT_POST, true);
                    curl_setopt($chFcm, CURLOPT_HTTPHEADER, $fcmHeaders);
                    curl_setopt($chFcm, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($chFcm, CURLOPT_SSL_VERIFYHOST, 0);  
                    curl_setopt($chFcm, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($chFcm, CURLOPT_POSTFIELDS, json_encode($fcmFields));
                    curl_exec($chFcm);           
                    curl_close($chFcm);
                }
            }
            
            // Bounce strictly to the new tracking state
            header("Location: delivery_offers.php?total=$total&orderId=$newOrderId");
            exit;
        }
    }
}

$totalPrice = isset($_GET['total']) ? htmlspecialchars($_GET['total']) : '0';
$orderId = isset($_GET['orderId']) ? htmlspecialchars($_GET['orderId']) : '8492';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qoon Express - Finding Drivers</title>
    <!-- ⚡ Apply theme BEFORE paint to prevent flash -->
    <script>
        (function() {
            var t = localStorage.getItem('qoon_theme') || 'dark';
            if (t === 'light') document.documentElement.classList.add('light-mode');
        })();
    </script>
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

        /* Responsive Layout: Mobile goes back to column */
        @media (max-width: 768px) {
            body { flex-direction: column-reverse; }
            #sidebar { width: 100% !important; height: 50vh !important; }
            #map-container { height: 50vh !important; width: 100% !important;}
        }

        /* --- Left Sidebar (Offers) --- */
        #sidebar {
            width: 450px;
            height: 100vh;
            background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(30px);
            border-right: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            box-shadow: none;
            position: relative;
        }

        /* Top Header inside Sidebar */
        .top-bar { 
            padding: 24px; 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: rgba(255,255,255,0.02);
        }
        
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .back-btn { 
            width: 44px; height: 44px; border-radius: 50%; 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            color: #fff; display: flex; center; justify-content: center; align-items: center; 
            cursor: pointer; text-decoration: none; transition: 0.2s; font-size: 18px;
        }
        .back-btn:active { transform: scale(0.9); }
        
        .status-pill { 
            background: rgba(155, 45, 241, 0.15); 
            padding: 10px 16px; border-radius: 99px; 
            font-weight: 700; font-size: 14px; color: var(--accent-glow-3); 
            border: 1px solid rgba(155, 45, 241, 0.4); 
            display: flex; align-items: center; gap: 8px;
        }

        /* Offers List Container */
        #offers-list {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        #offers-list::-webkit-scrollbar { width: 6px; }
        #offers-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

        /* Liquid Glass Offer Card */
        .offer-card { 
            width: 100%;
            background: var(--glass-bg); 
            backdrop-filter: blur(24px) saturate(200%); 
            -webkit-backdrop-filter: blur(24px) saturate(200%); 
            border: 1px solid var(--glass-border); 
            border-radius: 24px; 
            padding: 18px; 
            display: flex; flex-direction: column; gap: 16px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2), inset 0 0 20px rgba(255,255,255,0.02); 
            
            /* Slide down dynamic animation */
            transform: translateY(-20px) scale(0.95); 
            opacity: 0; 
            animation: slide-in-down 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; 
        }
        @keyframes slide-in-down { 
            to { transform: translateY(0) scale(1); opacity: 1; } 
        }

        .card-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .driver-info-group {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .driver-avatar { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.1); }
        
        .avatar-wrapper { position: relative; }
        .driver-rating { 
            position: absolute; bottom: -8px; left: 50%; transform: translateX(-50%); 
            background: #ffffff; border: 1px solid #ffffff; border-radius: 12px; 
            font-size: 11px; font-weight: 800; padding: 3px 8px; 
            display: flex; align-items: center; gap: 4px; box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            color: #000000;
        }
        .driver-rating i { color: #000000; font-size: 10px;}
        
        .driver-details { display: flex; flex-direction: column; gap: 4px; }
        .driver-name { font-size: 16px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 6px;}
        .driver-meta { font-size: 12px; color: rgba(255,255,255,0.5); display: flex; gap: 12px; font-weight: 500;}
        .driver-meta span { display: flex; align-items: center; gap: 4px; }
        
        .offer-price-container { 
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.2) 100%); 
            border: 1px solid rgba(46, 204, 113, 0.3); 
            padding: 6px 12px; border-radius: 12px; 
            text-align: center; 
        }
        .offer-price-label { font-size: 9px; font-weight: 800; color: #2ecc71; text-transform: uppercase; margin-bottom: 2px; display: block;}
        .offer-price { font-size: 20px; font-weight: 800; color: #2ecc71; line-height: 1; display:flex; align-items: baseline; gap: 4px; justify-content: center;}
        
        .accept-btn { 
            width: 100%; padding: 14px; border-radius: 14px; 
            background: linear-gradient(135deg, var(--accent-glow-1), var(--accent-glow-3));
            color: #fff; font-weight: 700; font-size: 15px; 
            border: none; cursor: pointer; transition: 0.2s; 
            display: flex; justify-content: center; align-items: center; gap: 8px;
            box-shadow: 0 4px 15px rgba(155, 45, 241, 0.3);
        }
        .accept-btn:active { transform: scale(0.97); }


        /* --- Right Map Area --- */
        #map-container {
            flex: 1;
            height: 100vh;
            position: relative;
            background: #000;
        }
        #map { position: absolute; inset: 0; z-index: 1; }

        /* Map Radar (Custom Leaflet DivIcon) */
        .map-radar-wrapper { position: relative; display: flex; justify-content: center; align-items: center; width: 100px; height: 100px; transform: translate(-50%, -50%);}
        .map-radar-center { width: 22px; height: 22px; background: var(--accent-glow-2); border: 4px solid var(--bg-color); border-radius: 50%; z-index: 10; box-shadow: 0 0 15px var(--accent-glow-2); }
        .map-radar-pulse { position: absolute; width: 22px; height: 22px; border-radius: 50%; background: rgba(44, 181, 232, 0.3); border: 1px solid rgba(44, 181, 232, 0.8); opacity: 0; animation: radar-pulse 2.5s infinite ease-out; }
        .map-radar-pulse:nth-child(2) { animation-delay: 0.8s; }
        .map-radar-pulse:nth-child(3) { animation-delay: 1.6s; }
        
        @keyframes radar-pulse {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(8); opacity: 0; }
        }

        /* Success Overlay */
        #success-overlay { position: fixed; inset: 0; background: rgba(10,10,10,0.9); backdrop-filter: blur(12px); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.4s; }
        #success-overlay.active { opacity: 1; pointer-events: all; }
        .success-circle { width: 90px; height: 90px; border-radius: 50%; background: #2ecc71; display: flex; align-items: center; justify-content: center; color: white; font-size: 40px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(46,204,113,0.4); transform: scale(0.5); transition: transform 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) 0.2s;}
        #success-overlay.active .success-circle { transform: scale(1); }
        .success-title { font-size: 24px; font-weight: 800; margin-bottom: 10px; color:#fff;}
        .success-desc { font-size: 15px; color: rgba(255,255,255,0.7); text-align: center; max-width: 400px; line-height: 1.5; margin-bottom: 30px; }
        .finish-btn { padding: 16px 40px; background: #fff; color: #000; border-radius: 99px; font-weight: 700; text-decoration: none; transition: 0.2s;}

        /* Cancel Modal */
        #cancel-overlay { position: fixed; inset: 0; background: rgba(10,10,10,0.85); backdrop-filter: blur(8px); z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
        #cancel-overlay.active { opacity: 1; pointer-events: all; }
        .cancel-modal { background: #1a1a1a; border: 1px solid rgba(255,59,48,0.2); border-radius: 24px; padding: 32px; width: 90%; max-width: 400px; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.5), inset 0 0 20px rgba(255,255,255,0.02); transform: scale(0.95) translateY(20px); transition: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1); opacity: 0;}
        #cancel-overlay.active .cancel-modal { transform: scale(1) translateY(0); opacity: 1;}
        .cancel-icon { width: 70px; height: 70px; border-radius: 50%; background: rgba(255,59,48,0.1); color: #ff3b30; font-size: 30px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; box-shadow: 0 0 20px rgba(255,59,48,0.2);}
        .cancel-title { font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 12px; }
        .cancel-desc { font-size: 14px; color: rgba(255,255,255,0.6); line-height: 1.5; margin-bottom: 30px; }
        .cancel-actions { display: flex; gap: 12px; }
        .cancel-btn-primary { flex: 1; padding: 14px; border-radius: 12px; background: #ff3b30; color: #fff; border: none; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .cancel-btn-primary:active { transform: scale(0.96); }
        .cancel-btn-secondary { flex: 1; padding: 14px; border-radius: 12px; background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.2s; }
        .cancel-btn-secondary:active { transform: scale(0.96); }

        /* --- LIGHT MODE OVERRIDES --- */
        html.light-mode {
            --bg-color: #f8f9fa;
            --text-main: #0f1115;
            --text-muted: #6b7280;
            --glass-bg: #ffffff;
            --glass-border: rgba(0,0,0,0.08);
        }
        html.light-mode body { background-color: #f8f9fa !important; }
        html.light-mode #sidebar { background: #ffffff; border-right-color: rgba(0,0,0,0.08); }
        html.light-mode .top-bar { background: #f9fafb; border-bottom-color: rgba(0,0,0,0.05); }
        html.light-mode .back-btn { background: #f3f4f6; border-color: rgba(0,0,0,0.08); color: #0f1115; }
        html.light-mode .back-btn:hover { background: #e5e7eb; }
        html.light-mode .offer-card { background: #ffffff; border-color: rgba(0,0,0,0.08); box-shadow: 0 10px 30px rgba(0,0,0,0.04); }
        html.light-mode .driver-name { color: #0f1115; }
        html.light-mode .driver-meta { color: #6b7280; }
        html.light-mode .driver-avatar { border-color: rgba(0,0,0,0.05); }
        html.light-mode h3, html.light-mode p { color: #0f1115 !important; }
        html.light-mode #success-overlay { background: rgba(255,255,255,0.95); }
        html.light-mode .success-title { color: #0f1115; }
        html.light-mode .success-desc { color: #4b5563; }
        html.light-mode .success-desc b { color: #0f1115 !important; }
        html.light-mode .finish-btn { background: #000; color: #fff; }
        html.light-mode #cancel-overlay { background: rgba(255,255,255,0.8); }
        html.light-mode .cancel-modal { background: #ffffff; border-color: rgba(0,0,0,0.08); }
        html.light-mode .cancel-title { color: #0f1115; }
        html.light-mode .cancel-desc { color: #6b7280; }
        html.light-mode .cancel-btn-secondary { background: #f3f4f6; color: #0f1115; border-color: rgba(0,0,0,0.08); }
        html.light-mode .map-radar-center { border-color: #ffffff; }
        html.light-mode #offers-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); }
        html.light-mode .top-bar h3, html.light-mode .top-bar p { color: #0f1115 !important; }
    </style>
</head>
<body>

    <!-- Left Sidebar: Offers & Status -->
    <div id="sidebar">
        <div class="top-bar">
            <div class="header-row">
                <a href="shop.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i></a>
                <div class="status-pill" id="status-title">
                    <i class="fa-solid fa-satellite-dish fa-fade"></i> Finding drivers...
                </div>
            </div>
            <div>
                <h3 style="font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 4px;">Delivery Offers</h3>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <p style="font-size: 13px; color: rgba(255,255,255,0.5);">Drivers are bidding to deliver your order.</p>
                    <button onclick="cancelPendingOrder()" id="cancel-order-btn" style="padding: 6px 12px; background: rgba(255, 59, 48, 0.1); border: 1px solid rgba(255, 59, 48, 0.3); color: #ff3b30; border-radius: 99px; cursor: pointer; font-size: 12px; font-weight: 600; transition: 0.2s;"><i class="fa-solid fa-xmark"></i> Cancel Order</button>
                </div>
            </div>
        </div>

        <div id="offers-list">
            <!-- Dynamic Glass Cards Inject Here -->
        </div>
    </div>

    <!-- Right Map Container -->
    <div id="map-container">
        <div id="map"></div>
    </div>

    <!-- Success Output -->
    <div id="success-overlay">
        <div class="success-circle"><i class="fa-solid fa-check"></i></div>
        <div class="success-title">Order Assigned!</div>
        <div class="success-desc">A driver is heading to the boutique. They will deliver your items shortly.<br><br>Grand Total: <b id="final-total" style="color:white;font-size:18px;"></b> MAD</div>
        <a id="finish-track-btn" href="track_order.php?orderId=<?= $orderId ?>" class="finish-btn"><i class="fa-solid fa-circle-notch fa-spin"></i> Opening Live Chat & Tracker...</a>
    </div>

    <!-- Cancel Confirmation Overlay -->
    <div id="cancel-overlay">
        <div class="cancel-modal">
            <div class="cancel-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="cancel-title">Cancel Order?</div>
            <div class="cancel-desc">Are you sure you want to cancel this order? This action cannot be undone.</div>
            <div class="cancel-actions">
                <button class="cancel-btn-secondary" onclick="closeCancelModal()">No, Keep it</button>
                <button class="cancel-btn-primary" id="confirm-cancel-btn" onclick="confirmCancelOrder()">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.firebase.com/js/client/2.2.1/firebase.js"></script>
    <script>
        const orderId = "<?= $orderId ?>";
        const trackerRef = new Firebase('https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/' + orderId);
        const cartTotal = parseFloat("<?= $totalPrice ?>");
        
        // --- 1. Initialize Map ---
        const initLat = 33.5731;
        const initLng = -7.5898;
        
        const map = L.map('map', { zoomControl: false }).setView([initLat, initLng], 14);
        
        const isLight = document.documentElement.classList.contains('light-mode');
        const tileUrl = isLight 
            ? 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';

        L.tileLayer(tileUrl, {
            maxZoom: 19,
            attribution: '© OpenStreetMap © CartoDB'
        }).addTo(map);

        // --- 2. Custom Radar Marker ---
        const radarIcon = L.divIcon({
            className: 'custom-radar',
            html: `
                <div class="map-radar-wrapper">
                    <div class="map-radar-pulse"></div>
                    <div class="map-radar-pulse"></div>
                    <div class="map-radar-pulse"></div>
                    <div class="map-radar-center"></div>
                </div>
            `,
            iconSize: [0, 0]
        });
        
        const userMarker = L.marker([initLat, initLng], {icon: radarIcon}).addTo(map);

        // Geolocation
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition((pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                map.setView([lat, lng], 15);
                userMarker.setLatLng([lat, lng]);
            });
        }

        // --- 3. Dynamic Bidding API Polling Logic ---
        let displayedOfferIds = [];
        let isFetching = false;
        let fetchInterval;

        function fetchLiveOffers() {
            if (isFetching) return;
            isFetching = true;
            
            fetch(`delivery_offers.php?ajax=offers&orderId=<?= $orderId ?>`)
            .then(res => res.json())
            .then(data => {
                isFetching = false;
                if(data.offers && data.offers.length > 0) {
                    document.getElementById('status-title').innerHTML = `<i class='fa-solid fa-arrow-down-short-wide'></i> Receiving Offers!`;
                    
                    data.offers.forEach((driver, idx) => {
                        if (!displayedOfferIds.includes(driver.id)) {
                            displayedOfferIds.push(driver.id);
                            
                            // Delay UI spawn slightly if multiple to animate the stack down beautifully
                            setTimeout(() => {
                                renderOfferCard(driver);
                                document.getElementById('status-title').innerHTML = `<i class='fa-solid fa-check-double'></i> ${displayedOfferIds.length} Offers`;
                                document.getElementById('status-title').style.background = "rgba(44, 181, 232, 0.1)";
                                document.getElementById('status-title').style.color = "var(--accent-glow-2)";
                                document.getElementById('status-title').style.borderColor = "rgba(44, 181, 232, 0.3)";
                            }, idx * 1500);
                        }
                    });
                }
            })
            .catch(err => { isFetching = false; });
        }

        function renderOfferCard(driver) {
            const list = document.getElementById('offers-list');
            const card = document.createElement('div');
            card.className = 'offer-card';
            card.innerHTML = `
                <div class="card-top-row">
                    <div class="driver-info-group">
                        <div class="avatar-wrapper">
                            <img src="${driver.img}" class="driver-avatar" onerror="this.src='https://ui-avatars.com/api/?name=Driver&background=random'">
                            <div class="driver-rating">
                                <i class="fa-solid fa-star"></i> ${driver.rating}
                            </div>
                        </div>
                        <div class="driver-details">
                            <div class="driver-name">${driver.name} <i class="fa-solid fa-circle-check" style="color:#2ecc71; font-size:14px;"></i></div>
                            <div class="driver-meta">
                                <span><i class="fa-solid fa-location-dot"></i> ${driver.distance}</span>
                                <span><i class="fa-regular fa-clock"></i> ${driver.time}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="offer-price-container">
                        <span class="offer-price-label">Deliv. Fee</span>
                        <div class="offer-price">+${driver.price}<span style="font-size:11px;">MAD</span></div>
                    </div>
                </div>
                <button class="accept-btn" onclick="acceptOffer(${driver.price}, ${driver.id}, '${driver.offerKey}')">Accept Offer</button>
            `;
            list.appendChild(card);
            list.scrollTo({ top: list.scrollHeight, behavior: 'smooth' });
        }

        function acceptOffer(deliveryPrice, driverId, offerKey) {
            const finalTotal = cartTotal + deliveryPrice;
            document.getElementById('final-total').innerText = finalTotal;
            document.getElementById('success-overlay').classList.add('active');
            
            // Stop radar
            document.querySelector('.map-radar-wrapper').style.display = 'none';

            // Send official Accept command via API to wake up the Driver App!
            const fd = new FormData();
            fd.append('OrderID', '<?= $orderId ?>');
            fd.append('DelvryId', driverId);
            fd.append('OrderPrice', deliveryPrice);
            fd.append('OfferKey', offerKey);

            // Optimistic Update to Firebase for instant Driver notification
            try {
                trackerRef.update({
                    current_status: 'ACCEPTED',
                    DelvryId: driverId,
                    OrderPrice: deliveryPrice,
                    accepted_at: Math.floor(Date.now() / 1000)
                });
            } catch(e) { console.error("Firebase update failed:", e); }

            fetch('AcceptOfferUser.php', {
                method: 'POST',
                body: fd
            }).then(() => {
                // Auto-redirect to Live Tracking & Chat after API confirmation and 1s delay
                setTimeout(() => {
                    window.location.href = `track_order.php?orderId=<?= $orderId ?>&tot=${finalTotal}`;
                }, 1000);
            }).catch(console.error);
        }

        // Start Scanning API via Polling
        setTimeout(() => {
            fetchLiveOffers();
            fetchInterval = setInterval(fetchLiveOffers, 4000);
        }, 1500);

        function cancelPendingOrder() {
            document.getElementById('cancel-overlay').classList.add('active');
        }

        function closeCancelModal() {
            document.getElementById('cancel-overlay').classList.remove('active');
        }

        function confirmCancelOrder() {
            const btn = document.getElementById('confirm-cancel-btn');
            const mainBtn = document.getElementById('cancel-order-btn');
            
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Canceling...';
            btn.disabled = true;
            if(mainBtn) mainBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Canceling...';

            const fd = new FormData();
            fd.append('OrderID', '<?= $orderId ?>');

            fetch('CancelOrderUser.php', { method: 'POST', body: fd })
            .then(res => res.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if(data.success) {
                        window.location.href = 'index.php';
                    } else {
                        alert("Failed to cancel: " + (data.message || 'Unknown error'));
                        closeCancelModal();
                        if(mainBtn) mainBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Order';
                        btn.innerHTML = 'Yes, Cancel';
                        btn.disabled = false;
                    }
                } catch(e) {
                    console.error("Raw response:", text);
                    alert("Network error. Response: " + text.substring(0, 50));
                    closeCancelModal();
                    if(mainBtn) mainBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Order';
                    btn.innerHTML = 'Yes, Cancel';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert("Network error.");
                closeCancelModal();
                if(mainBtn) mainBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Order';
                btn.innerHTML = 'Yes, Cancel';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
