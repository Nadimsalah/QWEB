<?php
require "conn.php";
$DriverID = '140';
$OrderId = '2222799';
$Price = '22';
$AppType = "QOON";

$res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID ='$OrderId' AND OrderState='waiting'");
if(mysqli_num_rows($res) > 0) {
    echo "Order found and is waiting.\n";
} else {
    echo "Order NOT found or NOT waiting.\n";
}

$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID =$DriverID AND DriverState='Active'");
if(mysqli_num_rows($res) > 0) {
    echo "Driver found and active.\n";
} else {
    echo "Driver NOT found or NOT active.\n";
}
?>
