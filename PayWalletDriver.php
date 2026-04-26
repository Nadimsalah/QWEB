<?php

require "conn.php";
//$test=0;






//send_notification();
//die;

	




$DriverID = $_POST["DriverID"];
$Money    = $_POST["Money"];

$ppasscode = 'JIB6C944CAE346'.$Money;

    $HMAC = hash('sha256', (get_magic_quotes_gpc() ? stripslashes($ppasscode) : $ppasscode));



//$DriverToken     = bin2hex(random_bytes(8));



$res = mysqli_query($con,"SELECT * FROM DriverTransactions order by DriverTransactionsID desc");
$DriverTransactionsID = 0;
while($row = mysqli_fetch_assoc($res)){
	$DriverTransactionsID = $row["DriverTransactionsID"];
	break;
}

$ids = $DriverTransactionsID + 1000;



$SS =  CashPlus($DriverID,$Money,$ids,$HMAC);

//echo $SS;
$arr = json_decode($SS, true);
$ww = $arr["TOKEN"];



//$Money = $Money * 100;
//	$result =  send_notification($DriverID,$Money);

  $sql="INSERT INTO DriverTransactions (DriverID,Address,SubAddress,Money,TransToken) 
  VALUES ('$DriverID','cash_plus','jibler_payment','$Money','$ww');";
  if(mysqli_query($con,$sql))
  {
      
      
//   $sql="UPDATE Orders SET PaidForDriver='Paid' WHERE DelvryId=$DriverID";
//   if(mysqli_query($con,$sql))
//   {}
   
      
         
	$DriverToken     = bin2hex(random_bytes(8));	 
		 
    $message ="Added sucssesfully";
    $success = true;
    $status_code = 200;
//	$result = $DriverToken;
	
	
	
	

	 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$ww,"message"=>$message));
		
  }
  else
  {
	   
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = "added";
	
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

	
  
   
}



function CashPlus($DriverID,$Money,$ID,$HMACe)
	{
		
		$driID = '{"Driver_id":"'.$DriverID.'"}';
		
		//echo $driID;
		
		
		
		$url = 'https://moneyservicedev.cashplus.ma:4434/cpws/cpmarchand/index.cfm?endpoint=/generate_token';
		$fields =array(
			  "request_id" =>$ID,
                "amount"  => $Money,
				"date_expiration" => "",
                "Fees"  => 0,
                "MARCHAND_CODE"  => "JIB",
                "HMAC"  => $HMACe,
				"Expiration_date" => "2021- 12-12 11: 00: 00",
				 "json_data"=> $driID
						    
						    
			);

		$headers = array(
			'Authorization:key=AAAAEDOF67k:APA91bFMPNwvWHetPtqc1i--ztKxrPdSd7ZbTXvrm0LWFV6KHlkw5I-9yOdt6ZtBq1PXo3uVEDcJnFmbAKpNH7tTS9wiKLjAaeLzB0J0KMI6xvsZ5z0C-4Kn98VzSLp_fJs-ibpmOJY2',
			'Content-Type:application/json',
			'Merchant_code:JIB'
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
       
   //    echo $result;
       
       curl_close($ch);
       
       $pizza  = $result;
        $pieces = explode(",", $pizza);
      //  echo $pieces[0]; // piece1
//        echo $pieces[1]; // piece2


       
       
       return $pizza;
	} 




function send_notification($DriverID,$Money)
	{
		$url = 'https://oper-token-api-preprod.m2t.ma/api/v2/token';
		$fields =array(
			  "batchId" =>"4a51b2fb-9cc9-48a2-9952-bb3cf362dca9",
                "requestDate"  => "20220804111010",
                "organismId"  => "0108",
                "serviceId"  => "0111",
                "redirectUrl"  => "",
                "customerId"  => $DriverID,
                "orderId"  => "e9b91a89-59b4-40a3-954a-55d2f8d69f32",
                "orderAmount"  => $Money,
                "description"  => "description",
                "expirationDate"  => "20231022093640",
                "invoiceDate"  => "20230622093640",
                "invoiceDueDate"  => "20230622093640",
                "customerName"  => "customerName",
                "callBackUrl"  => "https://jibler.ma/jibler/UserDriverApi/confirmPayDriver.php?DriverID=$DriverID",
                "customerMail"  => "customer@Mail.com",
                "customerPhone"  => "customerPhone",
                "checkSum"  => "f5197c76fb50124cbbf5fd05596ff24b",
                "tokenStatus"  => "ACTIVATE",
                "token"  => ""
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
       
   //    echo $result;
       
       curl_close($ch);
       
       $pizza  = $result;
        $pieces = explode(",", $pizza);
      //  echo $pieces[0]; // piece1
//        echo $pieces[1]; // piece2


       $pieces[0] =  str_replace("{","",$pieces[0]);
       $pieces[0] =  str_replace(":","",$pieces[0]);
       $pieces[0] =  str_replace('"', '', $pieces[0]);
       $pieces[0] =  str_replace('token', '', $pieces[0]);
       
       return $pieces[0];
	} 


die;
mysqli_close($con);
?>