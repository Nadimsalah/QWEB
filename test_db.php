<?php
require "conn.php";
$res = mysqli_query($con, "SELECT OrderID, UserID, DestinationName, OrderState FROM Orders ORDER BY OrderID DESC LIMIT 10");
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>
