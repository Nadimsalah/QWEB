<?php

require "conn.php";
$test=0;

$DelvryId = $_POST["DelvryId"];

$res = mysqli_query($con,"SELECT Orders.*, Users.name, Users.UserPhoto as UserProfilePhoto, 
    IFNULL(s.ShopName, s2.ShopName) as ShopName,
    IFNULL(s.ShopLogo, s2.ShopLogo) as ShopLogo
    FROM Orders 
    LEFT JOIN Users ON Users.UserID = Orders.UserID 
    LEFT JOIN Shops s ON s.ShopID = Orders.ShopID
    LEFT JOIN Shops s2 ON s2.ShopName = Orders.DestinationName
    WHERE DelvryId='$DelvryId' 
    AND OrderState IN ('Doing', 'Order pickup', 'Come to take it', 'Order processed', 'Preparing') 
    ORDER BY OrderID DESC");

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
		
	    $res2 = mysqli_query($con,"SELECT CityName FROM DeliveryZone WHERE DeliveryZoneID = $UserCitiesID");
    while($row2 = mysqli_fetch_assoc($res2)){ $row["UserCityName"] = $row2["CityName"]; }
    
     $res2 = mysqli_query($con,"SELECT CityName FROM DeliveryZone WHERE DeliveryZoneID = $DestnationCitiesID");
    while($row2 = mysqli_fetch_assoc($res2)){ $row["DestnationCityName"] = $row2["CityName"]; }
    
     $res2 = mysqli_query($con,"SELECT CountryName FROM Countries WHERE CountryID  = '$UserCountryId'");
    while($row2 = mysqli_fetch_assoc($res2)){ $row["UserCountryName"] = $row2["CountryName"]; }
    
     $res2 = mysqli_query($con,"SELECT CountryName FROM Countries WHERE CountryID  = '$DestnationCountryId'");
    while($row2 = mysqli_fetch_assoc($res2)){ $row["DestnationCountryName"] = $row2["CountryName"]; }

	$WeightsId = $row["WeightsId"]; 
	if($WeightsId!='-'){
		$res2 = mysqli_query($con,"SELECT WeightText FROM Weights WHERE WeightsId  = $WeightsId");
		while($row2 = mysqli_fetch_assoc($res2)){ $row["WeightText"] = $row2["WeightText"]; }
	}

	// Fallback for Firebase Users who don't exist in the legacy MySQL Users table
	if(empty($row["name"]) || $row["name"] == null){
		$row["name"] = $row["UserName"] ?? 'Customer';
	}
	$row["UserName"] = $row["name"]; // Ensure both keys exist

	// If the primary JOIN failed to find a photo, look it up by UserID, then phone, then email
	if (empty($row["UserPhoto"]) || $row["UserPhoto"] == "0" || $row["UserPhoto"] == "null") {
		$uid = intval($row['UserID'] ?? 0);
		if ($uid > 0) {
			$photoRes = mysqli_query($con, "SELECT UserPhoto FROM Users WHERE UserID = $uid LIMIT 1");
			if ($photoRow = mysqli_fetch_assoc($photoRes)) {
				if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
					$row['UserPhoto'] = $photoRow['UserPhoto'];
				}
			}
		}
		// Fallback: look up by phone number stored in the order
		if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserPhone'])) {
			$safePhone = mysqli_real_escape_string($con, $row['UserPhone']);
			$photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE PhoneNumber='$safePhone' LIMIT 1");
			if ($photoRow = mysqli_fetch_assoc($photoRes)) {
				if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
					$row['UserPhoto'] = $photoRow['UserPhoto'];
				}
				if (!empty($photoRow['name'])) { $row['name'] = $photoRow['name']; $row['UserName'] = $photoRow['name']; }
			}
		}
		// Fallback: look up by email stored in the order
		if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserEmail'])) {
			$safeEmail = mysqli_real_escape_string($con, $row['UserEmail']);
			$photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE Email='$safeEmail' LIMIT 1");
			if ($photoRow = mysqli_fetch_assoc($photoRes)) {
				if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
					$row['UserPhoto'] = $photoRow['UserPhoto'];
				}
				if (!empty($photoRow['name'])) { $row['name'] = $photoRow['name']; $row['UserName'] = $photoRow['name']; }
			}
		}
	}

	// Handle profile photo fallback
	if(empty($row["UserPhoto"]) || $row["UserPhoto"] == "0" || $row["UserPhoto"] == "null"){
		// Try the aliased UserProfilePhoto from the JOIN first
		if(!empty($row["UserProfilePhoto"]) && $row["UserProfilePhoto"] != "0" && $row["UserProfilePhoto"] != "null") {
			$row["UserPhoto"] = $row["UserProfilePhoto"];
		} else {
			$row["UserPhoto"] = "https://ui-avatars.com/api/?name=".urlencode($row["name"])."&background=random";
		}
	}
	
	// Convert to full URLs if they are just filenames
	if (!empty($row["UserPhoto"]) && !filter_var($row["UserPhoto"], FILTER_VALIDATE_URL)) {
		$row["UserPhoto"] = "https://qoon.app/photo/" . $row["UserPhoto"];
	}
	if (!empty($row["ShopLogo"]) && !filter_var($row["ShopLogo"], FILTER_VALIDATE_URL)) {
		$row["ShopLogo"] = "https://qoon.app/photo/" . $row["ShopLogo"];
	}
	if (!empty($row["DestnationPhoto"]) && !filter_var($row["DestnationPhoto"], FILTER_VALIDATE_URL)) {
		$row["DestnationPhoto"] = "https://qoon.app/photo/" . $row["DestnationPhoto"];
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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
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