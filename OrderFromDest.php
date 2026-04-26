<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];
$DriverID  = $_POST["DriverID"];


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

   $sql="UPDATE Orders SET OrderState='Doing' WHERE OrderID=$OrderID";
   if(mysqli_query($con,$sql))
   {
	   
	      
	    
	   
	
	$message ="Finished Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
    $res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$OrderID");

    $UserID = 0;
    
        $result = array();

            while($row = mysqli_fetch_assoc($res)){
            //$data = $row[0];
            $result[] = $row;
            $UserID =  $row["UserID"];

    }
    
    $sql="INSERT INTO UserNotification (OrderID,UserID,NotificationText) VALUES ('$OrderID','$UserID','La demande a été reçue de la source commande $OrderID');";
   if(mysqli_query($con,$sql))
   {
       
       $res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
			
			$UserFirebaseToken = "";

		$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];

			}
       
       
       send_notification($UserFirebaseToken,$OrderID);
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
	
	function send_notification($UserFirebaseToken,$OrderID)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => "a été livré",
			 'body' => "La demande a été reçue de la source commande $OrderID",
			 "link"=> "http://sae-marketing.com/$OrderID",
			 "color"=>'$OrderID',
			 'data'=>'$OrderID')
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