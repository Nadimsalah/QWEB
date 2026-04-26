<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];



$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	
	
	
		
	
	if(true){

   $sql="UPDATE Orders SET OrderState='Rated' WHERE OrderID=$OrderID";
   if(mysqli_query($con,$sql))
   {
	   
	  // $sql="UPDATE Drivers SET DriverRate=($Rating+DriverRate)/DriverOrdersNum WHERE DriverID=$DriverID";
		//	   if(mysqli_query($con,$sql))
			//   {
				   
			//   }
	   
	   	
	   
	   
	  
	   
	   
	
	$message ="Finished Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
    $sql="INSERT INTO DriverNotification (OrderID,DriverID,NotificationText) VALUES ('$OrderID','$DriverID','Vous êtes évalué $Rating pour le numéro de commande $OrderID');";
   if(mysqli_query($con,$sql))
   {
       
   }
   
   
   $DriverToken = "";
   
   $res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DriverID");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$DriverToken = $row["FirebaseDriverToken"];
		
		$test=4;
	//	send_notification($DriverToken,$OrderID,$Rating);
	}
    

   }
   else
   {

	$message ="Error Updated";
    $success = false;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

   }
   
	}
	
	else{
		
		
			$message ="Error Token Eror";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}
	
	
	
	function send_notification($DriverToken,$OrderID,$Rating)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
			 'title' => "Vous êtes évalué",
			 'body' => "Vous êtes évalué $Rating pour le numéro de commande $OrderID")
			);

		$headers = array(
			'Authorization:key=AAAAEDOF67k:APA91bFMPNwvWHetPtqc1i--ztKxrPdSd7ZbTXvrm0LWFV6KHlkw5I-9yOdt6ZtBq1PXo3uVEDcJnFmbAKpNH7tTS9wiKLjAaeLzB0J0KMI6xvsZ5z0C-4Kn98VzSLp_fJs-ibpmOJY2',
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
	
	
die;
mysqli_close($con);
?>