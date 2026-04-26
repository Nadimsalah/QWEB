<?php
require "conn.php";


$DriverID     = $_POST["DriverID"];

$LANG = $_POST["LANG"];


$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
}


$test=4;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
//	if($test==4 || empty($result)){
		
		
	$sql22="UPDATE Drivers SET LANG='$LANG' WHERE DriverID=$DriverID";
	if(mysqli_query($con,$sql22))
	{	
		
	$message ="Updated sucssesfully";
    $success = true;
    $status_code = 200;
	//$result = []; 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));		
	}
	else
   {
	   
	$message ="Date Used Before";
    $success = false;
    $status_code = 400;
	$result = []; 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
//	echo json_encode($key);
	
   }
   
//	}
	
// 	else{
		
		
// 			$message ="Error Token Eror";
// 			$success = false;
// 			$status_code = 200;
// 			$result = []; 
// 			echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
// 	}


   
   
   
   
   
   
die;
mysqli_close($con);
?>