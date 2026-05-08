<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT Video FROM Posts WHERE Video IS NOT NULL AND Video != "" ORDER BY PostId DESC LIMIT 1');
print_r(mysqli_fetch_assoc($res));
?>
