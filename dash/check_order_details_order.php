<?php
require 'conn.php';
$res = $con->query("DESCRIBE OrderDetailsOrder");
while($r = $res->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n";
?>
