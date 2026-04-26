<?php
require 'conn.php';
$res = $con->query('DESCRIBE UserTransaction');
if(!$res) die("No table UserTransaction: ".$con->error);
$cols = [];
while($row = $res->fetch_assoc()){
    $cols[] = $row['Field'];
}
echo "UserTransaction: " . implode(", ", $cols);
?>
