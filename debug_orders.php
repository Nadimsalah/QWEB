<?php
require "conn.php";
$res = mysqli_query($con, "SELECT OrderID, ShopID, OrderState, DelvryId, OfferKey FROM Orders ORDER BY OrderID DESC LIMIT 5");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['OrderID'] . " | ShopID: " . $row['ShopID'] . " | State: " . $row['OrderState'] . " | Driver: " . $row['DelvryId'] . " | OfferKey: " . $row['OfferKey'] . "\n";
}
