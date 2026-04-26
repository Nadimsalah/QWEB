<?php
require "conn.php";


$RequestUserFilesID     = $_POST["RequestUserFilesID"];

$test=0;

$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
} 


//echo $Token;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE Token='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
		if($test==4 || empty($result)){

$sql="DELETE FROM RequestUserFiles WHERE RequestUserFilesID = $RequestUserFilesID";


   if(mysqli_query($con,$sql))
   {
	
	        
    $message ="successfully";
    $success = true;
    $status_code = 200;
	$result = [];

	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
						
	
   }
   else
   {

    $message ="error";
    $success = false;
    $status_code = 200;

		echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
   }
   
   }
	
	else{
		
		
			$message ="Error Token Eror";
			$success = false;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}
	
	
	
	
   
die;
mysqli_close($con);



?>