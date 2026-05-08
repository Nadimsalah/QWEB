<?php
require '../api_conn.php';
$q = $con->query("SELECT OrderID, DestinationName, OrderPrice, CreatedAtOrders, UserName FROM Orders WHERE UserName LIKE '%Ali Ahmed%' OR OrderID = 1 LIMIT 5");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
