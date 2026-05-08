<?php
require 'conn.php';
$res = $con->query("DESCRIBE OrderDetails");
while($r = $res->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n";
?>
