<?php
require 'conn.php';
$q = mysqli_query($con, 'SELECT * FROM Orders WHERE OrderID=2222736');
print_r(mysqli_fetch_assoc($q));
?>
