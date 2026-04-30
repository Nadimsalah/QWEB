<?php
require 'conn.php';
$dId = 1;
// 1. Check sum with ShopRecive
$q1 = mysqli_query($con, "SELECT SUM(OrderPriceFromShop) as a, SUM(OrderPriceForOur) as b FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated') AND ShopRecive = 'NO'");
print_r(mysqli_fetch_assoc($q1));

// 2. Check sum without ShopRecive
$q2 = mysqli_query($con, "SELECT SUM(OrderPriceFromShop) as a, SUM(OrderPriceForOur) as b FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated')");
print_r(mysqli_fetch_assoc($q2));

// 3. Count rows
$q3 = mysqli_query($con, "SELECT count(*) FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated')");
print_r(mysqli_fetch_assoc($q3));

// 4. Sample ShopRecive values
$q4 = mysqli_query($con, "SELECT ShopRecive, OrderPriceFromShop, OrderPriceForOur FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated') LIMIT 5");
while($row = mysqli_fetch_assoc($q4)) { print_r($row); }
?>
