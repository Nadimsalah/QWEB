<?php
require "API REFRENCE/conn.php";
$res = mysqli_query($con, "SHOW COLUMNS FROM Drivers");
while($row = mysqli_fetch_assoc($res)) {
  echo $row['Field'] . "\n";
}
?>
