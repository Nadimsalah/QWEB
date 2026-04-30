<?php
require 'conn.php';
$q = mysqli_query($con, 'SHOW COLUMNS FROM Orders');
$cols = [];
while($r = mysqli_fetch_assoc($q)) $cols[] = $r['Field'];
echo implode(", ", $cols);
?>
