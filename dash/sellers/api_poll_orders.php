<?php
require_once 'init.php';

header('Content-Type: application/json');

$sellerID = (int)$_SESSION['SellerID'];
$lastID = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

if ($lastID <= 0) {
    echo json_encode(['status' => 'success', 'new_orders' => []]);
    exit;
}

// Check for ANY new orders strictly greater than last_id
// We only alert for orders that are newly Placed ('waiting')
$sql = "SELECT OrderID FROM Orders WHERE ShopID = $sellerID AND OrderID > $lastID AND OrderState = 'waiting' ORDER BY OrderID ASC";
$res = $con->query($sql);

$newOrders = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $newOrders[] = $row['OrderID'];
    }
}

echo json_encode(['status' => 'success', 'new_orders' => $newOrders]);
