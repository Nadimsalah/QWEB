<?php
require 'conn.php';
$res = $con->query('SELECT UserID, name, UserPhoto FROM Users WHERE UserID = 1000018');
print_r($res->fetch_assoc());
?>
