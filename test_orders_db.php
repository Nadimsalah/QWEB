<?php
require 'conn.php';
$r = mysqli_query($con, "SELECT OrderID, OrderState, ShowOrder, CreatedAtOrders, DestnationLat, DestnationLongt, OrderType FROM Orders ORDER BY OrderID DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($r)) {
    print_r($row);
}
