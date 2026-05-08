<?php
require "api_conn.php";
$r=$con->query("SHOW COLUMNS FROM Posts");
while($row=$r->fetch_assoc()){ echo $row["Field"]."\n"; }
?>
