<?php
require "conn.php";
$r = $con->query("SELECT * FROM Shops WHERE ShopID=721953");
if($r) { print_r($r->fetch_assoc()); } else { echo "query error"; }
$r2 = $con->query("SELECT * FROM Shops WHERE ShopID=5610");
if($r2) { print_r($r2->fetch_assoc()); }
