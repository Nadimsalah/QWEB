<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT OrderID, OrderState, CancelLat, CancelLng, CancelPhoto FROM Orders ORDER BY OrderID DESC LIMIT 3');
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
