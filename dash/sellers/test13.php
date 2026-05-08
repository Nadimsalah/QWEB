<?php
require '../api_conn.php';
$q = $con->query("SELECT * FROM Categories WHERE EnglishCategory LIKE '%Khadar%' OR ArabCategory LIKE '%خضار%'");
while($r = $q->fetch_assoc()) echo json_encode($r)."\n";
?>
