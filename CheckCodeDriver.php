<?php
use Twilio\Rest\Client; 
require "conn.php";


$DriverPhone     = $_POST["DriverPhone"];
$UserFirebaseToken  = $_POST["UserFirebaseToken"];
$Code = $_POST["Code"]; 




$res = "";

		$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverPhone='$DriverPhone' AND sentcode='$Code'");
		
		$test=0;

		$result = array();


		$AccountState = "NewAccount";

		while($row = mysqli_fetch_assoc($res)){

		$result[] = $row;

		$AccountState = $row["AccounntType"];
		$test=1;

		}
		
		
		
		
		if($test==0){
			

						$message ="Error Code";
						$success = false;
						$status_code = 200;
						
						echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
		}else {
					$message ="Loged sucssesfully";
					$success = true;
					$status_code = 200;
					echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
		}
   
die;
mysqli_close($con);
?>