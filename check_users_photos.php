<?php
define('FROM_UI', true);
require 'conn.php';
$res = mysqli_query($con, "SELECT UserID, name, UserPhoto FROM Users ORDER BY UserID DESC LIMIT 15");
while($row = mysqli_fetch_assoc($res)) {
    echo "ID: " . $row['UserID'] . " | Name: " . $row['name'] . " | Photo: " . $row['UserPhoto'] . "\n";
}
