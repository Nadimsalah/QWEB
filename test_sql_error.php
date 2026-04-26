<?php
require 'conn.php';
$r = $con->query("
    SELECT ShopID FROM Shops 
    WHERE (
        EXISTS (SELECT 1 FROM Posts WHERE ShopID = Shops.ShopID AND PostStatus='ACTIVE' AND PostPhoto != '' AND PostPhoto != '0')
        OR 
        EXISTS (
            SELECT 1 FROM Foods 
            LEFT JOIN ShopsCategory ON ShopsCategory.CategoryShopID = Foods.FoodCatID
            WHERE (Foods.FoodShopID = Shops.ShopID OR ShopsCategory.ShopID = Shops.ShopID)
              AND Foods.FoodPhoto != '' AND Foods.FoodPhoto != '0'
        )
    )
    LIMIT 12
");
if (!$r) {
    echo "SQL ERROR: " . $con->error;
} else {
    echo "SUCCESS: " . $r->num_rows . " shops found.";
}
