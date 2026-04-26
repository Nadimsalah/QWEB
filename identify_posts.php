<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }

// Delete posts where ShopID is not in Shops table (since that causes them to appear as 'QOON Shop' and they are test data)
$query1 = "SELECT PostID, PostText, ShopID FROM Posts WHERE ShopID NOT IN (SELECT ShopID FROM Shops)";
$res = $con->query($query1);
while($row = $res->fetch_assoc()) {
    echo "Orphan Post: {$row['PostID']} | Text: {$row['PostText']}\n";
}

$query2 = "SELECT PostID, PostText FROM Posts WHERE PostText LIKE '%test%' OR PostText LIKE '%تجربه%' OR PostText LIKE 'hello%' OR PostText LIKE 'مرحبا%'";
$res2 = $con->query($query2);
while($row = $res2->fetch_assoc()) {
    echo "Test Text Post: {$row['PostID']} | Text: {$row['PostText']}\n";
}

