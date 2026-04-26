<?php

require "conn.php";
$test=0;

$OrderID = $_POST["OrderID"];


				$res = mysqli_query($con,"SELECT UserFees FROM UserTransaction WHERE OrderID = $OrderID");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                        
                                       
										$UserFees = $row["UserFees"];
                                                               
                                        }

$res = mysqli_query($con,"SELECT Orders.*, Drivers.*, Users.name, Users.PhoneNumber, Users.UserPhoto, Shops.ShopName, Shops.ShopLat, Shops.ShopLongt, Shops.ShopPhone, Shops.ShopLogo FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Users ON Users.UserID = Orders.UserID LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE Orders.OrderID='$OrderID' ORDER BY Orders.OrderID DESC");

$result = array();
$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
if($UserFees!='-'){
//$row["OrderDetails"] = $row["OrderDetails"]  . 'Jibler fees ' . $UserFees ;
$row["OrderPriceFromShop"] = $row["OrderPriceFromShop"] + $UserFees;
$row["OrderPriceFromShop"] = (string)$row["OrderPriceFromShop"];
$row["UserFees"] = $UserFees;
}

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