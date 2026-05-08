<?php
require '../QOON WEB/QWEB-main/conn.php';
$res = mysqli_query($con, "SELECT OrderID, OrderState, ShowOrder FROM Orders WHERE OrderID = '2222748'");
echo json_encode(mysqli_fetch_assoc($res));
?>
