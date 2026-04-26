<?php
require "conn.php";


$DriverID  = $_GET["DriverID"];
$DriverID  = $_POST["Driver_id"];


$Token = "s";


$json = file_get_contents('php://input');
// Converts it into a PHP object 
$data = json_decode($json, true);



$DriverID  = $data['Driver_id'];
$UserID  = $data['UserID'];
$Money   =  $data['Money'];
//echo $data['Driver_id'];

$ShopID             = $data['ShopID'];
$Money              =  $data['Money'];
$ShopTransactionID  =  $data['ShopTransactionID'];



   if($DriverID!=""){
   
   
   $sql="UPDATE Orders SET PaidForDriver='Paid',DriverOmolaPaid='YES' WHERE DelvryId=$DriverID";
   if(mysqli_query($con,$sql))
   {
	   $SubscriptionNotPaidtCount = 0;
	   
	   $ids = "";
	   
	   	$res = mysqli_query($con,"SELECT * FROM SubscriptionDriver WHERE DriverID='$DriverID' AND Paid = 'NO'");
			while($row = mysqli_fetch_assoc($res)){
				
				$SubscriptionNotPaidtCount++;
				
				$SubscriptionDriverID = $row["SubscriptionDriverID"];
				
				  
			}

			if($SubscriptionNotPaidtCount>1){
				
				$paidSum = $SubscriptionNotPaidtCount-1;
		   $res = mysqli_query($con,"SELECT * FROM SubscriptionDriver WHERE DriverID='$DriverID' AND Paid = 'NO' limit $paidSum");
			while($row = mysqli_fetch_assoc($res)){
				
				
				
				$SubscriptionDriverID = $row["SubscriptionDriverID"];
				
				$ids = $ids. $SubscriptionDriverID . ','; 
				
				  
			}
				$ids = substr($ids, 0, -1);
				
				$sql="update SubscriptionDriver set Paid = 'YES' WHERE SubscriptionDriverID IN ($ids)";
				   if(mysqli_query($con,$sql))
				   {}
				
			}
	   
	   
	   
	   		   $res = mysqli_query($con,"SELECT FirebaseDriverToken,LANG FROM Drivers WHERE DriverID='$DriverID'");
			while($row = mysqli_fetch_assoc($res)){
				
				$FirebaseDriverToken = $row["FirebaseDriverToken"];
				$LANG = $row["LANG"];
				
				if($LANG=="EN"){
					
					$Title = "Payment Completed Successfully ✅";
					$MesB  = "Your account has been reactivated after paying the indebtedness.";
					
				}else if($LANG=="FR"){
					
					$Title = "Paiement réussi ✅";
					$MesB  = "Votre compte a été réactivé après paiement de la dette.";
					
				}else{
					
					$Title = "تم الدفع بنجاح ✅";
					$MesB  = "تم إعادة تفعيل حسابك بعد دفع المديونية";
				}
				
				DriverNotification($FirebaseDriverToken,$Title,$MesB);
				
			}
	   
	   
	   
   
    $message ="OK";
    $success = true;
    $status_code = 200;
	$result = $last_id; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));


