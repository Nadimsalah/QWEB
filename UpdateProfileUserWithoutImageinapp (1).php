<?php
require "conn.php";


$UserID     = $_POST["UserID"];
$fullname = $_POST["fullname"];
$email = $_POST["email"];



$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
}


$test=4;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
//	if($test==4 || empty($result)){
		
		
	$sql22="UPDATE Users SET name='$fullname',Email='$email' WHERE UserID=$UserID";
	if(mysqli_query($con,$sql22))
	{
		
		
					$res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");


					$result = array();
					while($row = mysqli_fetch_assoc($res)){

					$result[] = $row;

					}
		
		
		
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