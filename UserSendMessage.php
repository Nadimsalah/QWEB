<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];
$DriverID = $_POST["DriverID"];
$message  = $_POST["message"];


$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if($test==4 || empty($result)){




        $DriverID = "";
	   
	   $res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$OrderID ORDER BY OrderID DESC");

		$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$DriverID = $row["DelvryId"];
			$UserID = $row["UserID"];

			}
  
  	$res = mysqli_query($con,"SELECT name FROM Users WHERE UserID=$UserID");
		while($row = mysqli_fetch_assoc($res)){
			
			$name = $row["name"];
		}
			
			
	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DriverID");
		while($row = mysqli_fetch_assoc($res)){
		
		
		$DriverToken = $row["FirebaseDriverToken"];
		
		
		$test=4;
		

		
		//send_notification($DriverToken,$OrderID,$message,$name);
		newNotfi($DriverToken,$OrderID,$message,$name,$accessToken,$ProgID);
		
			$message ="sent";
			$success = true;
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
	
	
	function send_notification($DriverToken,$OrderID,$message,$Namew)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $DriverToken,
               'priority' => 'high',
              "sound"=> 'newtrip.mp3',
			  'notification'=>array(
			 'title' => $Namew,
			 'body' => $message),
          	  'data'=>array(
			 'title' => "Message",
			 'body' => $message,
             "link"=> "https://whereappco.com/Manager",
			 "color"=>'lool',
             "sound"=> 'newtrip', 
             "channel_id"=>"JiblerOrder",
              "android_channel_id"=> "JiblerOrder",
               'content-available'  =>  true,
			 'data'=>$state)	
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
	
	function newNotfi($tokens,$OrderID,$message,$Namew,$accessTokenw,$Pid)
	{

		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
				 'title' => $Namew, 
				 'body' => $message)
			);

        $fields = array(
            'message' => array(
                'token' => $tokens,
                'notification' => array(
                    'title' => $Namew,
                    'body' => $message
                )
            )
        );

		$headers = array(         
			'Authorization:Bearer '.$accessTokenw,
			'Content-Type:application/json'
			);

	   $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    //   curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: fcm.googleapis.com'));
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