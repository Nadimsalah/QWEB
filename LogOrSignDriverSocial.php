<?php
require "conn.php";



$DriverToken     	  = bin2hex(random_bytes(64));
$SocialID        	  = $_POST["SocialID"];
$AccountType	 	  = $_POST["AccountType"];
$FirebaseDriverToken  = $_POST["FirebaseDriverToken"];



$res = "";


		$res = mysqli_query($con,"SELECT * FROM Drivers WHERE AccountLoginType='$AccountType' AND SocialID='$SocialID'");
		$test=0;
		$result = array();

		while($row = mysqli_fetch_assoc($res)){

			$result[] = $row;
			$test=1;

		}
		
		if($test==0)
		{
			
				$sql="INSERT INTO Drivers (DriverPhone,FirebaseDriverToken,DriverToken,AccountLoginType,SocialID) VALUES ('$SocialID','$FirebaseDriverToken','$DriverToken','$AccountType','$SocialID');";
					
				if(mysqli_query($con,$sql))
				{
					
					
					$res = mysqli_query($con,"SELECT * FROM Drivers WHERE AccountLoginType='$AccountType' AND SocialID='$SocialID'");

					
					
				// 	    $message ="code sent please active account";
				// 		$success = true;
				// 		$status_code = 200;
				// 		$result = [];
						
					$result = array();
					while($row = mysqli_fetch_assoc($res)){

						$result[] = $row;
						$test=4;

					}

					if($test==4 || empty($result)){

						$message ="Done";
						$success = true;
						$status_code = 200;
						echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));

					}
						
					//	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
					
					
				}else{
					echo 'a7a';
				}
			
			
		}else 
		{
			
			
					$res = mysqli_query($con,"SELECT * FROM Drivers WHERE AccountLoginType='$AccountType' AND SocialID='$SocialID'");

					
					
				// 	    $message ="code sent please active account";
				// 		$success = true;
				// 		$status_code = 200;
				// 		$result = [];
						
					$result = array();
					while($row = mysqli_fetch_assoc($res)){

					$result[] = $row;
					$test=4;

					}

					if($test==4 || empty($result)){

					


						$message ="Done";
						$success = true;
						$status_code = 200;
						echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));

					}
					
		}
			
			
		
   
die;
mysqli_close($con);
?>