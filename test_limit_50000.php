<?php
require 'conn.php';
mysqli_query($con, "UPDATE MoneyStop SET MoneyStopNumber = '50000' LIMIT 1");
echo "Limit set to 50000.";
?>
