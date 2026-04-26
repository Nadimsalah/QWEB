<?php

require "conn.php";
$test=0;

$DriverID = $_POST["DriverID"] ?? $_POST["driverId"] ?? '';
$OrderId = $_POST["OrderId"] ?? $_POST["orderId"] ?? '';
$Price = $_POST["Price"] ?? $_POST["price"] ?? $_POST["Offer"] ?? $_POST["offer"] ?? '';
$OrderType = $_POST["OrderType"] ?? '';

$AppType = $_POST["AppType"] ?? '';

file_put_contents('post_debug.txt', print_r($_POST, true));



foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
} 

//	echo $Token;

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=90;

	}
	
	if(true){

        if($AppType==""){
            $AppType = "QOON";
        }

        if($AppType=="QOON"){


        $res = mysqli_query($con,"SELECT * FROM DriversOffer WHERE DriverID ='$DriverID' AND OrderId='$OrderId'");
        
        $result = array();
        
        
        
        while($row = mysqli_fetch_assoc($res)){
        
        //$data = $row[0];
        $result[] = $row;
        
        $test=4;
        
        }
        /////////////
        //echo json_encode(array("result"=>$result));
        if($test==4 || empty($result)){
        	
            $message ="You ordered before";
            $success = false;
        	$result = [];
            $status_code = 200;
        
        echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
        }
        else{
        	
        	$UserID = "";
        	$res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID ='$OrderId' AND OrderState='waiting'");
        
        	$result = array();
        
        
        
        	while($row = mysqli_fetch_assoc($res)){
        
        	//$data = $row[0];
        	$result[] = $row;
        	
        	$UserID = $row["UserID"];
        
        	$test=9;
        
        	}
        	
        	if($test==9){
        		
        		
        		
        		$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID =$DriverID AND DriverState='Active'");
        
        	$result = array();
        
        
        
        	while($row = mysqli_fetch_assoc($res)){
        
        	//$data = $row[0];
        	
        	$FName = $row["FName"]; 
        	$result[] = $row;
        	
        //	$UserID = $row["UserID"];
        
        	$test=20;
        
        	}
        		
        		
        	if($test==20){	
        		
        		
        		
        	$res = mysqli_query($con,"SELECT * FROM Orders WHERE DelvryId =$DriverID AND OrderState='Doing'");
        
        	$result = array();
        
        
            $nums = 0;
        
        	while($row = mysqli_fetch_assoc($res)){
        
        	//$data = $row[0];
        	$result[] = $row;
        	
        //	$UserID = $row["UserID"];
        
        	$test=30;
        	$nums++;
        
        	}
        		
        	if($nums<7){
        		
        		$sql="INSERT INTO DriversOffer (DriverID,OrderId,Price) VALUES ('$DriverID','$OrderId','$Price');";
           if(mysqli_query($con,$sql))
           {
        	   
        	if($OrderType=="SLOW"){   
        	   $sql="UPDATE Orders SET OrderState='Doing' , DelvryId = '$DriverID', OrderPrice = '$Price' WHERE OrderID=$OrderId";
        	   if(mysqli_query($con,$sql))
        	   {}
        	}
        	   
        	   $UserFirebaseToken = "";
        	   
        	   $res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
        		while($row = mysqli_fetch_assoc($res)){
        		
        		
        		$UserFirebaseToken = $row["UserFirebaseToken"];
        		$LANG 			   = $row["LANG"];
        		
        		$test=4;
        		
            $sql="INSERT INTO UserNotification (OrderID,UserID,NotificationText) VALUES ('$OrderId','$UserID','Vous avez une nouvelle offre en ordre $OrderId');";
           if(mysqli_query($con,$sql))
           {
           }
           
           
           /////////////////////////////////////////////
           
           
           
           
           $res = mysqli_query($con,"SELECT Orders.*,Drivers.*,Shops.Type FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE Orders.OrderID='$OrderId' ORDER BY OrderID DESC");

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
        		
        		send_notification($UserFirebaseToken,$OrderId,$FName,$LANG,$Price);
        		newNotfi($UserFirebaseToken,$OrderId,$FName,$LANG,$Price,$accessToken,$ProgID,$ORDERObjectLL);
        	}
        	   
        	   
        	   $message ="Done";
            $success = true;
            $status_code = 200;
        	$result = []; 
           echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
        
           }
           
        	}else{
        	    
        	    $message ="برجاء انهاء الطلبات السابقة قبل التقديم في طلبات جديدة";
        		$success = false;
        		$status_code = 200;
        		$result = []; 
        		echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
        	    
        	}
        		
        	} else{
        		
        		$message ="تواصل مع الاداره لتفعيل الحساب عبر الواتس اب";
        		$success = false;
        		$status_code = 200;
        		$result = []; 
        		echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
        	}
        		
        	}else{
        		
        		$message ="العرض لم يعد موجود";
        		$success = false;
        		$status_code = 200;
        		$result = []; 
        		echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
        	}
        	
        	
        }

        }else{
            
            
            
            
            $url = "https://zeewana.com/QoonDriverApis/AddOffer.php";

// البيانات التي سيتم إرسالها
            $postData = [
                'DriverID' => $DriverID,
                'OrderId' => $OrderId,
                'Price' => $Price,
                'OrderType' => $OrderType
            ];
            
            // تهيئة جلسة cURL
            $ch = curl_init($url);
            
            // إعدادات cURL
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // تجاهل التحقق من SSL (فقط أثناء التطوير)
            curl_setopt($ch, CURLOPT_POST, true); // تحديد أن الطلب من نوع POST
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); // تمرير البيانات
            
            // تنفيذ الطلب
            $response = curl_exec($ch);
            
            // التحقق من وجود أخطاء
            if (curl_errno($ch)) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode([
                    "status_code" => 500,
                    "success" => false,
                    "message" => "cURL Error: " . curl_error($ch)
                ]);
                curl_close($ch);
                exit;
            }
            
            // إغلاق الجلسة
            curl_close($ch);

            // ZEEWANA BUG FIX: Zeewana's API does not save the driver's 'Price' to the database,
            // which breaks the web UI polling. We must intercept the request right after it finishes
            // and forcefully save the Price into the `DriversOffer` table ourselves!
            if (!empty($Price)) {
                // Update the most recently generated offer row (which Zeewana just inserted!)
                $res = $con->query("UPDATE DriversOffer SET Price = '$Price' WHERE DriverID = '$DriverID' AND OrderId = '$OrderId' ORDER BY DriversOfferID DESC LIMIT 1");
            }
            
            // تحديد نوع المخرجات JSON
            header('Content-Type: application/json');
            
            // طباعة الاستجابة كما هي
            echo $response;
            
            
            
            
            
        }


    }
	
	else{
		
		
			$message ="Error Token Eror";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}



