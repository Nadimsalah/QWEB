<?php
require "conn.php";



$DriverId  		   = $_POST["DriverId"];
$Fname     		   = $_POST["Fname"];
$LName    		   = $_POST["LName"];
$DriverEmail       = $_POST["DriverEmail"];
$DriverPhone       = $_POST["DriverPhone"];
$PersonalPhoto     = $_POST["PersonalPhoto"];
$NationalIDPhoto   = $_POST["NationalIDPhoto"];
$NationalID        = $_POST["NationalID"];
$CountryID         = $_POST["CountryID"];
$DriverPhone       = $_POST["DriverPhone"];
$SocialID        	  = $_POST["SocialID"];
$AccountType	 	  = $_POST["AccountType"];
$photo1name=rand(1,700000).rand(1,700000);
$photo2name=rand(1,700000).rand(1,700000);


$CarPhoto    = $_POST["CarPhoto"];
$licensePhoto   = $_POST["licensePhoto"];


$paths1 = "$photo1name.png";
$path1 =  "photo/$paths1";
$actualpath1 = $DomainNamee."$path1";

$paths2 = "$photo2name.png";
$path2 =  "photo/$paths2";
$actualpath2 = $DomainNamee."$path2";

$paths3 = "$photo3name.png";
$path3 =  "photo/$paths3";
$actualpath3 = $DomainNamee."$path3";

$paths4 = "$photo4name.png";
$path4 =  "photo/$paths4";
$actualpath4 = $DomainNamee."$path4";


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
	
	if(true){

   $sql="UPDATE Drivers SET DriverEmail='$DriverEmail',Fname='$Fname',LName='$LName',PersonalPhoto='$actualpath1',NationalIDPhoto='$actualpath2',CarPhoto='$actualpath3',licensePhoto='$actualpath4',NationalID='$NationalID',CountryID='$CountryID',DriverPhone='$DriverPhone' WHERE AccountLoginType='$AccountType' AND SocialID='$SocialID'";
   if(mysqli_query($con,$sql))
   {
	   
	   $sql="UPDATE Drivers SET AccounntType='OLD' WHERE AccountLoginType='$AccountType' AND SocialID='$SocialID'";
	   if(mysqli_query($con,$sql))
	   {
	   
	   }
	   
	file_put_contents($path1,base64_decode($PersonalPhoto));		
	file_put_contents($path2,base64_decode($NationalIDPhoto));	
	
	file_put_contents($path3,base64_decode($CarPhoto));		
	file_put_contents($path4,base64_decode($licensePhoto));

	$message ="Updated Sucssessfuly";
    $success = true;
    $status_code = 200;
	//$result = []; 
	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverPhone='$DriverPhone'");

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