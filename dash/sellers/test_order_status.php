<?php
require_once 'init.php';
$sellerID = (int)$_SESSION['SellerID'];
$res = $con->query("SELECT OrderID, OrderState, ShopID, DelvryId FROM Orders ORDER BY OrderID DESC LIMIT 10");
while($row = $res->fetch_assoc()) {
    echo "OrderID: {$row['OrderID']} | State: {$row['OrderState']} | Shop: {$row['ShopID']} | Driver: {$row['DelvryId']}\n";
}
