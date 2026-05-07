<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test = 0;

$ShopID = isset($_POST["ShopID"]) ? $_POST["ShopID"] : 0;

$res = mysqli_query($con, "SELECT * FROM Foods WHERE FoodCatID IN (SELECT CategoryShopID FROM ShopsCategory WHERE ShopID='$ShopID')");

$result = array();

while($row = mysqli_fetch_assoc($res)){
    $result[] = $row;
    $test = 4;
}

if($test == 4 || empty($result)){
    $message = "successfully";
    $success = true;
    $status_code = 200;

    echo json_encode(array('status_code' => $status_code, 'success' => $success, "data" => $result, "message" => $message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
    $message = "No data";
    $success = false;
    $status_code = 200;
    $result = []; 
    echo json_encode(array('status_code' => $status_code, 'success' => $success, "data" => $result, "message" => $message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

mysqli_close($con);
?>
