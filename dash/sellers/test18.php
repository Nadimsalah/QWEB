<?php
require '../api_conn.php';
$q = $con->query("SELECT ShopLogo, ShopCover FROM Shops WHERE ShopLogo != '' LIMIT 5");
while($r = $q->fetch_assoc()) echo $r['ShopLogo'] . ' | ' . $r['ShopCover'] . "\n";
?>
