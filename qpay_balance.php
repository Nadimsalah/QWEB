<?php
require_once 'conn.php';

$userId = $_COOKIE['qoon_user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'balance' => 0]);
    exit;
}

$res = $con->query("SELECT Balance FROM Users WHERE UserID = '$userId'");
if ($res && $res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $balance = floatval($row['Balance'] ?? 0);
    echo json_encode(['success' => true, 'balance' => $balance]);
} else {
    echo json_encode(['success' => false, 'balance' => 0]);
}
?>
