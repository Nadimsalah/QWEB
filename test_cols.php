<?php
require 'conn.php';
$r = mysqli_query($con, 'SHOW COLUMNS FROM Orders');
while($row = mysqli_fetch_assoc($r)) {
    echo $row['Field'] . "\n";
}
?>
