<?php
require 'conn.php';
$userId = $_COOKIE['qoon_user_id'] ?? 42; // Just default to something if no cookie
$res = mysqli_query($con, "SELECT * FROM UserTransaction ORDER BY UserTransactionID DESC LIMIT 5");
$rows = [];
while($r = mysqli_fetch_assoc($res)){ $rows[] = $r; }
echo json_encode($rows, JSON_PRETTY_PRINT);
?>
