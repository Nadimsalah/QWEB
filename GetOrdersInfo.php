<?php

require "conn.php";
$test=0;

$OrderID = $_POST["OrderID"];

$res = mysqli_query($con,"SELECT Orders.*, Drivers.*, Users.name, Users.PhoneNumber, Users.UserPhoto, Shops.ShopName, Shops.ShopLat, Shops.ShopLongt, Shops.ShopPhone, Shops.ShopLogo FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Users ON Users.UserID = Orders.UserID LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE Orders.OrderID='$OrderID' ORDER BY Orders.OrderID DESC");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

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
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>