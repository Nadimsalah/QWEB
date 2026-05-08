<?php
require_once __DIR__ . '/../api_conn.php';

$res = $con->query("SHOW COLUMNS FROM Orders LIKE 'OrderSource'");
if ($res->num_rows == 0) {
    if ($con->query("ALTER TABLE Orders ADD COLUMN OrderSource VARCHAR(50) DEFAULT 'App'")) {
        echo "Successfully added 'OrderSource' column to Orders.\n";
    } else {
        echo "Error: " . $con->error . "\n";
    }
} else {
    echo "Column 'OrderSource' already exists.\n";
}
?>
