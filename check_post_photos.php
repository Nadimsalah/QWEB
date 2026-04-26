<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }

// Let's check for any type of separator for multiple images
$res = $con->query("SELECT PostID, PostPhoto FROM Posts WHERE PostPhoto LIKE '%|%' OR PostPhoto LIKE '%\"%' OR PostPhoto LIKE '%*%' OR PostPhoto LIKE '% %' LIMIT 5");
if ($res && $res->num_rows > 0) {
    echo "Found posts with multiple images?\n";
    while($row = $res->fetch_assoc()) {
        echo "Post ID: {$row['PostID']} | Photos: {$row['PostPhoto']}\n";
    }
} else {
    echo "No posts found with expected multiple image patterns.\n";
}

$res2 = $con->query("DESCRIBE Posts");
while($r = $res2->fetch_assoc()) {
    echo $r['Field'] . " - ";
}
echo "\n";
