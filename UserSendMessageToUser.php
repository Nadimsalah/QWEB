<?php
require "conn.php";


$SenderID   = $_POST["SenderID"];
$UserID   = $_POST["UserID"];

$messagebody = $_POST["messsage"]; 



$found = "NO";
			$res = mysqli_query($con,"SELECT addnum FROM `UserContacts` WHERE UserID = $SenderID AND ContactsID = $UserID");
	while($row = mysqli_fetch_assoc($res)){ $addnum = $row["addnum"]; $found = "YES"; }
     
	 $addnum = $addnum + 10;
		
		if($found=="NO"){
			$sql="INSERT INTO UserContacts (UserID,ContactsID) VALUES ('$UserID','$SenderID');";
				   if(mysqli_query($con,$sql))
				   {}
		}else{
			$sql="UPDATE UserContacts SET addnum = '$addnum' WHERE UserID = $SenderID AND ContactsID = $UserID";
				   if(mysqli_query($con,$sql))
				   {}
		}	   
			   

        
  
			
			
			$res = mysqli_query($con,"SELECT UserFirebaseToken,LANG FROM Users WHERE UserID=$UserID");
			
			$UserFirebaseToken = "";

			$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			
			$LANG = $row["LANG"];
			
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];
			

			}
			
			$res = mysqli_query($con,"SELECT name,PhoneNumber,UserPhoto FROM Users WHERE UserID=$SenderID");
			
			

			$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$Uname             = $row["name"];
			$UPhoneNumber       = $row["PhoneNumber"];
			$UUserPhoto       = $row["UserPhoto"];

			}
	   
	   
	   
	  
	
	
	
	        if($messagebody!=""){
				
				if($messagebody=="Audio"){
					
					if($LANG=="FR"){
						
						$messagebody = "Message vocal";
						
					}else if($LANG=="EN"){
						$messagebody = "Voice Message";
					}else{
						$messagebody = " رسالة صوتية";
					}
					
				}else {
					

					
				}
				
				    if (strpos($messagebody, 'Dh') === false) {
    					send_notification2($UserFirebaseToken,$FName,$messagebody);
    					newNotfi($UserFirebaseToken,$FName,$messagebody,$accessToken,$ProgID,$UPhoneNumber,$Uname,$UUserPhoto,$SenderID);
				    }
			}else{
					send_notification2($UserFirebaseToken,$FName,$messagebody);
					newNotfi($UserFirebaseToken,$FName,$messagebody,$accessToken,$ProgID,$UPhoneNumber,$Uname,$UUserPhoto,$SenderID);
            }
	   
	   
	   $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
	   
    

   
   
	
	
	
	
	
	
		function send_notification2($UserFirebaseToken,$FNamew,$Message)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => $FNamew,
			 'body' => $Message,
			  "link"=> "http://sae-marketing.com",
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
	
	
	function newNotfi($tokens,$TitleW,$MesBodyW,$accessTokenw,$Pid,$phoneNumber,$name,$userPhoto,$userID)
	{

		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
				 'title' => $TitleW, 
				 'body' => $MesBodyW)
			);

        $fields = array(
            'message' => array(
                'token' => $tokens,
                'notification' => array(
                    'title' => $TitleW,
                    'body' => $MesBodyW
                ),
                'data' => array(
                    'phoneNumber' => $phoneNumber,
                    'name' => $name,
                    'userPhoto' => $userPhoto,
                    'userID' => $userID,
                    'Type' => "MESSAGE"
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