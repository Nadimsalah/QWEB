<?php

require "conn.php";

$OrderID = $_POST["OrderID"];
	   	   
	   $res = mysqli_query($con,"SELECT Users.LANG,Users.UserFirebaseToken FROM Users JOIN Orders ON Users.UserID = Orders.UserID WHERE Orders.OrderID=$OrderID");
			
			$UserFirebaseToken = "";

			$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			
			$LANG = $row["LANG"];
			
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];
			
			
			
					if($LANG=="EN"){
						
						$Title = "Approval Granted";
						$messagebody = "Your request has been approved.";
												
					}else if($LANG=="FR"){
						$Title = "Approval Granted";
						$messagebody = "Your request has been approved.";
					}else{
						$Title = "تمت الموافقة ";
						$messagebody = "تمت الموافقة علي طلبك";
					}
			
			
				send_notification2($UserFirebaseToken,$Title,$messagebody);

			}
	   
	   
	   
   
   
   

//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message =" Successfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
else{
    
    http_response_code(400);

// Get the new response code
//var_dump(http_response_code());
    
	$message ="Error";
    $success = false;
    $status_code = 400;
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}





	function send_notification2($UserFirebaseToken,$FNamew,$Message)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => $FNamew,
			 'body' => $Message)
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