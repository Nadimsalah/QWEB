<?php
require 'conn.php';
$r = mysqli_query($con, "SELECT * FROM Drivers WHERE FName LIKE '%Abdessamad%' OR LName LIKE '%Abdessamad%' LIMIT 1");
print_r(mysqli_fetch_assoc($r));
