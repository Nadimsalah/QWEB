<?php
require 'conn.php';
$res = $con->query("SELECT DriverPhone, DriverPassword FROM Drivers LIMIT 1");
print_r($res->fetch_assoc());
?>
