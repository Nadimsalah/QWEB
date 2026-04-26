<?php
require_once 'conn.php';

header('Content-Type: application/json');

$userId = $_COOKIE['qoon_user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order = $_POST['order'] ?? '';

if (empty($order)) {
    echo json_encode(['success' => false, 'message' => 'No order provided']);
    exit;
}

$stmt = $con->prepare("UPDATE Users SET CategoryOrder = ? WHERE UserID = ?");
$stmt->bind_param("ss", $order, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
