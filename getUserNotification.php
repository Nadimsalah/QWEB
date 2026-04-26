<?php

require "conn.php";
$test=0;

$UserID = $_POST["UserID"];

$res = mysqli_query($con,"SELECT * FROM UserNotification LEFT JOIN Orders ON UserNotification.OrderID = Orders.OrderID join Drivers ON Drivers.DriverID = Orders.DelvryId WHERE UserNotification.UserID='$UserID' ORDER BY UserNotification.NotificationID DESC");

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



$sql="UPDATE UserNotification SET seen='seen' WHERE UserID='$UserID'";
   if(mysqli_query($con,$sql))
   {

    }


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