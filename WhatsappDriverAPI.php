<?php


  require "conn.php";
  

  $PhoneNumber = $_POST["PhoneNumber"];
  
  $UserName= "";
  

  
  
  $digits = 4;
  $randNum =  rand(pow(10, $digits-1), pow(10, $digits)-1);


//  $messagew = ' Salut *'. $UserName .'* 👋 Votre code de verification Jibler est: ' . $randNum .' '. ✅;
  
  $messagew = 'Salaam *' . $UserName .'* 👋 hada  code de verification dyal Jibler: ' . $randNum .' ✅
🙈 Mat partagih mea tawahed 🔐';

  $messagew = $randNum.' code de confirmation Pour activer votre compte.';	

  
  $send_tow = $PhoneNumber;  
  

		
		 send($send_tow,$messagew);
		
		
		$message ="Sent sucssesfully";
		$success = true;
		$status_code = 200;
		//$result = []; 
		echo json_encode(array('status_code' => $status_code,'success' => $success ,'data' => $randNum,"message"=>$message));
		
	


  

  function send($send_to, $message){
    
    $data = array('to_number' => $send_to, 'type' => 'text', 'message'=> $message);

    $url = "https://api.maytapi.com/api/7a8bc06b-a25a-4d60-84b5-eec2903d92ce/28858/sendMessage";
	
	$headers = array(
			'x-maytapi-key:366d2ac5-9387-4b5d-9dd0-15e6e6127b30',
			'Content-Type:application/json'
			);
	
	
    $ch = curl_init( $url );
    curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt( $ch, CURLOPT_HEADER, 0);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec( $ch );
    return $response;
  }


?>
