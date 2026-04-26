<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }
$res = $con->query("SELECT Posts.PostID, Posts.PostText, Posts.CreatedAtPosts, Shops.ShopName, Posts.ShopID FROM Posts JOIN Shops ON Posts.ShopID = Shops.ShopID WHERE Shops.ShopName = 'QOON Shop' ORDER BY Posts.PostID DESC LIMIT 100");
if(!$res) { die("Query failed: " . $con->error); }
while($row = $res->fetch_assoc()){
    echo "ID: {$row['PostID']} | ShopID: {$row['ShopID']} | Date: {$row['CreatedAtPosts']} | Text: " . str_replace("\n", " ", $row['PostText']) . "\n";
}
