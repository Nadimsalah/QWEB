<?php
require 'conn.php';
$r1 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN CancelPhoto TEXT");
echo "CancelPhoto: " . ($r1 ? 'Success' : mysqli_error($con)) . "\n";
$r2 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN CancelLat VARCHAR(50)");
echo "CancelLat: " . ($r2 ? 'Success' : mysqli_error($con)) . "\n";
$r3 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN CancelLng VARCHAR(50)");
echo "CancelLng: " . ($r3 ? 'Success' : mysqli_error($con)) . "\n";
?>
