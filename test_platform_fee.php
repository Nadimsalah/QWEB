<?php
require 'conn.php';
$q = mysqli_query($con, 'SELECT OrderPrice, OrderPriceFromShop, OrderPriceForOur, PlatformFee FROM Orders WHERE OrderID=2222736');
while($r = mysqli_fetch_assoc($q)) print_r($r);
?>
