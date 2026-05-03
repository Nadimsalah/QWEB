<?php
header('Content-Type: application/json');
require_once 'conn.php';

$userId = $_COOKIE['qoon_user_id'] ?? '';
if (empty($userId) || !$con) {
    echo json_encode([]);
    exit;
}

$raw = $_GET['ids'] ?? '';
$ids = array_filter(array_map('trim', explode(',', $raw)));
$ids = array_slice($ids, 0, 20); // max 20

if (empty($ids)) {
    echo json_encode([]);
    exit;
}

$domain = $DomainNamee ?? 'https://qoon.app/dash/';

function buildPhotoUrl($path, $domain, $name) {
    if (!$path || $path === '0' || $path === 'NONE') {
        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=6C63FF&color=fff&size=128';
    }
    if (strpos($path, 'http') !== false) return $path;
    return rtrim($domain, '/') . '/photo/' . ltrim($path, '/');
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('s', count($ids));

$stmt = $con->prepare("SELECT UserID, name, UserPhoto FROM Users WHERE UserID IN ($placeholders)");
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$res = $stmt->get_result();

$result = [];
while ($row = $res->fetch_assoc()) {
    $result[$row['UserID']] = [
        'name'  => $row['name'] ?: 'User',
        'photo' => buildPhotoUrl($row['UserPhoto'], $domain, $row['name'] ?: 'User'),
    ];
}
$stmt->close();
$con->close();

echo json_encode($result);
