<?php
require 'conn.php';
$UserID = 1000018; // From upload_debug.txt
$stmt = $con->prepare("UPDATE Users SET UserPhoto=? WHERE UserID=?");
$newFilename = 'test_update.jpg';
$stmt->bind_param("si", $newFilename, $UserID);
if ($stmt->execute()) {
    echo "SUCCESS\n";
} else {
    echo "ERROR: " . $stmt->error . "\n";
}
