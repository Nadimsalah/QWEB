<?php
require "conn.php";
$res=$con->query("SELECT DISTINCT RequestPayStatues FROM RequestPay");
while($row=$res->fetch_row()) echo $row[0] . "\n";
?>
