<?php
require_once 'init.php';

$action = $_GET['action'] ?? '';

if ($action == 'get_details') {
    $orderID = $con->real_escape_string($_GET['order_id']);
    
    // Fetch Order Info
    $orderSql = "SELECT o.OrderSource, o.UserAddress, o.UserPhone as ManualPhone, o.Method, o.OrderID, o.OrderState,
                 u.PhoneNumber as DbPhone, u.name as BuyerName, u.UserPhoto as BuyerPhoto,
                 d.DriverPhone, d.FName as DriverName, d.PersonalPhoto as DriverPhoto, d.DriverID,
                 s.ShopName, s.ShopLogo as ShopPhoto
                 FROM Orders o
                 LEFT JOIN Users u ON o.UserID = u.UserID
                 LEFT JOIN Drivers d ON o.DelvryId = d.DriverID
                 LEFT JOIN Shops s ON o.DestinationName = s.ShopName
                 WHERE o.OrderID = '$orderID'";
    $orderInfo = $con->query($orderSql)->fetch_assoc();

    // Fetch products
    $sql = "SELECT od.Quantity, od.Size, od.Color, f.FoodName, f.FoodPrice, f.FoodPhoto 
            FROM OrderDetailsOrder od
            JOIN Foods f ON od.FoodID = f.FoodID
            WHERE od.OrderID = '$orderID'";
            
    $res = $con->query($sql);
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'info' => $orderInfo,
        'items' => $items
    ]);
    exit;
}

