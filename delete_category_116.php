<?php
require "conn.php";
$query = "DELETE FROM Categories WHERE CategoryId = '116'";
if (mysqli_query($con, $query)) {
    echo "<h1>Category 116 successfully deleted from the database!</h1><p>You can now delete this file.</p>";
} else {
    echo "<h1>Error deleting category 116: " . mysqli_error($con) . "</h1>";
}
?>
