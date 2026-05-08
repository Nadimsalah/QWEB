<?php
require 'conn.php';
echo "--- ShopsCategory ---\n";
$res = $con->query("DESCRIBE ShopsCategory");
if($res) while($r = $res->fetch_assoc()) echo $r['Field'] . "\n";

echo "\n--- Foods ---\n";
$res2 = $con->query("SELECT * FROM Foods LIMIT 1");
if($res2) {
    $row = $res2->fetch_assoc();
    print_r(array_keys($row));
}
?>
