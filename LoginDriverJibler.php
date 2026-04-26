<?php

require "conn.php";

$pass="a";
$DriverPhone = $_POST["DriverPhone"];
$DriverPassword = $_POST["DriverPassword"];
$FirebaseDriverToken = $_POST["FirebaseDriverToken"];



// echo $DriverPhone;
// echo $DriverPassword;

$test=0;
$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverPhone='$DriverPhone' AND DriverPassword='$DriverPassword'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;

$sql="UPDATE Drivers SET FirebaseDriverToken='$FirebaseDriverToken' WHERE DriverPhone='$DriverPhone'";
   if(mysqli_query($con,$sql))
   {
	   
	   
   }

$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="loged sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="الجوال او كلمة المرور خاطئ";
    $success = false;
    $status_code = 200;
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
die;
mysqli_close($con);
?>