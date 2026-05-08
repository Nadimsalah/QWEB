<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT * FROM Orders WHERE OrderID=2222716');
print_r(mysqli_fetch_assoc($res));
?>
