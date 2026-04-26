<?php
require "conn.php";



$DriverId  		   = $_POST["DriverId"];
$Fname     		   = $_POST["Name"];
$Lname     		   = $_POST["Lname"];
$DriverEmail       = $_POST["DriverEmail"];

$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if($test==4 || empty($result)){

   $sql="UPDATE Drivers SET DriverEmail='$DriverEmail',Fname='$Fname',LName='$Lname' WHERE DriverID=$DriverId";
   if(mysqli_query($con,$sql))
   {

	$message ="Updated Sucssessfuly";
    $success = true;
    $status_code = 200;
	//$result = []; 
	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DriverId");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;


$test=4;

}
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));

   }
   else
   {
	   
	   

	$message ="Error Updated";
    $success = false;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));

   }
   
	}
	
	else{
		
		
			$message ="Error Token Eror";
			$success = false;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
		
	}
die;
mysqli_close($con);
?>