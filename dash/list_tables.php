<?php
require 'conn.php';
$res = $con->query("SHOW TABLES");
while($r = $res->fetch_row()) echo $r[0]."\n";
?>
