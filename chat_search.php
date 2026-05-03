<?php
header('Content-Type: application/json');
require_once 'conn.php';

// Only allow logged-in users
session_start();
if (empty($_SESSION['UserID'])) {
    echo json_encode(['found' => false, 'error' => 'Not logged in']);
    exit;
}

$searchPhone = trim($_GET['phone'] ?? '');

if ($searchPhone === '' || !$con) {
    echo json_encode(['found' => false]);
    exit;
}

// Clean: strip spaces, dashes, parentheses
$cleanSearch = preg_replace('/[\s\-\(\)]+/', '', $searchPhone);

// Digits only
$digitsOnly = preg_replace('/[^0-9]/', '', $cleanSearch);

if (strlen($digitsOnly) < 4) {
    echo json_encode(['found' => false]);
    exit;
}

// Match numbers ending with these digits (handles country code variations)
$searchPattern = '%' . ltrim($digitsOnly, '0');

$stmt = $con->prepare("SELECT UserID, name, UserPhoto, PhoneNumber FROM Users WHERE PhoneNumber LIKE ? LIMIT 1");
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // Build full photo URL
    $photo = $row['UserPhoto'] ?? '';
    if ($photo && !str_starts_with($photo, 'http')) {
        // Try to build full URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $domain   = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $photo    = rtrim($domain, '/') . '/' . ltrim($photo, '/');
    }
    if (!$photo) {
        $photo = 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=6C63FF&color=fff&size=128';
    }

    echo json_encode([
        'found' => true,
        'id'    => $row['UserID'],
        'name'  => $row['name'] ?: 'User',
        'phone' => $row['PhoneNumber'],
        'photo' => $photo,
    ]);
} else {
    echo json_encode(['found' => false]);
}

$stmt->close();
$con->close();
