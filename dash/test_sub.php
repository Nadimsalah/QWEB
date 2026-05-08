<?php
require 'api_conn.php';
$r = $con->query("DESCRIBE ShopLastTransaction");
while($row = $r->fetch_assoc()) {
    echo $row['Field'].'|Null:'.$row['Null'].'|Default:'.($row['Default']??'NULL')."\n";
}

$ok = $con->query("INSERT INTO ShopLastTransaction (ShopID,Money,Method,TransactionName,TransactionStatus,CreatedAtShopLastTransaction) VALUES (732773,'-199','Wallet','Test Sub','Done',NOW())");
echo "Insert error: " . $con->error . "\n";
echo "Affected: " . $con->affected_rows . "\n";
if($ok) $con->query("DELETE FROM ShopLastTransaction WHERE ShopID=732773 AND TransactionName='Test Sub'");
?>
