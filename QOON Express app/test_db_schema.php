<?php
require '../QOON WEB/QWEB-main/conn.php';
$res = mysqli_query($con, "DESCRIBE DriversOffer");
$schema = [];
while($row = mysqli_fetch_assoc($res)) { $schema[] = $row; }
echo json_encode($schema);
?>
