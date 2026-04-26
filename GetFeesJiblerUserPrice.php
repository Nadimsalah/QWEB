<?php
require "conn.php";

$total = floatval($_POST['total'] ?? 0);

// Get user fee percentage from OrdersJiblerpercentage
$res = mysqli_query($con, "SELECT disUser FROM OrdersJiblerpercentage LIMIT 1");
$pct = 10; // default
if ($row = mysqli_fetch_assoc($res)) {
    $pct = floatval($row['disUser']);
}

$fee = $total * $pct / 100;
if ($fee < 3) $fee = 3; // minimum 3 MAD

echo json_encode([
    'success' => true,
    'data'    => round($fee, 2),
    'pct'     => $pct,
    'message' => 'ok'
]);
mysqli_close($con);