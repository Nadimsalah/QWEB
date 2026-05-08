<?php
require 'conn.php';
$res = $con->query("DESCRIBE Foods");
if($res) while($r = $res->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n";
?>
