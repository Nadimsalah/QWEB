<?php
require "conn.php";


$OrderID  = $_POST["OrderID"];
$UserID   = $_POST["UserID"];

$messagebody = $_POST["messsage"];


 

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
            
            
            $res2 = mysqli_query($con,"SELECT * FROM OrdersCancelledRes JOIN CancelOrderReasons ON OrdersCancelledRes.CancelOrderReasonsID= CancelOrderReasons.CancelOrderReasonsID WHERE OrdersCancelledRes.OrderID='$OrderID'");
            
            $row["CancelledReason"] = "";
            
            while($row2 = mysqli_fetch_assoc($res2)){
            	$row["CancelledReason"] = $row2["Reason"];
            }
            
            
                $res22 = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
        		while($row22 = mysqli_fetch_assoc($res22)){
        		
        		
            		$UserFirebaseToken = $row22["UserFirebaseToken"];
            		$LANG 			   = $row22["LANG"];
            		
        		
        		}
            
            
            
            $result3 = array();
            $res3 = mysqli_query($con,"SELECT * FROM OrderDetailsOrder Join Foods ON OrderDetailsOrder.FoodID = Foods.FoodID WHERE OrderDetailsOrder.OrderID='$OrderID'");
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
           
           
           newNotfi($UserFirebaseToken,$LANG,$messagebody,$accessToken,$ProgID,$ORDERObjectLL);



	$message ="Done";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$ORDERObjectLL,"message"=>$message));



	
	
	
  function newNotfi($DriverToken,$LANGw,$messagebodyw,$accessTokenw,$Pid,$ORDERObject)
	{


		if($LANGw=="AR"||$LANGw=="ar"){
			$Title =  "QOON Express";
			$body  = $messagebodyw; 
		}else if($LANGw=="EN"||$LANGw=="en"){
			$Title =  "QOON Express";
			$body  = $messagebodyw; 
		}else if($LANGw=="FR"||$LANGw=="fr"){
			$Title =  "QOON Express";
			$body  = $messagebodyw; 
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