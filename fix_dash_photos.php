<?php
// ============================================================
// QOON PHOTO FIX — migrate /dash/photo/ → correct path
// Run ONCE on the live server then DELETE this file
// ============================================================
header('Content-Type: application/json');
require_once 'conn.php';

$fixed = [];
$failed = [];

// Find all users with /dash/photo/ absolute URLs
$res = $con->query("SELECT UserID, name, UserPhoto FROM Users WHERE UserPhoto LIKE '%/dash/photo/%'");

while ($row = $res->fetch_assoc()) {
    $oldUrl = $row['UserPhoto'];
    // Extract just the filename
    $filename = basename(parse_url($oldUrl, PHP_URL_PATH));
    // Store as plain filename (relative) — resolvePhotoUrl will build the correct URL
    $newValue = $filename;
    
    $stmt = $con->prepare("UPDATE Users SET UserPhoto = ? WHERE UserID = ?");
    $stmt->bind_param("si", $newValue, $row['UserID']);
    
    if ($stmt->execute()) {
        $fixed[] = [
            'UserID' => $row['UserID'],
            'name' => $row['name'],
            'old' => $oldUrl,
            'new' => $newValue,
            'resolved_url' => resolvePhotoUrl($newValue, $row['name'])
        ];
    } else {
        $failed[] = ['UserID' => $row['UserID'], 'error' => $stmt->error];
    }
}

echo json_encode([
    'fixed_count' => count($fixed),
    'failed_count' => count($failed),
    'fixed' => $fixed,
    'failed' => $failed,
    'message' => count($fixed) > 0 ? 'Migration complete! Delete this file.' : 'No /dash/photo/ URLs found.'
], JSON_PRETTY_PRINT);
?>
