<?php
require '../api_conn.php';
$q = $con->query("DESCRIBE Shops");
while($r = $q->fetch_assoc()) echo $r['Field'].",";
?>
