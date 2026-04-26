<?php
require 'conn.php';
mysqli_query($con, "ALTER TABLE Orders ADD COLUMN UserPhoto VARCHAR(255) DEFAULT ''");
echo "Done";
?>
