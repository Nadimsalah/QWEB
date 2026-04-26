<?php
require 'conn.php';
$res = $con->query("SELECT OrderID, OrderPriceFromShop, OrderPrice, PlatformFee, OrderDetails FROM Orders ORDER BY OrderID DESC LIMIT 1");
if($row = $res->fetch_assoc()){
    echo json_encode($row);
}
?>
