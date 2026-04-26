<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'conn.php';

$res = mysqli_query($con, "SELECT DestnationPhoto FROM Orders WHERE OrderID=4614");
if ($res) {
    echo "Order 4614 DestnationPhoto: ";
    print_r(mysqli_fetch_assoc($res));
}

echo "\n\n";

$res = mysqli_query($con, "SELECT Logo FROM Shops LIMIT 3");
if ($res) {
    echo "Shops Logo: \n";
    while($r = mysqli_fetch_assoc($res)) {
        print_r($r);
    }
}

echo "\n\n";

$res = mysqli_query($con, "SELECT UserPhoto FROM Users LIMIT 3");
if ($res) {
    echo "Users UserPhoto: \n";
    while($r = mysqli_fetch_assoc($res)) {
        print_r($r);
    }
}
?>
