<?php
require '../api_conn.php';
$q = $con->query("SELECT ShopID, COUNT(*) as c FROM Orders WHERE ShopID > 0 GROUP BY ShopID ORDER BY c DESC LIMIT 5");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
