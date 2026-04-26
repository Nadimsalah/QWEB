<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$UserID = $_POST["UserID"];



$res = mysqli_query($con,"SELECT DriverRates.CreatedAtDriverRates,DriverRates.RATE,Drivers.FName,Drivers.PersonalPhoto FROM DriverRates JOIN Drivers ON Drivers.DriverID = DriverRates.DriverID WHERE DriverRates.UserID='$UserID' ORDER BY CreatedAtDriverRates DESC");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

}


$res2 = mysqli_query($con,"SELECT ShopRates.RATE,ShopRates.CreatedAtShopRates,Shops.ShopName,Shops.ShopLogo FROM ShopRates JOIN Shops ON Shops.ShopID = ShopRates.ShopID WHERE ShopRates.UserID='$UserID' ORDER BY CreatedAtShopRates DESC");

$result2 = array();



while($row2 = mysqli_fetch_assoc($res2)){

//$data = $row[0];
$result2[] = $row2;



$test=4;

}

/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"data2"=>$result2,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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