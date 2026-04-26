<?php
require 'conn.php';
$r = $con->query("SELECT FoodPhoto FROM Foods WHERE FoodName LIKE '%tacos%' LIMIT 10");
$out = [];
while($row = $r->fetch_assoc()) $out[] = $row['FoodPhoto'];
echo json_encode($out);
