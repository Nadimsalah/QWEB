<?php
require 'conn.php';
$res = mysqli_query($con, "DESCRIBE Users");
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
