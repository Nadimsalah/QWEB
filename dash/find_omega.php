<?php
require 'conn.php';
$res = $con->query("SELECT ShopID, ShopName FROM Shops WHERE ShopName LIKE '%omega%'");
if($res) while($r = $res->fetch_assoc()) echo $r['ShopID'] . " | " . $r['ShopName'] . "\n";
?>
