<?php
require 'conn.php';
$q = mysqli_query($con, "SELECT OrderID, ShopRecive, OrderPriceFromShop, OrderPriceForOur FROM Orders WHERE DelvryId = '1' AND OrderState IN ('Done', 'Rated') LIMIT 5");
while($row = mysqli_fetch_assoc($q)) { print_r($row); }
?>