function send_notification($DriverToken,$OrderID,$FNamew,$LANGw,$Moneyw)
	{
		if($LANGw=="AR"||$LANGw=="ar"){
			$Title = $FNamew . " في طريقه الي استلام";
			$body  = $FNamew . " قد قدم عرًضا لتوصيل طلبك بقيمة " . $Moneyw . "درهم "; 
		}else if($LANGw=="EN"||$LANGw=="en"){
			$Title = $FNamew . " is on their way to pick up your order";
			$body  = $FNamew . " has offered to deliver your order for " . $Moneyw . "MAD"; 
		}else if($LANGw=="FR"||$LANGw=="fr"){
			$Title = $FNamew . " est en route pour récupérer votre commande";
			$body  = $FNamew . " a proposé de livrer votre commande pour " . $Moneyw . "MAD";  
		}
		
		
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
			 'title' => "$Title",
			 'body' => "$body",
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
	
	
		function newNotfi($DriverToken,$OrderID,$FNamew,$LANGw,$Moneyw,$accessTokenw,$Pid,$ORDERObject)
	{


        $Moneyw = number_format((float)$Moneyw, 2, '.', '');


		if($LANGw=="AR"||$LANGw=="ar"){
			$Title =  "QOON Express";
			$body  = $FNamew . " قدّم عرضًا بقيمة " . $Moneyw . "درهم "; 
		}else if($LANGw=="EN"||$LANGw=="en"){
			$Title =  "QOON Express";
			$body  = $FNamew . " has submitted an offer: " . $Moneyw . " MAD"; 
		}else if($LANGw=="FR"||$LANGw=="fr"){
			$Title =  "QOON Express";
			$body  = $FNamew . " a soumis une offre " . $Moneyw . " MAD";  
		}


		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
				 'title' => $Title, 
				 'body' => $body)
			);

        $fields = array(
            'message' => array(
                'token' => $DriverToken,
                'notification' => array(
                    'title' => $Title,
                    'body' => $body
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