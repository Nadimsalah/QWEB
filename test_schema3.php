<?php
require 'conn.php';
echo "DRIVERS:\n";
$res = mysqli_query($con, "SHOW COLUMNS FROM Drivers");
while($row = mysqli_fetch_assoc($res)) { echo $row['Field'] . ", "; }
echo "\n\nUSERS:\n";
$res = mysqli_query($con, "SHOW COLUMNS FROM Users");
while($row = mysqli_fetch_assoc($res)) { echo $row['Field'] . ", "; }
echo "\n\nORDERS:\n";
$res = mysqli_query($con, "SHOW COLUMNS FROM Orders");
while($row = mysqli_fetch_assoc($res)) { echo $row['Field'] . ", "; }
?>
