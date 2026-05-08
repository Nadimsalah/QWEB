<?php
require 'conn.php';
$res = $con->query("DESCRIBE BoostsByShop");
if($res) {
    while($r = $res->fetch_assoc()) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
} else {
    echo "Table 'BoostsByShop' not found. Checking for similar tables...\n";
    $res2 = $con->query("SHOW TABLES LIKE '%Boost%'");
    while($r2 = $res2->fetch_row()) echo $r2[0] . "\n";
}
?>
