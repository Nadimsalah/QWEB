<?php
require "conn.php";


$UserID    		  = $_POST["UserID"];
$ShopID  		  = $_POST["ShopID"];
$PostID     	  = $_POST["PostID"];

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

   $sql="INSERT INTO Likes (UserID,ShopID,PostID) VALUES ('$UserID','$ShopID','$PostID');";
   if(mysqli_query($con,$sql))
   {



	 $sql="INSERT INTO ShopNotification (ShopID,OrderID,NotificationText) VALUES ('$ShopID','0','A new like to your post');";
   if(mysqli_query($con,$sql))
   {}
    

	$last_id = mysqli_insert_id($con);
	//echo $last_id;
	   
	$key['result'] = "done";
//	echo json_encode($key);	


if (strpos($PostID, 'r') !== false) {
    
}else{
$sql="Update Posts set PostLikes = PostLikes+1 where PostId = $PostID";
   if(mysqli_query($con,$sql))
   {}
}

	$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID = $ShopID");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 //$ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 
				 
				 ResturantNotification($ShopFirebaseToken);
				 newNotfi($ShopFirebaseToken,$accessToken,$ProgID);

            }



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
   
function ResturantNotification($tokens)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => "Like",
			 'body' => "A new like to your post ")
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
	
	
	function newNotfi($tokens,$accessTokenw,$Pid)
	{

		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
				 'title' => "Like", 
				 'body' => "A new like to your post ")
			);

        $fields = array(
            'message' => array(
                'token' => $tokens,
                'notification' => array(
                    'title' => "Like",
                    'body' => "A new like to your post "
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