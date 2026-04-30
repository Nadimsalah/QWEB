<?php
require 'conn.php';
$r = mysqli_query($con, 'SELECT * FROM MoneyStop LIMIT 1');
while($row = mysqli_fetch_assoc($r)) { print_r($row); }
$r = mysqli_query($con, 'SELECT * FROM DriverReachLimit LIMIT 1');
while($row = mysqli_fetch_assoc($r)) { print_r($row); }
?>
