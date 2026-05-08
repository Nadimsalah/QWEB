<?php
header('Content-Type: application/json');
require 'conn.php';

$driverLat  = floatval($_GET['lat']  ?? 0);
$driverLng  = floatval($_GET['lng']  ?? 0);
$driverId   = intval($_GET['driver_id'] ?? 0);

if (!$driverLat || !$driverLng) {
    echo json_encode(['success' => false, 'data' => [], 'message' => 'Location required']);
    exit;
}

$sql = "SELECT o.OrderID, o.OrderDetails, o.OrderState, o.CreatedAtOrders,
               o.DestnationLat, o.DestnationLongt, o.DestnationAddress, o.DestinationName,
               o.DestnationPhoto, o.OrderDelvTime, o.OrderType, o.PlatformFee,
               o.UserID, o.UserEmail,
               IFNULL(NULLIF(u.name, ''), o.UserName) AS UserName, 
               IFNULL(NULLIF(u.name, ''), o.UserName) AS name, 
               u.UserPhoto, 
               IFNULL(NULLIF(u.PhoneNumber, ''), o.UserPhone) AS UserPhone,
               s.ShopName, s.ShopLogo,
               IFNULL((6372.797 * acos(
                   cos(radians($driverLat)) * cos(radians(IFNULL(NULLIF(o.DestnationLat, ''), 0))) *
                   cos(radians(IFNULL(NULLIF(o.DestnationLongt, ''), 0)) - radians($driverLng)) +
                   sin(radians($driverLat)) * sin(radians(IFNULL(NULLIF(o.DestnationLat, ''), 0)))
               )), 999) AS distance
        FROM Orders o
        LEFT JOIN Users u ON u.UserID = o.UserID
        LEFT JOIN Shops s ON s.ShopName = o.DestinationName
        WHERE o.OrderState = 'waiting'
          AND o.ShowOrder = 'YES'
          AND (o.CreatedAtOrders > NOW() - INTERVAL 2880 MINUTE OR o.OrderType = 'SLOW')
        GROUP BY o.OrderID
        HAVING distance <= 9999
        ORDER BY o.OrderID DESC
        LIMIT 30";

$res    = mysqli_query($con, $sql);
$orders = [];
while ($row = mysqli_fetch_assoc($res)) {
    // If the JOIN didn't return a photo (UserID=0 for Firebase users), look up by phone/email
    if (empty($row['UserPhoto']) || $row['UserPhoto'] === '0' || $row['UserPhoto'] === 'null') {
        $uid = intval($row['UserID'] ?? 0);
        if ($uid > 0) {
            $photoRes = mysqli_query($con, "SELECT UserPhoto FROM Users WHERE UserID = $uid LIMIT 1");
            if ($photoRow = mysqli_fetch_assoc($photoRes)) {
                if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                    $row['UserPhoto'] = $photoRow['UserPhoto'];
                }
            }
        }
        // Fallback: look up by phone number stored in the order (skip invalid placeholder '00000000')
        if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserPhone']) && $row['UserPhone'] !== '00000000') {
            $safePhone = mysqli_real_escape_string($con, $row['UserPhone']);
            $cleanPhone = preg_replace('/[^0-9]/', '', $row['UserPhone']);
            if (strlen($cleanPhone) >= 8) {
                $last8 = substr($cleanPhone, -8);
                $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE PhoneNumber LIKE '%$last8' LIMIT 1");
            } else {
                $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE PhoneNumber='$safePhone' LIMIT 1");
            }
            if ($photoRow = mysqli_fetch_assoc($photoRes)) {
                if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                    $row['UserPhoto'] = $photoRow['UserPhoto'];
                }
                if (!empty($photoRow['name'])) { $row['UserName'] = $photoRow['name']; }
            }
        }
        // Fallback: look up by email stored in the order
        if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserEmail']) && $row['UserEmail'] !== 'user@qoon.app') {
            $safeEmail = mysqli_real_escape_string($con, $row['UserEmail']);
            $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE Email='$safeEmail' LIMIT 1");
            if ($photoRow = mysqli_fetch_assoc($photoRes)) {
                if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                    $row['UserPhoto'] = $photoRow['UserPhoto'];
                }
                if (!empty($photoRow['name'])) { $row['UserName'] = $photoRow['name']; }
            }
        }
        // Fallback: look up by name stored in the order
        if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserName']) && $row['UserName'] !== 'QOON User' && $row['UserName'] !== 'Customer') {
            $safeName = mysqli_real_escape_string($con, $row['UserName']);
            $photoRes = mysqli_query($con, "SELECT UserPhoto, UserPhoto FROM Users WHERE name='$safeName' AND UserPhoto != '' AND UserPhoto != '0' LIMIT 1");
            if ($photoRow = mysqli_fetch_assoc($photoRes)) {
                if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                    $row['UserPhoto'] = $photoRow['UserPhoto'];
                }
            }
        }
    }

    // Fix photo fallback for User
    if (empty($row['UserPhoto']) || $row['UserPhoto'] === '0' || $row['UserPhoto'] === 'null') {
        $row['UserPhoto'] = 'https://ui-avatars.com/api/?name=' . urlencode($row['UserName'] ?? 'User') . '&background=2cb5e8&color=fff';
    } else if (!filter_var($row['UserPhoto'], FILTER_VALIDATE_URL)) {
        $row['UserPhoto'] = 'https://qoon.app/photo/' . $row['UserPhoto'];
    }
    
    // Handle Shop Logo / Destination Photo
    if (empty($row['ShopLogo']) || $row['ShopLogo'] === '0') {
        if (!empty($row['DestnationPhoto']) && $row['DestnationPhoto'] !== '0') {
            $row['ShopLogo'] = $row['DestnationPhoto'];
        }
    }

    if (!empty($row['ShopLogo']) && $row['ShopLogo'] !== '0' && !filter_var($row['ShopLogo'], FILTER_VALIDATE_URL)) {
        $row['ShopLogo'] = 'https://qoon.app/photo/' . $row['ShopLogo'];
    }
    
    if (empty($row['DestnationPhoto']) || $row['DestnationPhoto'] === '0') {
        $row['DestnationPhoto'] = !empty($row['ShopLogo']) ? $row['ShopLogo'] : $row['UserPhoto'];
    } else if (!filter_var($row['DestnationPhoto'], FILTER_VALIDATE_URL)) {
        $row['DestnationPhoto'] = 'https://qoon.app/photo/' . $row['DestnationPhoto'];
    }

    $row['distance'] = round(floatval($row['distance']), 1);
    $orders[] = $row;
}

echo json_encode(['success' => true, 'data' => $orders, 'count' => count($orders)]);
