<?php
require 'conn.php';
$query = "ALTER TABLE Users ADD COLUMN CategoryOrder TEXT DEFAULT NULL";
if (mysqli_query($con, $query)) {
    echo "Column added successfully";
} else {
    echo "Error: " . mysqli_error($con);
}
