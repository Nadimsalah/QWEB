<?php
require 'conn.php';
$target = ['ShopTransaction', 'SlasesRevTransaction', 'Money', 'ShopLastTransaction'];
foreach($target as $t) {
    echo "TABLE: $t\n";
    $res = $con->query("DESCRIBE $t");
    if($res) {
        while($r = $res->fetch_assoc()) {
            echo "  - " . $r['Field'] . " (" . $r['Type'] . ")\n";
        }
    } else {
        echo "  - ERROR DESCRIBING\n";
    }
}
?>
