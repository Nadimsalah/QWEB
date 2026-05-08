<?php
require 'conn.php';
$res = $con->query("SELECT * FROM OrdersJiblerpercentage");
while($r = $res->fetch_assoc()) print_r($r);
?>
