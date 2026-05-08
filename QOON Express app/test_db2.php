<?php
require '../QOON WEB/QWEB-main/conn.php';
$res = mysqli_query($con, "SELECT * FROM DriversOffer WHERE OrderId = '2222748'");
$offers = [];
while($row = mysqli_fetch_assoc($res)) { $offers[] = $row; }
echo json_encode($offers);
?>
