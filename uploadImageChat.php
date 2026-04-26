<?php
require "conn.php";




$photochat     = $_POST["photochat"];
$photo1name=rand(1,700000).rand(1,700000);




$paths1 = "$photo1name.png";
$path1 =  "photo/$paths1";
$actualpath1 = $DomainNamee."$path1";

   if(true)
   {
	      
	file_put_contents($path1,base64_decode($photochat));		
	$message ="Updated Sucssessfuly";
    $success = true;
    $status_code = 200;
	//$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$actualpath1,"message"=>$message));

   }
   
   
	
	
	
die;
mysqli_close($con);
?>