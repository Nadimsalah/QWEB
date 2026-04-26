<?php
require_once "C:/Users/dell/Desktop/userDriver/userDriver/UserDriverApi/conn.php";
if(!$con) { die("DB Connection failed"); }

// Delete posts that belong to an invalid shop (these appear as "QOON Shop" in the UI)
$query = "DELETE FROM Posts WHERE ShopID NOT IN (SELECT ShopID FROM Shops)";
if ($con->query($query) === TRUE) {
    echo "Successfully deleted orphaned posts showing up as QOON Shop.\n";
    echo "Rows affected: " . $con->affected_rows . "\n";
} else {
    echo "Error deleting posts: " . $con->error . "\n";
}

// Optionally, let's also delete any post strictly containing exact matched texts if they somehow survived.
$texts = ['test70','test50','ايايتبت','Crème visage','Gommage visage'];
foreach($texts as $t) {
    $con->query("DELETE FROM Posts WHERE PostText LIKE '%$t%'");
}
