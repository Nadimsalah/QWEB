<?php
header('Content-Type: application/json');
require_once 'conn.php';

$results = [];

// 1. Check photo folder exists
$photoDir = __DIR__ . '/photo/';
$results['photo_dir_exists'] = is_dir($photoDir);

// 2. Check write permission
$testFile = $photoDir . 'write_test_' . time() . '.txt';
$written = @file_put_contents($testFile, 'permission_check');
$results['photo_dir_writable'] = ($written !== false);
if ($written !== false) {
    @unlink($testFile);
}

// 3. Check DB connection
$results['db_connected'] = ($con && !$con->connect_error);

// 4. Check current domain resolution
$results['DomainNamee'] = $DomainNamee;

// 5. Check latest uploaded photos in DB
$res = $con->query("SELECT UserID, name, UserPhoto FROM Users WHERE UserPhoto != '' AND UserPhoto != 'NONE' ORDER BY UserID DESC LIMIT 5");
$latestPhotos = [];
while ($row = $res->fetch_assoc()) {
    $latestPhotos[] = [
        'UserID' => $row['UserID'],
        'name' => $row['name'],
        'UserPhoto_raw' => $row['UserPhoto'],
        'UserPhoto_resolved' => resolvePhotoUrl($row['UserPhoto'], $row['name'])
    ];
}
$results['latest_users_photos'] = $latestPhotos;

// 6. Check if sample photo file exists on disk
$sampleRow = $latestPhotos[0] ?? null;
if ($sampleRow && strpos($sampleRow['UserPhoto_raw'], 'http') === false) {
    $filePath = $photoDir . basename($sampleRow['UserPhoto_raw']);
    $results['sample_photo_on_disk'] = file_exists($filePath) ? 'EXISTS' : 'MISSING (file not on this server)';
    $results['sample_photo_path_checked'] = $filePath;
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
