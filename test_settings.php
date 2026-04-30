<?php
require 'conn.php';
$r = mysqli_query($con, 'SELECT * FROM Settings LIMIT 1');
while($row = mysqli_fetch_assoc($r)) { print_r($row); }
?>
