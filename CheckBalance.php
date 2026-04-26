<?php

require "conn.php";
$test=0;

$UserID   = $_POST["UserID"];
$Total    = $_POST["Total"];
$OrderID  = $_POST["OrderID"];
$Method   = $_POST["Method"];


$res = mysqli_query($con,"SELECT Balance FROM `Users` WHERE UserID ='$UserID'");

$result = array();



while($row = mysqli_fetch_assoc($res)){
	
	$Balance = $row["Balance"];
	$result[] = $row;
	
}
$test = "10"; 
if((int)$Total <= (int)$Balance){
	
	$test="4";
}else{
	$test="10";
}


/////////////
//echo json_encode(array("result"=>$result));
if($test=="4"){
    $message ="sucssesfully";
    $success = true;
	//$result  ="sucssesfully"; 
    $status_code = 200;
	
	if($OrderID!=""){
	$sql22="UPDATE Orders SET Method='$Method' WHERE OrderID = $OrderID";
	if(mysqli_query($con,$sql22))
	{}
	}
	

echo json_encode(array('status_code' => $status_code,'success' => $success,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="No Blance now";
    $success = true;
	//$result ="No Blance"; 
    $status_code = 400; 
   echo json_encode(array('status_code' => $status_code,'success' => $success,"data"=>$result[0],"message"=>$message));
}
die;
mysqli_close($con);
?>