if ($action == 'update_status') {
    $orderID = $con->real_escape_string($_POST['order_id']);
    $newStatus = $con->real_escape_string($_POST['status']);
    
    // Validate status
    $allowed = ['waiting', 'Accepted', 'Preparing', 'Ready', 'Doing', 'Done', 'Cancelled', 'Rated', 'Arrived', 'Returned', 'No_Answer', 'Postponed', 'Paid', 'Out_For_Delivery', 'Refunded'];
    if (!in_array($newStatus, $allowed)) {
        @ob_clean();
        header('Content-Type: application/json');
        die(json_encode(['status' => 'error', 'message' => 'Invalid status for store']));
    }
    
    if ($newStatus === 'Ready' || $newStatus === 'Returned') {
        $chk = $con->query("SELECT OrderSource FROM Orders WHERE OrderID = '$orderID'")->fetch_assoc();
        if ($chk && $chk['OrderSource'] !== 'WebStore') {
            $pin = isset($_POST['pin']) ? $con->real_escape_string($_POST['pin']) : '';
            
            if ($newStatus === 'Ready') {
                $correctPin = str_pad(abs(crc32($orderID . "QOON_SHOP_PICKUP_TOKEN")) % 10000, 4, '0', STR_PAD_LEFT);
            } else { // Returned
                $correctPin = str_pad(abs(crc32($orderID . "CANCELPIN")) % 10000, 4, '0', STR_PAD_LEFT);
            }
            
            if ($pin !== $correctPin) {
                @ob_clean();
                header('Content-Type: application/json');
                die(json_encode(['status' => 'error', 'message' => 'Invalid Security PIN! Please ask the driver for the correct 4-digit code.']));
            }
        }
    }
    
    $sql = "UPDATE Orders SET OrderState = '$newStatus' WHERE OrderID = '$orderID' AND ShopID = " . (int)$_SESSION['SellerID'];
    
    if ($con->query($sql)) {
        
        // --- REFUND LOGIC for RETURNED ORDERS ---
        if ($newStatus === 'Returned' || $newStatus === 'Refunded' || $newStatus === 'Cancelled') {
            // Check if paid with QOON Pay
            $checkQoon = $con->query("SELECT o.UserID, o.Method, s.ShopName, s.ShopLogo, o.DelvryId, d.FName, d.LName, d.PersonalPhoto FROM Orders o 
                                      LEFT JOIN Shops s ON o.ShopID = s.ShopID
                                      LEFT JOIN Drivers d ON o.DelvryId = d.DriverID
                                      WHERE o.OrderID = '$orderID'");
            if ($checkQoon && $qRow = $checkQoon->fetch_assoc()) {
                $payMethod = strtoupper(trim($qRow['Method']));
                if ($payMethod === 'QOON' || $payMethod === 'QOON PAY') {
                    // Check if a refund has already been issued to prevent double refund
                    $checkRefund = $con->query("SELECT UserTransactionID FROM UserTransaction WHERE OrderID = '$orderID' AND MoneyPlusOrLess = 'plus' LIMIT 1");
                    if ($checkRefund->num_rows == 0) {
                        // Find exactly how much was deducted
                        $transQuery = $con->query("SELECT Money FROM UserTransaction WHERE OrderID = '$orderID' AND MoneyPlusOrLess = 'less' LIMIT 1");
                        if ($transQuery && $tr = $transQuery->fetch_assoc()) {
                            $refundAmt = floatval($tr['Money']);
                            if ($refundAmt > 0) {
                                // 1. Refund the user
                                $con->query("UPDATE Users SET Balance = Balance + $refundAmt WHERE UserID = " . (int)$qRow['UserID']);
                                
                                // 2. Insert new 'plus' transaction
                                $shopName = $qRow['ShopName'] ? 'Refund - ' . $qRow['ShopName'] : 'Refunded Order';
                                $shopPhoto = $qRow['ShopLogo'] ?: '';
                                $driverName = trim($qRow['FName'] . ' ' . $qRow['LName']);
                                $driverPhoto = $qRow['PersonalPhoto'] ?: '';
                                $driverID = $qRow['DelvryId'] ?: 0;
                                
                                $insTrans = $con->prepare("INSERT INTO UserTransaction (UserID, Money, Method, DistnationName, DistnationPhoto, DriverID, OrderID, DriverName, Driverphoto, MoneyPlusOrLess, UserFees) VALUES (?, ?, 'Refund', ?, ?, ?, ?, ?, ?, 'plus', 0)");
                                $insTrans->bind_param("idssiiss", $qRow['UserID'], $refundAmt, $shopName, $shopPhoto, $driverID, $orderID, $driverName, $driverPhoto);
                                $insTrans->execute();
                                $insTrans->close();
                            }
                        }
                    }
                }
            }
        }
        // ----------------------------------------
        // --- Firebase Push (Option 2 Sync) ---
        $fbUrl = "https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/$orderID.json";
        $fbData = ['current_status' => $newStatus, 'updated_at' => time()];
        if ($newStatus === 'Cancelled') {
            $fbData['cancel_reason'] = isset($_POST['cancel_reason']) ? $_POST['cancel_reason'] : 'Unknown';
            $fbData['cancelled_by'] = 'Shop';
        }
        $chFb = curl_init();
        curl_setopt($chFb, CURLOPT_URL, $fbUrl);
        curl_setopt($chFb, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chFb, CURLOPT_CUSTOMREQUEST, "PATCH");
        curl_setopt($chFb, CURLOPT_SSL_VERIFYHOST, 0);  
        curl_setopt($chFb, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chFb, CURLOPT_POSTFIELDS, json_encode($fbData));
        curl_exec($chFb);
        curl_close($chFb);
        
        // --- Firebase Push to Chat ---
        $msgTxt = "Update: Your order is now " . $newStatus;
        if($newStatus == 'waiting') $msgTxt = "We have received your order! Please wait while we confirm it. 🕒";
        if($newStatus == 'Accepted') $msgTxt = "Great news! We have confirmed your order and will start preparing it soon. 👨‍🍳";
        if($newStatus == 'Preparing') $msgTxt = "Your order is now being freshly prepared! 🥘";
        if($newStatus == 'Ready') $msgTxt = "Your order is ready and waiting for the driver to pick it up! 🛍️";
        if($newStatus == 'Doing') $msgTxt = "Your order has been handed to the driver and is on its way! 🛵";
        if($newStatus == 'Done') $msgTxt = "Your order has been delivered successfully. Enjoy! 🎉";
        if($newStatus == 'Returned') $msgTxt = "Your order has been returned to the store. 🔄";
        if($newStatus == 'No_Answer') $msgTxt = "Unfortunately, we received no answer from the customer. 📞";
        if($newStatus == 'Postponed') $msgTxt = "Your delivery has been postponed. ⏳";
        if($newStatus == 'Paid') $msgTxt = "Payment confirmed. Thank you! 💳";
        if($newStatus == 'Out_For_Delivery') $msgTxt = "Your order is out for delivery! 🛵";
        if($newStatus == 'Refunded') $msgTxt = "Your order has been refunded. 💸";
        if($newStatus == 'Cancelled') {
            $reason = isset($_POST['cancel_reason']) ? $_POST['cancel_reason'] : 'Unknown';
            $msgTxt = "Unfortunately, we had to cancel your order. Reason: " . $reason . " 😔";
        }
        $msgData = [
            'CreatedTime' => time() * 1000,
            'MessageType' => 'words',
            'height' => time() * 1000,
            'message' => $msgTxt,
            'name' => $_SESSION['SellerName'] ?? 'Store',
            'sender' => 'vendor'
        ];
        
        $chMsg = curl_init("https://jibler-37339-default-rtdb.firebaseio.com/Messages/$orderID.json");
        curl_setopt($chMsg, CURLOPT_POST, true);
        curl_setopt($chMsg, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chMsg, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chMsg, CURLOPT_POSTFIELDS, json_encode($msgData));
        curl_exec($chMsg);
        curl_close($chMsg);
        // -------------------------------------
        @ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
    } else {
        @ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $con->error]);
    }
    exit;
}
?>
