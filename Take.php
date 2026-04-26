<?php

require "conn.php";
$test= 4;
$OrderID = $_POST["OrderID"];

$updateSql = "UPDATE Orders SET OrderState='Come to take it' WHERE OrderID='$OrderID'";
mysqli_query($con, $updateSql);

$res = mysqli_query($con,"SELECT Users.LANG,Users.UserFirebaseToken FROM Users JOIN Orders ON Users.UserID = Orders.UserID WHERE Orders.OrderID=$OrderID");
			
			$UserFirebaseToken = "";

			$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			
			$LANG = $row["LANG"];
			
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];
			
			
			
					if($LANG == "EN"){
                        $Title = "Ready for Pickup";
                        $messagebody = "Your order is ready. Please come to pick it up.";
                    } else if($LANG == "FR"){
                        $Title = "Prête à être récupérée";
                        $messagebody = "Votre commande est prête. Veuillez venir la récupérer.";
                    } else {
                        $Title = "طلبك جاهز للاستلام";
                        $messagebody = "يرجى الحضور لاستلام طلبك.";
                    }

			
			
				send_notification2($UserFirebaseToken,$Title,$messagebody);
				
				
				
							 /////////////////////////////////////////////
    
           
           $res = mysqli_query($con,"SELECT Orders.*,Drivers.*,Shops.Type FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE Orders.OrderID='$OrderID' ORDER BY OrderID DESC");

            $result = array();
            $i=0;
            while($row = mysqli_fetch_assoc($res)){
            
            $OrderID = $row["OrderID"];
            
             if($row["DestinationName"]=="Deliveryservice"){
                    
                    $row["DestinationName"] = "QOON Express";
                }
            
            //$data = $row[0];
            
            
            $tags = explode(' ',$row["CreatedAtOrders"]);
            
            $date1 =date_create($tags[0]);
            
            $date2=date_create(date("Y-m-d"));
            $diff=date_diff($date2,$date1);
            $ss =  $diff->format("%R%a");
            $row["TimeToCome"] = $row["ReadyTime"] - $ss;
            
            
            $res2 = mysqli_query($con,"SELECT * FROM OrdersCancelledRes JOIN CancelOrderReasons ON OrdersCancelledRes.CancelOrderReasonsID= CancelOrderReasons.CancelOrderReasonsID WHERE OrdersCancelledRes.OrderID='$OrderId'");
            
            $row["CancelledReason"] = "";
            
            while($row2 = mysqli_fetch_assoc($res2)){
            	$row["CancelledReason"] = $row2["Reason"];
            }
            
            
            
            
            $result3 = array();
            $res3 = mysqli_query($con,"SELECT * FROM OrderDetailsOrder Join Foods ON OrderDetailsOrder.FoodID = Foods.FoodID WHERE OrderDetailsOrder.OrderID='$OrderId'");
            while($row3 = mysqli_fetch_assoc($res3)){
            	$result3[] = $row3;
            }
            
            
            
            
            $result[] = $row;
            
            array_splice($result[$i], 1000, 1010, array($result3));
                
            $result[$i]["Food"] = $result[$i]["0"];
            unset($result[$i]["0"]);
            
            
            
            $test=4;
            $i++;
            }
           
           $ORDERObjectLL = $result[0];
           
           
           
           /////////////////////////////////////////////
				
				
				
				newNotfi($UserFirebaseToken,$Title,$messagebody,$accessToken,$ProgID,$ORDERObjectLL);

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





function newNotfi($tokens,$TitleW,$MesBodyW,$accessTokenw,$Pid,$ORDERObject)
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
                    'OrderData' => json_encode($ORDERObject, JSON_UNESCAPED_UNICODE),
                    'Type' => "CHAT"
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