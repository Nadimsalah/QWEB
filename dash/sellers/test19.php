<?php
require '../api_conn.php';
$q = $con->query("SELECT ShopID, ShopName, ShopLogo, ShopCover FROM Shops ORDER BY ShopID DESC LIMIT 3");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
