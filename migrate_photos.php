<?php
require 'conn.php';

// Check all users with jibler.app in their photo URL
$res = $con->query("SELECT UserID, name, UserPhoto FROM Users WHERE UserPhoto LIKE '%jibler%' OR UserPhoto LIKE '%partener%' ORDER BY UserID DESC");
$count = $res->num_rows;
echo "Users with old jibler.app photo URLs: $count\n\n";

while ($row = $res->fetch_assoc()) {
    $oldUrl = $row['UserPhoto'];
    $filename = basename(parse_url($oldUrl, PHP_URL_PATH));
    $newUrl = 'photo/' . $filename;
    echo "ID: {$row['UserID']} | Name: {$row['name']}\n";
    echo "  OLD: $oldUrl\n";
    echo "  NEW: $newUrl\n\n";
    
    // Fix it
    $stmt = $con->prepare("UPDATE Users SET UserPhoto = ? WHERE UserID = ?");
    $stmt->bind_param("si", $newUrl, $row['UserID']);
    if ($stmt->execute()) {
        echo "  ✓ FIXED in DB\n\n";
    } else {
        echo "  ✗ FAILED: " . $stmt->error . "\n\n";
    }
}

echo "Done! All old jibler.app photo URLs have been migrated.\n";
?>
