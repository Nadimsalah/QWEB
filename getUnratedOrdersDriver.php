<?php

require "conn.php";
$test=0;

$DelvryId = $_POST["DriverID"];

$res = mysqli_query($con,"SELECT * FROM Orders LEFT JOIN Users ON Users.UserID = Orders.UserID LEFT JOIN Shops ON Orders.DestinationName = Shops.ShopName WHERE DelvryId='$DelvryId' AND UserRated = '-' AND OrderState != 'Cancelled' ORDER BY OrderID DESC LIMIT 1");

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

// $sql="UPDATE Orders SET OrderState='Rated' WHERE UserID='$UserId' AND OrderState='Done'";
//   if(mysqli_query($con,$sql))
//   {}


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