<?php
require 'conn.php';
$r = $con->query("SHOW CREATE TABLE OrderDetailsOrder");
$row = $r->fetch_assoc();
echo $row['Create Table'] . "\n\n";

$r = $con->query("SELECT * FROM OrderDetailsOrder WHERE OrderID=4614");
while ($row = $r->fetch_assoc()) {
  print_r($row);
}
?>
