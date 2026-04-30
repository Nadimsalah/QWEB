<?php
require 'conn.php';
$q = mysqli_query($con, "SELECT DriverID, FName, LName FROM Drivers LIMIT 5");
while($r = mysqli_fetch_assoc($q)) print_r($r);
?>
