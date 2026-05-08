<?php
require "conn.php";
$res=$con->query("DESCRIBE RequestPay");
while($row=$res->fetch_assoc()) print_r($row);
?>
