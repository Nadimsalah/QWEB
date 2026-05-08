<?php
require_once 'init.php';
$sellerID = (int)$_SESSION['SellerID'];
$res = $con->query("SELECT OrderID, ShopID, OrderState, DelvryId, OrderSource, CreatedAtOrders FROM Orders ORDER BY OrderID DESC LIMIT 10");
$data = [];
while($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
