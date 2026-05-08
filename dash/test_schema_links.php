<?php
require 'conn.php';
echo "--- Posts ---\n";
$res1 = $con->query("DESCRIBE Posts");
if($res1) { while($r = $res1->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n"; }
echo "\n--- ShopStory ---\n";
$res2 = $con->query("DESCRIBE ShopStory");
if($res2) { while($r = $res2->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n"; }
echo "\n--- Tags / Linking Tables? ---\n";
$res3 = $con->query("SHOW TABLES LIKE '%Link%'");
if($res3) { while($r = $res3->fetch_row()) echo $r[0] . "\n"; }
?>
