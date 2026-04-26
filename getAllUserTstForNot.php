<?php

require "conn.php";
$test=0;


$res = mysqli_query($con,"SELECT * FROM Users");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


$Title2 = "Teesst";
$Body2  = "Teeessssttt";


$UserFirebaseToken2 = $row["UserFirebaseToken"];

send_notification_all22($UserFirebaseToken2,$Title2,$Body2,$accessToken,$ProgID);

$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}



function send_notification_all22($UserFirebaseToken,$Title,$Body,$accessTokenw,$Pid)
	{
		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
				 'title' => $Title, 
				 'body' => $Body)
			);

        $fields = array(
            'message' => array(
                'token' => $UserFirebaseToken,
                'notification' => array(
                    'title' => $Title,
                    'body' => $Body
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
       echo  $result;
       return $result;
	}


die;
mysqli_close($con);
?>