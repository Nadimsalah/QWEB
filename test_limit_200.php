<?php
require 'conn.php';
mysqli_query($con, "UPDATE MoneyStop SET MoneyStopNumber = '200' LIMIT 1");
echo "Limit set to 200.";
?>
