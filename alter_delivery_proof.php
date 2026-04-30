<?php
require 'conn.php';
$r1 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN DeliveryPhoto TEXT");
echo "DeliveryPhoto: " . ($r1 ? 'Success' : mysqli_error($con)) . "\n";
$r2 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN DeliveryLat VARCHAR(50)");
echo "DeliveryLat: " . ($r2 ? 'Success' : mysqli_error($con)) . "\n";
$r3 = mysqli_query($con, "ALTER TABLE Orders ADD COLUMN DeliveryLng VARCHAR(50)");
echo "DeliveryLng: " . ($r3 ? 'Success' : mysqli_error($con)) . "\n";
?>
