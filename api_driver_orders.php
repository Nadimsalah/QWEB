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
               o.DestnationPhoto, o.OrderDelvTime, o.OrderType,
               u.name AS UserName, u.UserPhoto, u.PhoneNumber AS UserPhone,
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
    // Fix photo fallback
    if (empty($row['UserPhoto']) || $row['UserPhoto'] === '0') {
        $row['UserPhoto'] = 'https://ui-avatars.com/api/?name=' . urlencode($row['UserName'] ?? 'User') . '&background=2cb5e8&color=fff';
    }
    if (empty($row['DestnationPhoto']) || $row['DestnationPhoto'] === '0') {
        $row['DestnationPhoto'] = $row['UserPhoto'];
    }
    $row['distance'] = round(floatval($row['distance']), 1);
    $orders[] = $row;
}

echo json_encode(['success' => true, 'data' => $orders, 'count' => count($orders)]);
