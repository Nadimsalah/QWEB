<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT * FROM Categories LIMIT 1');
if($res) {
    print_r(mysqli_fetch_assoc($res));
} else {
    echo "Query failed: " . mysqli_error($con);
}
?>
