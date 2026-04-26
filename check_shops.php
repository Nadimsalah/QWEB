<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }
$res = $con->query("SELECT ShopID, ShopName FROM Shops WHERE ShopID = 9");
if(!$res) { die("Query failed: " . $con->error); }
while($row = $res->fetch_assoc()){
    echo "ID: {$row['ShopID']} | ShopName: '{$row['ShopName']}'\n";
}
