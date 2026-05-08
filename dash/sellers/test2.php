<?php
require '../api_conn.php';
$dSql = "INSERT INTO OrderDetailsOrder (OrderID, FoodID, Quantity) VALUES ('123', '1', '1')";
if ($con->query($dSql)) { echo "OK"; } else { echo $con->error; }
?>
