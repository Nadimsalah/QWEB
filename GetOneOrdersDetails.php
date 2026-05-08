<?php

require "conn.php";
$test=0;

$OrderID = $_POST["OrderID"];


				$res = mysqli_query($con,"SELECT UserFees FROM UserTransaction WHERE OrderID = $OrderID");
                        
                                        $result = array();
                        
					$UserFees = '-';
					while($row = mysqli_fetch_assoc($res)){
						$UserFees = $row["UserFees"];
					}
                                                               

$res = mysqli_query($con,"SELECT Orders.*, Drivers.*, Users.name, Users.PhoneNumber, Users.UserPhoto, 
    IFNULL(IFNULL(s.ShopName, s2.ShopName), Orders.DestinationName) as ShopName,
    IFNULL(s.ShopLat, s2.ShopLat) as ShopLat,
    IFNULL(s.ShopLongt, s2.ShopLongt) as ShopLongt,
    IFNULL(s.ShopPhone, s2.ShopPhone) as ShopPhone,
    IFNULL(IFNULL(s.ShopLogo, s2.ShopLogo), Orders.DestnationPhoto) as ShopLogo 
    FROM Orders 
    LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId 
    LEFT JOIN Users ON Users.UserID = Orders.UserID 
    LEFT JOIN Shops s ON s.ShopID = Orders.ShopID 
    LEFT JOIN Shops s2 ON s2.ShopName = Orders.DestinationName
    WHERE Orders.OrderID='$OrderID' 
    ORDER BY Orders.OrderID DESC");

$result = array();
$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
if($UserFees!='-'){
    $currentPrice = isset($row["OrderPriceFromShop"]) && is_numeric($row["OrderPriceFromShop"]) ? (float)$row["OrderPriceFromShop"] : 0;
    $fee = is_numeric($UserFees) ? (float)$UserFees : 0;
    $row["OrderPriceFromShop"] = (string)($currentPrice + $fee);
    $row["UserFees"] = $UserFees;
}

if(empty($row["name"]) || $row["name"] == null){
    $row["name"] = $row["UserName"] ?? 'Customer';
}
if(empty($row["PhoneNumber"]) || $row["PhoneNumber"] == null || $row["PhoneNumber"] == "0"){
    $row["PhoneNumber"] = $row["UserPhone"] ?? '';
}
if(empty($row["UserPhoto"]) || $row["UserPhoto"] == "0" || $row["UserPhoto"] == "null"){
    // Try by phone
    if (!empty($row['UserPhone'])) {
        $safePhone = mysqli_real_escape_string($con, $row['UserPhone']);
        $cleanPhone = preg_replace('/[^0-9]/', '', $row['UserPhone']);
        if (strlen($cleanPhone) >= 8) {
            $last8 = substr($cleanPhone, -8);
            $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE PhoneNumber LIKE '%$last8' LIMIT 1");
        } else {
            $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE PhoneNumber='$safePhone' LIMIT 1");
        }
        if ($photoRow = mysqli_fetch_assoc($photoRes)) {
            if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                $row['UserPhoto'] = $photoRow['UserPhoto'];
            }
            if (!empty($photoRow['name'])) { $row['name'] = $photoRow['name']; }
        }
    }
    // Try by email
    if ((empty($row['UserPhoto']) || $row['UserPhoto'] === '0') && !empty($row['UserEmail'])) {
        $safeEmail = mysqli_real_escape_string($con, $row['UserEmail']);
        $photoRes = mysqli_query($con, "SELECT UserPhoto, name FROM Users WHERE Email='$safeEmail' LIMIT 1");
        if ($photoRow = mysqli_fetch_assoc($photoRes)) {
            if (!empty($photoRow['UserPhoto']) && $photoRow['UserPhoto'] !== '0') {
                $row['UserPhoto'] = $photoRow['UserPhoto'];
            }
            if (!empty($photoRow['name'])) { $row['name'] = $photoRow['name']; }
        }
    }
    // Final fallback: generated avatar
    if (empty($row['UserPhoto']) || $row['UserPhoto'] === '0' || $row['UserPhoto'] === 'null') {
        $row["UserPhoto"] = "https://ui-avatars.com/api/?name=".urlencode($row["name"] ?? 'User')."&background=random";
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

$row["ShopPickupPin"] = str_pad(abs(crc32($row["OrderID"] . "QOON_SHOP_PICKUP_TOKEN")) % 10000, 4, '0', STR_PAD_LEFT);

if (empty($row["FourDigit"]) || $row["FourDigit"] === '0' || $row["FourDigit"] === '0000' || $row["FourDigit"] === '') {
    $deterministicPin = str_pad(abs(crc32($row["OrderID"] . "QOON_DELIVERY_PIN")) % 10000, 4, '0', STR_PAD_LEFT);
    $row["FourDigit"] = $deterministicPin;
    $updOrderID = $row["OrderID"];
    mysqli_query($con, "UPDATE Orders SET FourDigit='$deterministicPin' WHERE OrderID='$updOrderID'");
} else {
    $row["FourDigit"] = str_pad($row["FourDigit"], 4, '0', STR_PAD_LEFT);
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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>