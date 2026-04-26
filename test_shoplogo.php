<?php
require 'conn.php';
$res = mysqli_query($con, "SELECT ShopLogo FROM Shops LIMIT 3");
echo "Shops Logo: \n";
while($r = mysqli_fetch_assoc($res)) {
    print_r($r);
}
?>
