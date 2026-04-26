<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];
$Rating   = $_POST["Rating"];
$Review   = $_POST["Review"];


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

   $sql="UPDATE Orders SET UserRated=$Rating,UserReview='$Review' WHERE OrderID=$OrderID";
   if(mysqli_query($con,$sql))
   {
	   
	   
	   $UserID = "";
	   
	   $res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$OrderID ORDER BY OrderID DESC");

		$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$UserID = $row["UserID"];

			}
			
			
			$res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
			
			$UserFirebaseToken = "";

		$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];

			}
	   
	   
	   
	  
	
	$message ="Finished Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
	
	 $sql="UPDATE Users SET userRate=($Rating+usertotalnumofrates)/UserOrdersNum , UserTotalRates=UserTotalRates+1 , UserTotalReview=UserTotalReview+1 WHERE UserID=$UserID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	    $sql="UPDATE Users SET usertotalnumofrates=usertotalnumofrates+$Rating WHERE UserID=$UserID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	   
	   
	   
    
 $sql="INSERT INTO UserNotification (OrderID,UserID,NotificationText) VALUES ('$OrderID','$UserID','Votre a été évalué $Rating for order number $OrderID');";
   if(mysqli_query($con,$sql))
   {
     //  send_notification($UserFirebaseToken,$OrderID,$Rating);
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
	
	
	function send_notification($UserFirebaseToken,$OrderID,$Rating)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => "Votre a été évalué",
			 'body' => "Votre a été évalué $Rating",
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