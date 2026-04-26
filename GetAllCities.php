<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;



$CountryID = $_POST["CountryID"];
if($CountryID == ""){
	
	$CountryID = "1";
}
$SellerLat   = $_POST["UserLat"];
$SellerLongt = $_POST["UserLongt"];

if($SellerLongt==""){
	$res = mysqli_query($con,"SELECT * FROM DeliveryZone WHERE CountryID = '$CountryID' order by CityName asc");
}else{
	
	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($SellerLat)) * cos(radians(CityLat)) * cos(radians(CityLongt) - radians($SellerLongt)) + sin(radians($SellerLat)) * sin(radians(CityLat)))) AS distance FROM DeliveryZone WHERE CountryID = '$CountryID' ORDER BY distance ASC");
	
}
$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

 $row["CityName"] =$row["CityName"] ;
 $row["CityID"] = $row["DeliveryZoneID"] ;

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