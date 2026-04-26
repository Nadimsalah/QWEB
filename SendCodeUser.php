<?php

require "conn.php";

$pass="a";
$PhoneNumber = $_POST["PhoneNumber"];
$Code = $_POST["Code"];


if($Code=='123456'){

$test=0;
$res = mysqli_query($con,"SELECT * FROM Users WHERE PhoneNumber='$PhoneNumber'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;
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
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}

}else{
	$message ="Wrong code";
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
die;
mysqli_close($con);
?>