<?php require "conn.php"; $res = $con->query("SELECT DriverCommesion FROM MoneyStop LIMIT 1"); print_r($res->fetch_assoc()); ?>
