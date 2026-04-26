<?php
require "conn.php";
$test=0;


$CountryID = $_POST["CountryID"];
$SellerLat   = $_POST["UserLat"];
$SellerLongt = $_POST["UserLongt"];

if($SellerLongt==""){
	$res = mysqli_query($con,"SELECT * FROM DeliveryZone WHERE CountryID = '$CountryID' order by CityName asc");
}else{
	
	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($SellerLat)) * cos(radians(CityLat)) * cos(radians(CityLongt) - radians($SellerLongt)) + sin(radians($SellerLat)) * sin(radians(CityLat)))) AS distance FROM DeliveryZone WHERE CountryID = '$CountryID' ORDER BY distance ASC");
	
}
$result = array();

while($row = mysqli_fetch_assoc($res)){

$result[] = $row;

$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    

    $message ="FOUND";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	$message ="NOTFOUND";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}




die;
mysqli_close($con);
?>