<?php
require 'conn.php';
// Check any orders with ShopRecive = 'NO'
$q = mysqli_query($con, "SELECT OrderID, DelvryId, OrderPriceFromShop, OrderPriceForOur, ShopRecive, PaidForDriver FROM Orders WHERE ShopRecive = 'NO' LIMIT 5");
while($row = mysqli_fetch_assoc($q)) { print_r($row); }
?>
