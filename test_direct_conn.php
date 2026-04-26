<?php
$conn_time = microtime(true);
$dbhost = "145.223.33.118";
$dbuser = "qoon_Qoon";
$dbpass = ";)xo6b(RE}K%";
$dbname = "qoon_Qoon";

$con = mysqli_init();
$con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3);
if (!@$con->real_connect($dbhost, $dbuser, $dbpass, $dbname)) {
    echo "Fail " . $con->connect_error;
} else {
    echo "Success! Connected in " . (microtime(true) - $conn_time) . " seconds";
}
?>
