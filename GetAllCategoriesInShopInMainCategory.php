<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryId = $_POST["CategoryId"];
$UserLat    = $_POST["UserLat"];
$UserLongt    = $_POST["UserLongt"];


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM ShopsCategory JOIN Shops ON ShopsCategory.ShopID = Shops.ShopID WHERE Shops.CategoryID ='$CategoryID' AND Shops.Status = 'ACTIVE' HAVING distance <= 50 ORDER BY distance ASC ");


$res = mysqli_query($con,"SELECT * FROM KinzMadintySmallProducts WHERE CategoryId ='$CategoryId'");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


 unset($row["CategoryId"]);



$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>