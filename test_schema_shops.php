<?php
require 'conn.php';
$res = mysqli_query($con, "SHOW COLUMNS FROM Shops");
echo "SHOPS:\n";
while($row = mysqli_fetch_assoc($res)) { echo $row['Field'] . ", "; }
?>
