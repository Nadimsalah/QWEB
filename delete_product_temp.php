<?php
require_once 'conn.php';

// Find and delete "Chawarma poulet au plate"
$result = $con->query("SELECT FoodID, FoodName, ShopID FROM Foods WHERE FoodName LIKE '%Chawarma poulet%' OR FoodName LIKE '%chawarma poulet%' LIMIT 10");

if ($result && $result->num_rows > 0) {
    echo "<h2>Found products:</h2><ul>";
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        echo "<li>ID: {$row['FoodID']} | Name: {$row['FoodName']} | ShopID: {$row['ShopID']}</li>";
        $ids[] = intval($row['FoodID']);
    }
    echo "</ul>";

    if (!empty($ids) && isset($_GET['confirm']) && $_GET['confirm'] === '1') {
        $idList = implode(',', $ids);
        $del = $con->query("DELETE FROM Foods WHERE FoodID IN ($idList)");
        if ($del) {
            echo "<p style='color:green;font-weight:700;'>✅ Deleted " . count($ids) . " product(s) successfully.</p>";
        } else {
            echo "<p style='color:red;'>❌ Delete failed: " . $con->error . "</p>";
        }
    } else {
        echo "<p><a href='?confirm=1' style='color:red;font-weight:700;padding:10px 20px;background:#fee2e2;border-radius:8px;text-decoration:none;'>⚠️ Click here to confirm deletion</a></p>";
    }
} else {
    echo "<p>No product found matching 'Chawarma poulet'</p>";
}
?>
