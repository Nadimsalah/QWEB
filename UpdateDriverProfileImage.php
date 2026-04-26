<?php
require "conn.php";



$DriverId  		   = $_POST["DriverId"];
$Fname     		   = $_POST["Name"];
$LName    		   = $_POST["Lname"];
$DriverEmail       = $_POST["DriverEmail"];
$PersonalPhoto     = $_POST["PersonalPhoto"];

$photo1name=rand(1,700000).rand(1,700000);



$paths1 = "$photo1name.png";
$path1 =  "photo/$paths1";
$actualpath1 = $DomainNamee."$path1";





$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if(true){

   // Use prepared statements and store relative paths
   $stmt = $con->prepare("UPDATE Drivers SET DriverEmail=?, Fname=?, LName=?, PersonalPhoto=? WHERE DriverID=?");
   $stmt->bind_param("ssssi", $DriverEmail, $Fname, $LName, $paths1, $DriverId);
   
   if ($stmt->execute()) {
       // Strip prefix if present
       if (preg_match('/^data:image\/(\w+);base64,/', $PersonalPhoto, $type)) {
           $PersonalPhoto = substr($PersonalPhoto, strpos($PersonalPhoto, ',') + 1);
       }
       file_put_contents($path1, base64_decode($PersonalPhoto));

       $res = mysqli_query($con, "SELECT * FROM Drivers WHERE DriverID=$DriverId");
       $driver = mysqli_fetch_assoc($res);
       
       // Resolve for the response
       $driver['PersonalPhoto'] = resolvePhotoUrl($driver['PersonalPhoto'], $driver['Fname']);
       
       $message = "Updated Successfully";
       $success = true;
       $status_code = 200;
       echo json_encode(array('status_code' => $status_code, 'success' => $success, "data" => $driver, "message" => $message));
   } else {
       $message = "Error Updating Database";
       $success = false;
       $status_code = 200; // Maintaining existing status code pattern
       echo json_encode(array('status_code' => $status_code, 'success' => $success, "message" => $message));
   }

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