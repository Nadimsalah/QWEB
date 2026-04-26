<?php
require 'conn.php';
$res=$con->query('DESCRIBE Users');
while($row=$res->fetch_assoc()) echo $row['Field'] . ' - ' . $row['Type'] . "\n";
