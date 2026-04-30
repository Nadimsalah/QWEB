<?php
require 'conn.php';
$r = mysqli_query($con, 'SHOW TABLES');
while($row = mysqli_fetch_array($r)) echo $row[0].", ";
?>
