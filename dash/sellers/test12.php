<?php
require '../api_conn.php';
$q = $con->query("SELECT DISTINCT Pro FROM Categories");
while($r = $q->fetch_assoc()) echo $r['Pro']."\n";
?>
