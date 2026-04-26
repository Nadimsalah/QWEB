<?php

require "conn.php";
$test=0;

$ShopID = $_POST["ShopID"];

$res = mysqli_query($con,"SELECT DelvLongPrice FROM DeliveryZone JOIN Shops ON DeliveryZone.DeliveryZoneID = Shops.CityID WHERE Shops.ShopID=$ShopID");

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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
die;
mysqli_close($con);
?>