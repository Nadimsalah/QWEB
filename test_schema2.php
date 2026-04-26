<?php
require 'conn.php';
$r = $con->query("SHOW COLUMNS FROM Posts");
$p = [];
while ($row = $r->fetch_assoc()) $p[] = $row['Field'];

$r = $con->query("SHOW COLUMNS FROM Foods");
$f = [];
while ($row = $r->fetch_assoc()) $f[] = $row['Field'];

$r = $con->query("SHOW COLUMNS FROM Shops");
$s = [];
while ($row = $r->fetch_assoc()) $s[] = $row['Field'];

echo json_encode(["Posts" => $p, "Foods" => $f, "Shops" => $s]);
