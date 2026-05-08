<?php
require '../api_conn.php';
$q = $con->query("SELECT CategoryId, EnglishCategory, Type, Pro FROM Categories WHERE CategoryId = 56");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
