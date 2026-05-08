<?php
require_once __DIR__ . '/../api_conn.php';
$q = $con->query("DESCRIBE Orders");
while ($r = $q->fetch_assoc()) echo $r['Field'] . ", ";
?>
