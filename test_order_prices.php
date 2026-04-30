<?php
require 'conn.php';
$q = mysqli_query($con, "SELECT OrderPrice, OrderPriceFromShop, OrderPriceForOur FROM Orders WHERE OrderID='2222736'");
while($r = mysqli_fetch_assoc($q)) print_r($r);

// Also get the commission from MoneyStop just in case
$commRes = $con->query("SELECT DriverCommesion FROM MoneyStop LIMIT 1");
if($commRes && $commRes->num_rows > 0) {
    print_r($commRes->fetch_assoc());
}
?>
