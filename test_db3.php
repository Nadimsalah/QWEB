<?php
require "conn.php";
$res = mysqli_query($con, "SELECT * FROM OrdersJiblerpercentageDriver");
$data = [];
while($row = mysqli_fetch_assoc($res)) {
    $data[] = $row;
}
echo json_encode($data);
?>
