<?php

require "conn.php";
$test=0;

$DelvryId = $_POST["DelvryId"];


$res = mysqli_query($con,"SELECT * FROM DriverNotification LEFT JOIN Orders ON DriverNotification.OrderID = Orders.OrderID join Users ON Users.UserID = Orders.UserID  WHERE DriverID='$DelvryId' ORDER BY DriverNotification.NotificationID DESC");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    
    
    $sql="UPDATE DriverNotification SET seen='seen' WHERE DriverID='$DelvryId'";
   if(mysqli_query($con,$sql))
   {

    }
    
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