<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }
$res = $con->query("SELECT PostID, ShopID, PostText, CreatedAtPosts FROM Posts LIMIT 20");
if(!$res) { die("Query failed: " . $con->error); }
while($row = $res->fetch_assoc()){
    echo "ID: {$row['PostID']} | ShopID: {$row['ShopID']} | Date: {$row['CreatedAtPosts']} | Text: " . substr(str_replace(["\n","\r"], " ", $row['PostText']), 0, 50) . "\n";
}
