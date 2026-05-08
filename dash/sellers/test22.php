<?php
require '../api_conn.php';
$res = $con->query('DESCRIBE Orders');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
