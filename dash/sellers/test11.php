<?php
require '../api_conn.php';
$q = $con->query("SELECT * FROM Categories LIMIT 10");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
