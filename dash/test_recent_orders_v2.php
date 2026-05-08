<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT OrderID, OrderState, DelvryId, CancelLat, CancelLng, CancelPhoto FROM Orders ORDER BY OrderID DESC LIMIT 5');
while($row = mysqli_fetch_assoc($res)) {
    echo "OrderID: " . $row['OrderID'] . "\n";
    echo "State: " . $row['OrderState'] . "\n";
    echo "DriverID: " . $row['DelvryId'] . "\n";
    echo "CancelLat: " . $row['CancelLat'] . "\n";
    echo "CancelLng: " . $row['CancelLng'] . "\n";
    echo "CancelPhoto: " . $row['CancelPhoto'] . "\n";
    echo "---------------------------\n";
}
?>
