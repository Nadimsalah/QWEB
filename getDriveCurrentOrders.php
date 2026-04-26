<?php

require "conn.php";
$test=0;

$DelvryId = $_POST["DelvryId"];

$Page = $_POST["Page"];


$Page = $_POST["Page"];  

if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 10; 
}


$res = mysqli_query($con,"SELECT Orders.*,Users.UserID,Users.name,Users.PhoneNumber,Users.UserPhoto,Shops.ShopName,Shops.ShopLat,Shops.ShopID,Shops.ShopLongt FROM Orders LEFT JOIN Users ON Users.UserID = Orders.UserID LEFT JOIN Shops ON Orders.DestinationName = Shops.ShopName WHERE DelvryId='$DelvryId' ORDER BY OrderID DESC LIMIT $Page, 10");

$result = array();
$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$row["MaxDeliveryPrice"]  = (string)$row["MaxDeliveryPrice"];

if($row["OrderTypeSender"]=="COMPANY"){
	
	$CompanyID = $row["CompanyID"];
	$res2 = mysqli_query($con,"SELECT * FROM Company WHERE CompanyID = $CompanyID");
		while($row2 = mysqli_fetch_assoc($res2)){
			
			$row["UserPhoto"] = $row2["Logo"];
			$row["DestnationPhoto"] = $row2["Logo"];
			
		}
		
	
	
}

	$UserCitiesID = $row["UserCitiesID"];
    $DestnationCitiesID = $row["DestnationCitiesID"];
    $UserCountryId = $row["UserCountryId"];
    $DestnationCountryId = $row["DestnationCountryId"]; 
	
	
	if($UserCitiesID==""){ $UserCitiesID = 53;}
	if($DestnationCitiesID==""){ $DestnationCitiesID = 53;}
	if($UserCountryId==""){ $UserCountryId = 1;}
	if($DestnationCountryId==""){ $DestnationCountryId = 1;}
		
// 	    $res2 = mysqli_query($con,"SELECT CityName FROM DeliveryZone WHERE DeliveryZoneID = $UserCitiesID");
//     while($row2 = mysqli_fetch_assoc($res2)){ $row["UserCityName"] = $row2["CityName"]; }
    
//      $res2 = mysqli_query($con,"SELECT CityName FROM DeliveryZone WHERE DeliveryZoneID = $DestnationCitiesID");
//     while($row2 = mysqli_fetch_assoc($res2)){ $row["DestnationCityName"] = $row2["CityName"]; }
    
//      $res2 = mysqli_query($con,"SELECT CountryName FROM Countries WHERE CountryID  = '$UserCountryId'");
//     while($row2 = mysqli_fetch_assoc($res2)){ $row["UserCountryName"] = $row2["CountryName"]; }
    
//      $res2 = mysqli_query($con,"SELECT CountryName FROM Countries WHERE CountryID  = '$DestnationCountryId'");
//     while($row2 = mysqli_fetch_assoc($res2)){ $row["DestnationCountryName"] = $row2["CountryName"]; }
	
	
// 	$WeightsId = $row["WeightsId"]; 
// 	if($WeightsId!='-'){
// 		$res2 = mysqli_query($con,"SELECT WeightText FROM Weights WHERE WeightsId  = $WeightsId");
// 		while($row2 = mysqli_fetch_assoc($res2)){ $row["WeightText"] = $row2["WeightText"]; }
// 	}

if(empty($row["UserPhoto"]) || $row["UserPhoto"] == "0"){
    $row["UserPhoto"] = "https://ui-avatars.com/api/?name=".urlencode($row["name"] ?? 'User')."&background=random";
}

$result[] = $row;

$OrderID = $row["OrderID"]; 
$result3 = array();
$res3 = mysqli_query($con,"SELECT * FROM OrderDetailsOrder Join Foods ON OrderDetailsOrder.FoodID = Foods.FoodID WHERE OrderDetailsOrder.OrderID='$OrderID'");
while($row3 = mysqli_fetch_assoc($res3)){
	$result3[] = $row3;
}

array_splice($result[$i], 1000, 1010, array($result3));
    
$result[$i]["Food"] = $result[$i]["0"];
unset($result[$i]["0"]);



$test=4;
$i++;
}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
    
    
    
    
$res = mysqli_query($con,"SELECT count(*) FROM Orders LEFT JOIN Users ON Users.UserID = Orders.UserID LEFT JOIN Shops ON Orders.DestinationName = Shops.ShopName WHERE DelvryId='$DelvryId' ORDER BY OrderID DESC");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count =  $row["count(*)"];

}
	
	
$Page = (int)$Page/10;
$next = 0;
$next = 10 * $Page;
$next = $next +10;
$has = false;

if($next>=$count){
    
    $has = false;
}else{
    $has =  true;
}

$array = array(
    "allresult" => $count,
    "currentpage" => $Page,
    "hasNextPage" => $has,
);    
    

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array));
}
else{
	$message ="Error";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>