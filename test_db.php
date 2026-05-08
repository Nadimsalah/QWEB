<?php
require "conn.php";
$res = mysqli_query($con, "SELECT OrderID, ShopID, OrderType, OrderState, DelvryId FROM Orders ORDER BY OrderID DESC LIMIT 1");
$row = mysqli_fetch_assoc($res);
print_r($row);
?>
