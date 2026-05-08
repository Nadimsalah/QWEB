<?php
require 'conn.php';
$tables = ['Transactions', 'Wallet', 'TransferOfShop', 'ShopBalance', 'Withdrawals'];
foreach($tables as $t) {
    $res = $con->query("SHOW TABLES LIKE '$t'");
    if($res->num_rows > 0) {
        echo "FOUND: $t\n";
        $res2 = $con->query("DESCRIBE $t");
        while($r = $res2->fetch_assoc()) {
            echo "  - " . $r['Field'] . " (" . $r['Type'] . ")\n";
        }
    }
}
// Also search for general transaction-like tables
$res3 = $con->query("SHOW TABLES");
while($row = $res3->fetch_row()) {
    if(stripos($row[0], 'trans') !== false || stripos($row[0], 'wallet') !== false || stripos($row[0], 'money') !== false) {
        echo "POTENTIAL: " . $row[0] . "\n";
    }
}
?>
