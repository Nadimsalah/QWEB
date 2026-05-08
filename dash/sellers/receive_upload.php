<?php
/**
 * receive_upload.php — Live server endpoint
 * Receives a file pushed from local dev machine and saves it to /photo/
 * Deploy this file to: https://qoon.app/dash/sellers/receive_upload.php
 */
$SECRET_TOKEN = 'qoon_sync_2024';

if (($_GET['token'] ?? '') !== $SECRET_TOKEN) {
    http_response_code(403);
    die('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('POST required');
}

if (empty($_FILES['file']['tmp_name'])) {
    die('No file received');
}

$fileName = basename($_GET['file'] ?? '');
if (empty($fileName) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $fileName)) {
    die('Invalid filename');
}

$dest = __DIR__ . '/../photo/' . $fileName;
if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    echo "OK: $fileName saved";
} else {
    http_response_code(500);
    echo "FAILED saving $fileName";
}
?>
