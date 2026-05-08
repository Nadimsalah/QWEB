<?php
require '../api_conn.php';
$q = $con->query("SELECT CategoryId, EnglishCategory, ArabCategory FROM Categories WHERE EnglishCategory LIKE '%Kenz%'");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
