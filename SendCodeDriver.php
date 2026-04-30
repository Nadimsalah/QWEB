<?php

require "conn.php";

$pass="a";
$PhoneNumber = $_POST["PhoneNumber"];
$Code = $_POST["Code"];
$FirebaseDriverToken  = $_POST["FirebaseDriverToken"];



if($Code=='1234'){

$test=0;
$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverPhone='$PhoneNumber'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;
$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 && !empty($result)){
	
	$sql22="UPDATE Drivers SET FirebaseDriverToken='$FirebaseDriverToken' WHERE DriverPhone='$PhoneNumber'";
					if(mysqli_query($con,$sql22))
					{
						
					}
	
    $message ="loged sucssesfully";
    $success = true;
    $status_code = 200;

    $data = isset($result[0]) ? $result[0] : new stdClass();
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$data,"message"=>$message));
}
else{
	$message ="Error";
    $success = false;
    $status_code = 200;
    $data = new stdClass();
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$data,"message"=>$message));
}

}else{
	$message ="Wrong code";
    $success = false;
    $status_code = 200;
    $data = new stdClass();
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$data,"message"=>$message));
}
die;
mysqli_close($con);
?>