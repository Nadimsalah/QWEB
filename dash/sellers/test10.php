<?php
require '../api_conn.php';
$q = $con->query("SELECT DISTINCT Type FROM Categories");
while($r = $q->fetch_assoc()) echo $r['Type']."\n";
?>
