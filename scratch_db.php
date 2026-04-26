<?php
require 'conn.php';
$r = $con->query('DESCRIBE DeliveryZone');
$fields = [];
while($row = $r->fetch_assoc()) $fields[] = $row['Field'];
echo implode(', ', $fields);
?>
