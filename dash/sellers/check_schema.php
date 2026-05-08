<?php
require_once 'init.php';
$r = $con->query('DESCRIBE Shops');
while ($row = $r->fetch_assoc()) echo $row['Field'] . "\n";
?>
