<?php
require "conn.php";
$res = mysqli_query($con, "DESCRIBE Orders");
$data = [];
while($row = mysqli_fetch_assoc($res)) {
    $data[] = $row['Field'] . ' - ' . $row['Type'];
}
echo implode("\n", $data);
?>
