<?php
require '../api_conn.php';
$q = $con->query("DESCRIBE Categories");
while($r = $q->fetch_assoc()) echo $r['Field'].' - '.$r['Type']."\n";
?>
