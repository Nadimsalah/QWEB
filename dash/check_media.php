<?php
require 'conn.php';
function desc($tbl) {
    global $con;
    echo "--- $tbl ---\n";
    $res = $con->query("DESCRIBE $tbl");
    if($res) while($r = $res->fetch_assoc()) echo $r['Field'] . " (" . $r['Type'] . ")\n";
    echo "\n";
}
desc('Posts');
desc('UserReals');
desc('ShopStory');
?>
