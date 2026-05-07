<?php
error_reporting(0);
ini_set("display_errors", "0");
require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = isset($_POST["CategoryID"]) ? $_POST["CategoryID"] : "";
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;
$Page = !empty($_POST["Page"]) ? (int)$_POST["Page"] : 0;
$Page = $Page * 20; 

if ($CategoryID == "") {
    echo json_encode(['status_code' => 400, 'success' => false, "data" => [], "message" => "CategoryID is required"]);
    exit;
}

$res = mysqli_query($con,"SELECT Shops.*,Posts.*,Foods.*, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Posts ON Shops.ShopID=Posts.ShopID LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID JOIN Categories ON Shops.CategoryID = Categories.CategoryId WHERE Shops.Status = 'ACTIVE' AND Categories.CategoryId = '$CategoryID' AND Posts.PostStatus='ACTIVE' ORDER BY Posts.CreatedAtPosts DESC LIMIT $Page, 20");

$result = array();
while($row = mysqli_fetch_assoc($res)){
    $result[] = $row;
}

echo json_encode(array('status_code' => 200, 'success' => true, "data" => $result, "message" => "success"), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
mysqli_close($con);
?>
