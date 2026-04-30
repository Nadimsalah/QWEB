<?php
require 'conn.php';
$pin = rand(1000, 9999);
$con->query("UPDATE Orders SET FourDigit='$pin' WHERE OrderID=2222729");
echo "Updated to " . $pin;
?>
