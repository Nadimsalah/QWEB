<?php
require 'conn.php';
$cloneRes = $con->query("SELECT * FROM Orders WHERE OrderID = 4614 LIMIT 1");
$clone = $cloneRes->fetch_assoc();
echo json_encode(array_keys($clone));
?>
