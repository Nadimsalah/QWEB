<?php
require 'conn.php';
$res = $con->query('ALTER TABLE Orders ADD COLUMN PlatformFee DECIMAL(10,2) DEFAULT 0');
if($res){ echo "Success"; } else { echo "Error: " . $con->error; }
?>
