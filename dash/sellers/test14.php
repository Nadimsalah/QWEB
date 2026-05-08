<?php
require '../api_conn.php';
$q = $con->query("SELECT * FROM Categories WHERE Type = 'Small' LIMIT 5");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
