<?php
require '../api_conn.php';
$q = $con->query("SELECT Photo FROM Categories LIMIT 5");
while($r = $q->fetch_assoc()) echo $r['Photo']."\n";
?>
