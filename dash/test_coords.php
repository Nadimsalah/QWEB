<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT OrderID, UserLat, UserLongt FROM Orders WHERE OrderID=2222719');
print_r(mysqli_fetch_assoc($res));
?>
