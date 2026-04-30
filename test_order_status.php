<?php
require 'conn.php';
$newPin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
$con->query("UPDATE Orders SET FourDigit = '$newPin' WHERE OrderID = '2222730'");
echo "Order 2222730 updated to $newPin";
?>
