<?php
require 'conn.php';
$sql = "UPDATE Orders SET ShopRecive = 'NO', OrderPriceFromShop = '120', OrderPriceForOur = '15' WHERE DelvryId = '1' AND OrderState IN ('Done', 'Rated') LIMIT 2";
if (!mysqli_query($con, $sql)) {
    echo "Error: " . mysqli_error($con);
} else {
    echo mysqli_affected_rows($con) . " rows updated.";
}
?>
