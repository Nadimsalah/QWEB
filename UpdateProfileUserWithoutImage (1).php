<?php
require "conn.php";


$Phone     = $_POST["UserPhone"];
$AccountType = $_POST["AccountType"];
$FaceID = $_POST["FaceID"];
$GoogleID = $_POST["GoogleID"];

$FullName = $_POST["FullName"];
$Email = $_POST["Email"];
$BirthOfDate = $_POST["BirthOfDate"];
$Gender = $_POST["Gender"];



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
		
		
					if($AccountType=="Phone"){
						$sql22="UPDATE Users SET name='$FullName',Email='$Email',Email='$Email',BirthDate='$BirthOfDate',Gender='$Gender',AccountState='Old' WHERE PhoneNumber='$Phone'";
					}else if($AccountType=="FaceBook"){
						$sql22="UPDATE Users SET name='$FullName',Email='$Email',Email='$Email',BirthDate='$BirthOfDate',Gender='$Gender',PhoneNumber='$Phone',AccountState='Old' WHERE FaceID='$FaceID'";
					}else if($AccountType=="Google"){
						$sql22="UPDATE Users SET name='$FullName',Email='$Email',Email='$Email',BirthDate='$BirthOfDate',Gender='$Gender',PhoneNumber='$Phone',AccountState='Old' WHERE GoogleID='$GoogleID'";
					}
		
		

	if(mysqli_query($con,$sql22))
	{
		
		
					if($AccountType=="Phone"){
					$res = mysqli_query($con,"SELECT * FROM Users WHERE PhoneNumber='$Phone'");
					
					
					}else if($AccountType=="FaceBook"){
					$res = mysqli_query($con,"SELECT * FROM Users WHERE FaceID='$FaceID'");
					
					}else if($AccountType=="Google"){
					$res = mysqli_query($con,"SELECT * FROM Users WHERE GoogleID='$GoogleID'");
					
				
					}

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