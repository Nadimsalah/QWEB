<?php
require '../api_conn.php';
$q = $con->query("SELECT Orders.OrderID, Orders.DestinationName, Orders.UserName, Users.name FROM Orders LEFT JOIN Users ON Orders.UserID = Users.UserID ORDER BY OrderID DESC LIMIT 10");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
