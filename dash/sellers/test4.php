<?php
require '../api_conn.php';
$con->query("ALTER TABLE Shops MODIFY COLUMN FB_AccessToken TEXT");
$con->query("ALTER TABLE Shops MODIFY COLUMN IG_AccessToken TEXT");

$tok = "EAAXM3tZAadsMBRbGyZCP34Qc0VZAv7UWMxbiVCkFCZCRQChGfiZCyZAsG3IEikH4kR2mBAwXdhGVpvE49XZCRZBdlseEcNjkZBdSOZCkTbF8c6rK2MIFgvDpgZC1wU7a88rUUaqUj64hb9OWXcXchu3EYZCOYYantx4Q6QovOFpddkYsiZCsgeBRt7ZBF2ZAWHwNClZAdvcxEBZBo4o0o7W0y0yofsiKHYOQDPXOopSoZA8VTvwlAzl63ZBLxDJMSpSZAuyGZBpuqhzvs4p21RDoZCgRmMylom9uEKhKZAZAZCi2ZA2NwVwAbCBdy0jRMEoe9sfHiXONenXWOjVm8ZATmMJHyZCi";
$pid = "2164785670987819";
$q = $con->query("UPDATE Shops SET FB_AccessToken='$tok', FB_PageID='$pid'");
if($q) { echo "Success mapping user to Shop."; } else { echo "Error: ".$con->error; }
?>
