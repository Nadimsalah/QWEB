<?php
require "conn.php";
$res=$con->query("DESCRIBE ShopLastTransaction");
while($row=$res->fetch_assoc()) print_r($row);
?>
