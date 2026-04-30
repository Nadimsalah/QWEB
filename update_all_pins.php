<?php
require 'conn.php';
// We fetch all orders that have '1234' as the PIN and assign them a random one
$res = $con->query("SELECT OrderID FROM Orders WHERE FourDigit = '1234'");
while($row = $res->fetch_assoc()) {
    $orderId = $row['OrderID'];
    $newPin = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $con->query("UPDATE Orders SET FourDigit = '$newPin' WHERE OrderID = '$orderId'");
    echo "Updated order $orderId to PIN $newPin\n";
}
echo "Done.";
?>
