<?php
require 'conn.php';
$res = $con->query("SHOW COLUMNS FROM Orders");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
