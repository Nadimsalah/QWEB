<?php
$start = microtime(true);
require_once 'conn.php';
$conn_time = microtime(true);

if($con) {
    try {
        $con->query("SELECT * FROM Categories WHERE Type='Top' ORDER BY priority DESC LIMIT 20");
    } catch(Throwable $e){}
}
$cat_time = microtime(true);

if($con) {
    try {
        $con->query("SELECT Shops.*, Posts.*, Foods.* 
                  FROM Posts 
                  LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                  LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID 
                  WHERE Posts.PostStatus='ACTIVE' 
                  ORDER BY Posts.CreatedAtPosts DESC 
                  LIMIT 15");
    } catch(Throwable $e){}
}
$post_time = microtime(true);

echo "Connection took: " . ($conn_time - $start) . " seconds<br>";
echo "Categories query took: " . ($cat_time - $conn_time) . " seconds<br>";
echo "Posts query took: " . ($post_time - $cat_time) . " seconds<br>";
?>