/*
$res = mysqli_query($con,"SELECT * FROM `DriverTransactions` WHERE DriverID='$DriverID' ORDER BY DriverTransactionsID DESC limit 1");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){


$TransToken = $row["TransToken"];
$DriverTransactionsID = $row["DriverTransactionsID"];

break;
}



   echo "1;".$TransToken.";".$DriverTransactionsID.";OK";
   
   
   }else{


$res = mysqli_query($con,"SELECT * FROM `DriverTransactions` WHERE DriverID='$DriverID' ORDER BY DriverTransactionsID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){


$TransToken = $row["TransToken"];
$DriverTransactionsID = $row["DriverTransactionsID"];

break;
}

       
       
       echo "3;".$TransToken.";".$DriverTransactionsID.";OK";
	   
	   */
   }
   
   }else{
	   
	   
	if($UserID!=""){
		
		 $sql="UPDATE Users SET Balance=Balance+$Money WHERE UserID=$UserID";
		   if(mysqli_query($con,$sql))
		   {
			   
			   
			   
			   
			   
			   
			   $message ="OK";
				$success = true;
				$status_code = 200;
				$result = $last_id; 
				echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
			   
			   
			   $res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
			
			$UserFirebaseToken = "";

			$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			
			$LANG = $row["LANG"];
			
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];
			$name = $row["name"];
			$Balance = $row["Balance"];
			$Gender = $row["Gender"];

			}
			   if($LANG=="EN"){
						
						$Title = "Wallet Balance Charged!";
						$messagebody = "Dear " .$name . " , your wallet balance has been successfully
charged. You now have " . $Balance . " MAD credited to your account. Enjoy
seamless transactions and make the most of our services.";
						
					}else if($LANG=="FR"){
						$Title = "Solde du portefeuille rechargé !";
						
						if($Gender == "Male"){
							$Cher = "Chère";
							
						}else{
							$Cher = "Cher";
						}
						
						$messagebody = $Cher . " " .$name .", votre solde de portefeuille a été
rechargé avec succès. Vous disposez désormais de" . $Balance . "MAD crédité
sur votre compte. Profitez de transactions sans effort et tirez le meilleur
parti de nos services";
					}else{
						$Title = "تم شحن رصيد المحفظة";
						
						if($Gender == "Male"){
							$Cher = "عزيزي";
						}else{
							$Cher = "عزيزتي";
						}
						
						$messagebody =  $Cher . "  "  .$name . " تم شحن رصيد محفظتك بنجاح. لديك " . $Balance . " درهم " . "  ُمعتمد على حسابك. استمتع بعمليات مريحة واستفد من خدماتنا";
					}
			   
			   
			   UserNotification($UserFirebaseToken,$Title,$messagebody);
		   }
		   
		   
	}else{
		
		if($ShopID!=""){
			
			$sql="UPDATE Shops SET Balance=Balance+$Money WHERE ShopID=$ShopID";
		   if(mysqli_query($con,$sql))
		   {
			   
			   $res = mysqli_query($con,"SELECT Balance,Status,PaySub FROM Shops WHERE Shops.ShopID = '$ShopID'");

				$result = array();

				while($row = mysqli_fetch_assoc($res)){
					
					$Balance = $row["Balance"];
					$Status  = $row["Status"];
					$PaySub  = $row["PaySub"];		
				}
				
				if($Balance >= 0){
					if($Status=="NO"||$Status==""){
						if($PaySub=="NO"){
							$sql="UPDATE Shops SET Status = 'ACTIVE',PaySub='YES' WHERE ShopID=$ShopID";
							   if(mysqli_query($con,$sql))
							   {
								   
								   $sql="UPDATE ShopLastTransaction SET Method ='Done' WHERE ShopID= $ShopID AND TransactionPhoto = 'Subscriptions'";
										   if(mysqli_query($con,$sql))
										   {
											   
										   }
								   
								   
							   }
						}
					}
					
				}
			   
			 
				$sql="UPDATE ShopLastTransaction SET Method ='Done' WHERE ShopTransactionID=$ShopTransactionID";
						   if(mysqli_query($con,$sql))
						   {
							   
						   }
					

				$res = mysqli_query($con,"SELECT ShopFirebaseToken,LANG FROM Shops WHERE ShopID = $ShopID");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 //$ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 
				 $ShopLang = $row["LANG"]; 

            }	
						   
						   
				if($ShopLang=="AR"||$ShopLang=="ar"){
				   
				   $ShopTitle = "تم شحن الرصيد بنجاح ✅ ";
				   $ShopBody  = "تم شحن حسابك بنجاح بمبلغ ". $Money ." درهم.";
				   
			   }else if($ShopLang=="EN"||$ShopLang=="en"){
				   
				   $ShopTitle = "Balance Charged Successfully✅";
				   $ShopBody  = "Your account has been successfully charged  ". $Money ." MAD.";
				   
			   }else{
				   
				   $ShopTitle = "Solde rechargé avec succès ✅";
				   $ShopBody  = "Votre compte a été rechargé de ". $Money ." MAD avec succès";
				   
			   }
       
				ResturantNotification($ShopFirebaseToken,$ShopTitle,$ShopBody);		   

			 
			   
			   $message ="OK";
				$success = true;
				$status_code = 200;
				$result = $last_id; 
				echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
			   
			   
		   }
			
		}else{
	   
			$message ="You must enter the driver id NOK";
			$success = false;
			$status_code = 200;
			echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
		}
	}
	   
   }
   
   
   
   
   
   function ResturantNotification($tokens,$ShopTitlew,$ShopBodyw)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $ShopTitlew,
			 'body' => $ShopBodyw)
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
	
	
	
	
	function UserNotification($tokens,$ShopTitlew,$ShopBodyw)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $ShopTitlew,
			 'body' => $ShopBodyw)
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
	
	
	
	function DriverNotification($tokens,$ShopTitlew,$ShopBodyw)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $ShopTitlew,
			 'body' => $ShopBodyw)
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