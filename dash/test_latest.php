<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT OrderID, OrderState, DelvryId, CancelLat, CancelLng, CancelPhoto FROM Orders ORDER BY OrderID DESC LIMIT 1');
print_r(mysqli_fetch_assoc($res));
?>
