<?php
require 'conn.php';
$res = $con->query("SELECT OrderID, CreatedAtOrders FROM Orders WHERE OrderID=2222681");
echo json_encode($res->fetch_assoc());
?>
