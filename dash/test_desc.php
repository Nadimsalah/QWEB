<?php
require 'conn.php';
$res = mysqli_query($con, 'DESCRIBE Orders');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
