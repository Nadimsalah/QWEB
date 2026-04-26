<?php

require "conn.php";
$test=0;

$UserId = $_POST["UserId"];

$res = mysqli_query($con,"SELECT * FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId WHERE UserID='$UserId' AND OrderState='Done' ORDER BY OrderID DESC");

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