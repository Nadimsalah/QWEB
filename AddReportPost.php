<?php
require "conn.php";


$UserID    		  = $_POST["UserID"];
$Text  		  = $_POST["Text"];
$PostId     	  = $_POST["PostId"];

$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
} 

//	echo $Token;

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if(true){

   $sql="INSERT INTO ReportsPost (UserID,Text,PostId) VALUES ('$UserID','$Text','$PostId');";
   if(mysqli_query($con,$sql))
   {   

	$message ="Posted Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = $last_id; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
   }
   else
   {
	   
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = "0"; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
//	echo json_encode($key);
	
   }
   
	}
	
	else{
		
		
			$message ="Error Token Eror";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}
   
 /*
function send_notification($tokens, $UserName, $PostTitle)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => "Mlbet",
			 'body' => "$UserName Posted a job $PostTitle")
			);

		$headers = array(
			'Authorization:key=AAAAWduYoZs:APA91bGnSEZ0R8QHu4nY3pGBx8C_RxOwm-nNElYw7qwXusmVGGukyrTcUoviQieWYhnJdrxQ1mWkoGoNYyq8HRcYsF79rTaAMExo_Aa-U6nxe-wxeFn_wMByu7PsJiHVfPd1yN6Mh7pL',
			'Content-Type:application/json'
			);

	   $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
	} 
   */
   
   
die;
mysqli_close($con);
?>