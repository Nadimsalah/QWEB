<?php
require 'conn.php';
$res = $con->query("DESCRIBE Users");
while($r = $res->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n";
?>
