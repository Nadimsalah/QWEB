<?php
require 'conn.php';
$res = $con->query("SELECT name, UserPhoto FROM Users WHERE UserPhoto IS NOT NULL AND UserPhoto != '' LIMIT 5");
if($res) {
    while($row = $res->fetch_assoc()) {
        echo "User: " . $row['name'] . " | Photo: " . $row['UserPhoto'] . "\n";
    }
}
?>
