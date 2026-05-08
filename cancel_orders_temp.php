<?php
require "conn.php";
// Only allow a specific token to prevent abuse
if (isset($_GET['token']) && $_GET['token'] === 'antigravity_secret') {
    // Cancel the last 20 orders
    // Make sure we only cancel orders that are not already cancelled or delivered
    $res = mysqli_query($con, "UPDATE Orders SET OrderState = 'Canceled' WHERE OrderState NOT IN ('Canceled', 'Delivered', 'Cancelled') ORDER BY OrderID DESC LIMIT 20");
    if($res) {
        echo "SUCCESS: Cancelled last 20 active orders.";
    } else {
        echo "ERROR: " . mysqli_error($con);
    }
} else {
    echo "Unauthorized";
}
?>
