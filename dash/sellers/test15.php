<?php
require '../api_conn.php';
$q = $con->query("SELECT * FROM Categories WHERE Pro = 'Pro' LIMIT 5");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
