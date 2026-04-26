<?php require 'conn.php'; $res = $con->query('SELECT * FROM Users LIMIT 1'); print_r(array_keys($res->fetch_assoc())); ?>
