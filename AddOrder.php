<?php
require "conn.php";
require "pdf/pdfprint/fpdf.php";


$UserName    =  $_POST["UserName"];
$UserPhone   =  $_POST["UserPhone"];
$UserEmail   = $_POST["UserEmail"];
$UserAddress =$_POST["UserAddress"]; 
$CarTypeID = $_POST["CarTypeID"];
$WeightsId = $_POST["WeightsId"]; 
$UserCitiesID= $_POST["UserCitiesID"];
$DestnationCitiesID= $_POST["DestnationCitiesID"];
$UserCountryId= $_POST["UserCountryId"];
$DestnationCountryId= $_POST["DestnationCountryId"];


$UserID     = $_POST["UserID"];
$DestinationName  = $_POST["DestinationName"];
$DestnationLat      = $_POST["DestnationLat"];
$DestnationLongt   = $_POST["DestnationLongt"];
$DestnationPhoto  = $_POST["DestnationPhoto"];
$DestnationAddress = $_POST["DestnationAddress"];
$OrderDetails    = $_POST["OrderDetails"];


$UserLat     = $_POST["UserLat"];
$UserLongt     = $_POST["UserLongt"];

$RealType    = $_POST["RealType"];

if($RealType==""){
    $RealType = 'QOON';
}



$res = mysqli_query($con,"SELECT name,PhoneNumber,Email FROM Users WHERE UserID=$UserID");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$UserrrrName            =  $row["name"];
		$UserrrPhoneNumber      =  $row["PhoneNumber"];
		$Email      =  $row["Email"];

	}
	

	$res44 = mysqli_query($con,"SELECT DeliveryZoneID FROM DeliveryZone");
	while($row44 = mysqli_fetch_assoc($res44)){
		$DeliveryZoneID = $row44["DeliveryZoneID"];
	
		$res2 = mysqli_query($con,"SELECT * FROM CityBoders WHERE DeliveryZoneID = $DeliveryZoneID");

		$result2 = array();
		$i = 0;
		$Found = "NO";

		while($row2 = mysqli_fetch_assoc($res2)){
			
			array_push($result2, new Point($row2["CityLat"], $row2["CityLongt"]));
			
			
		}

		$position = new Point($UserLat, $UserLongt);

		if (isInsidePolygon($result2, $position)) {
			$Found = "YES";
			$test=4;
			
			$DestnationCitiesID  =  $DeliveryZoneID;
			
			break;
		} else {
			$Found = "NO";
		}
		
	}



$OrderDelvTime = $_POST["OrderDelvTime"];
$OrderPriceFromShop    = $_POST["OrderPriceFromShop"];
$ShopID        = $_POST["ShopID"]; 
$Token = "s";
$Method = $_POST["Method"]; 

$OrderType = $_POST["OrderType"];
$ShowOrder = $_POST["ShowOrder"];
$ReadyTime = $_POST["ReadyTime"];
$Comment   = $_POST["Comment"];

$MaxDeliveryPrice = $_POST["MaxDeliveryPrice"];

$FoodIDs = $_POST["FoodIDs"];

 $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('FODds','$FoodIDs');";
   if(mysqli_query($con,$sql))
   {	}

//$UserAddress = $_POST["usersddress"];

if($MaxDeliveryPrice==""){
	
	$MaxDeliveryPrice = "100000";
}

if($OrderType==""){ 
	$OrderType = "Fast";
}

if($ReadyTime==""){ 
	$ReadyTime = "1";
}

if($ShowOrder==""){
	$ShowOrder = "YES";
}

   $sql="INSERT INTO Addresses (UserID,AddressType,AddressText,AddressLat,AddressLongt,AddressName) VALUES ('$UserID','Home','MyAddress','$UserLat','$UserLongt','Home');";
   if(mysqli_query($con,$sql))
   {}


foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

if($Method == ""){
	
	$Method = "CASH";
}

$FatoraDetails = "";

if($ShopID !=""){
	
	$FatoraDetails = $OrderDetails;
	
}

//	echo $Token;

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if($ShopID==""){
	    $ShopID = "0";
	}
	
	$res = mysqli_query($con,"SELECT CityID FROM Shops WHERE ShopID=$ShopID");
	while($row = mysqli_fetch_assoc($res)){
		$UserCitiesID = $row["CityID"];
	}
	
	
		$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID = $ShopID");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 //$ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 
                 $ShopPhone = $row["ShopPhone"]; 
				 $ShopLang = $row["LANG"]; 
				 
				 $ShopName = $row["ShopName"]; 
				 $ShopLogo = $row["ShopLogo"]; 

            }
	
	
	
	if(true){
	    
	    if($OrderPriceFromShop==""){
	        $OrderPriceFromShop = 0;
	    }
    
    
    
    
    if($ShopID==""){
    
    $ShopID = '0';
	
	
    
    	$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopName='$DestinationName' AND ShopLogo='$DestnationPhoto'");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 $ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 

            }
    }else{
        
        	$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID = $ShopID");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 //$ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 
				 $ShopLang = $row["LANG"]; 

            }
        
        
        
        
    }        
	
$PriceForShop = 0;
$splits = explode("\n", $OrderDetails);
$splits = $splits[sizeof($splits)-1];
$search = 'MAD';
if(preg_match("/{$search}/i", $splits)) {
  $splits = chop($splits,"MAD");
  $PriceForShop = $splits;
}



    
if($ShopID !="0"){
	
	$FatoraDetails = $OrderDetails;
	
}

if($Comment==""){
	$Comment = "-";
}


if($ShopID=="0"){
		$ShowOrder = "YES";
		$OrderType = "Fast";
		$Comment   = "-";
		
		//$rr = $UserLat;
	//	$UserLat = $UserLongt;
	//	$UserLongt = $UserLat;
		
	//	$rrr = $DestnationLat;
	//	$DestnationLat = $DestnationLongt;
	//	$DestnationLongt = $rrr;
		
	}
	
	
	if($DestnationPhoto=="0"||$DestnationPhoto==""){
	   $DestnationPhoto =  "https://qoon.app/dash/photo/w-1440822481.png";
	}
	
	
		$OrdNum = rand(1000000000, 9999999999);
		$desg = rand(1000, 9999);
		
	   $PDFPAth = "https://qoon.app/userDriver/UserDriverApi/pdf/Invoice_POS_".$OrdNum.".pdf";

	$sql="INSERT INTO Orders (FourDigit,UserID,DestinationName,DestnationAddress,DestnationLat,DestnationLongt,DestnationPhoto,
   OrderDetails,OrderState,UserLat,UserLongt,OrderDelvTime,ShopID,OrderPriceForOur,OrderPriceFromShop,UserReview,OrderType,ShowOrder,Method,ReadyTime,MaxDeliveryPrice,FatoraDetails,Comment,UserName,UserPhone,UserEmail,UserAddress,CarTypeID,WeightsId,UserCitiesID,DestnationCitiesID,UserCountryId,DestnationCountryId,CompanyID,RealType,PDF)
   VALUES ('$desg','$UserID','$DestinationName','$DestnationAddress','$DestnationLat','$DestnationLongt','$DestnationPhoto',
   '$OrderDetails','waiting','$UserLat','$UserLongt','$OrderDelvTime','$ShopID','$PriceForShop','$OrderPriceFromShop','-','$OrderType','$ShowOrder','$Method','$ReadyTime','$MaxDeliveryPrice','$FatoraDetails','$Comment','$UserName','$UserPhone','$UserEmail','$UserAddress','$CarTypeID','$WeightsId','$UserCitiesID','$DestnationCitiesID','$UserCountryId','$DestnationCountryId','0','$RealType','$PDFPAth');";
   if(mysqli_query($con,$sql))
   {



 //  $sql="INSERT INTO Orders (UserID,DestinationName,DestnationAddress,DestnationLat,DestnationLongt,DestnationPhoto,
 //  OrderDetails,OrderState,UserLat,UserLongt,OrderDelvTime,ShopID,OrderPriceForOur,OrderPriceFromShop,UserRated,UserReview,OrderType,ShowOrder,Method,ReadyTime,MaxDeliveryPrice,FatoraDetails,Comment) VALUES ('$UserID','$DestinationName','$DestnationAddress','$DestnationLat','$DestnationLongt','$DestnationPhoto',
  // '$OrderDetails','waiting','$UserLat','$UserLongt','$OrderDelvTime','$ShopID','$PriceForShop','$OrderPriceFromShop',5,'-','$OrderType','$ShowOrder','$Method','$ReadyTime','$MaxDeliveryPrice','$FatoraDetails','$Comment');";
  // if(mysqli_query($con,$sql))
  // {


       

	$last_id = mysqli_insert_id($con);

	NotiShop($ShopID,$last_id);
	$elements = explode("#", $FoodIDs);
	
	foreach ($elements as $element) {
			
		if($element!=""){	
			$parts = explode("*", $element);
				$id = $parts[0];
				$Quantity = $parts[1];
				$Size = $parts[2];	
				$Color = $parts[3];
			if($Quantity!=""){	
			$sql="INSERT INTO OrderDetailsOrder (OrderID,FoodID,Quantity,Size,Color)
			   VALUES ('$last_id','$id','$Quantity','$Size','$Color');";
			   if(mysqli_query($con,$sql))
			   {}
			}   
		}
			   
	}			
	
	
	
	
	//$last_id = $con->insert_id;
	//echo $last_id;
	$yy = $last_id;
	AddOrderFirebase($last_id);
	   
	$key['result'] = "done";
//	echo json_encode($key);	

	$message ="Posted Sucssessfuly";
    $success = true;
    $status_code = 200;
	//$result = $last_id; 
	
	
	 $sql="INSERT INTO ShopNotification (ShopID,OrderID,NotificationText) VALUES ('$ShopID','$last_id','new order');";
   if(mysqli_query($con,$sql))
   {		
		$OrderPriceFromShop =	number_format($OrderPriceFromShop, 2);
       
       if($ShopLang=="AR"||$ShopLang=="ar"){
		   
		   $ShopTitle = "✅ استلمت طلبا جديدا ";
		   $ShopBody  = "لقد استلمت طلبا جديدا ". $OrderPriceFromShop ." درهم.";
		   
	   }else if($ShopLang=="EN"||$ShopLang=="en"){
		   
		   $ShopTitle = "New Order Received! ✅";
		   $ShopBody  = "You have received a new order ". $OrderPriceFromShop ." MAD.";
		   
	   }else{
		   
		   $ShopTitle = "Nouvelle commande reçue ! ✅";
		   $ShopBody  = "Vous avez reçu une nouvelle commande ". $OrderPriceFromShop ." MAD.";
		   
	   }
       
       ResturantNotification($ShopFirebaseToken,$ShopTitle,$ShopBody);
	   newNotfi($ShopFirebaseToken,$ShopTitle,$ShopBody,$accessToken,$ProgID);
	   
	   
	   
	   
	   
	   
   
               //////////////////////////
            
            
            $pdf = new FPDF('P', 'mm', array(80, 200)); // الطول يمكن يزيد حسب المحتوى
            $pdf->AddPage();
            
            // إعداد الخطوط
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 6, $DestinationName, 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 5, "Tel: $ShopPhone", 0, 1, 'C');

            $pdf->Cell(0, 5, "Date: " . date('Y-m-d H:i'), 0, 1, 'C');
            $pdf->Ln(3);
            
            // خط فاصل
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(3);
            
            // معلومات العميل
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 5, "Customer: $UserrrrName", 0, 1);
            $pdf->Cell(0, 5, "Tel: $UserrrPhoneNumber", 0, 1);
            
            $pdf->Cell(0, 5, "Invoice #: $last_id", 0, 1);
            $pdf->Ln(2);
            
            // خط فاصل
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(3);
            
            // عناوين الأعمدة
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, 'Item', 0, 0);
            $pdf->Cell(15, 6, 'Qty', 0, 0, 'C');
            $pdf->Cell(20, 6, 'Price', 0, 0, 'C');
            $pdf->Cell(15, 6, 'Total', 0, 1, 'R');
            
            // خط فاصل
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(2);
            
            // عناصر الفاتورة
            $pdf->SetFont('Arial', '', 10);
            $grandTotal = 0;
            
            $items = [
                ['name' => 'Test Item 1', 'qty' => 2, 'price' => 100],
                ['name' => 'Test Item 2', 'qty' => 1, 'price' => 75],
                ['name' => 'Test Item 3', 'qty' => 3, 'price' => 40],
            ];
            
            // foreach ($items as $item) {
            //     $total = $item['qty'] * $item['price'];
            //     $grandTotal += $total;
            //     $pdf->Cell(30, 6, $item['name'], 0, 0);
            //     $pdf->Cell(15, 6, $item['qty'], 0, 0, 'C');
            //     $pdf->Cell(20, 6, number_format($item['price'], 0), 0, 0, 'C');
            //     $pdf->Cell(15, 6, number_format($total, 0), 0, 1, 'R');
            // }
            
            
            $elements = explode("#", $FoodIDs);
	
        	foreach ($elements as $element) {
        			
        		if($element!=""){	
        			$parts = explode("*", $element);
        			
        			
        			if (count($parts) < 2) {
                        continue;
                    }
                
                    if (!isset($parts[0], $parts[1])) {
                        continue;
                    }
        			
        				$id = $parts[0];
        				$Quantity = $parts[1];
        				// $Size = $parts[2];	
        				// $Color = $parts[3];
        			
            		if($id!=""){
            		    
                		$res = mysqli_query($con,"SELECT FoodPrice,FoodOfferPrice,FoodName FROM Foods WHERE FoodID = $id");
                         while($row = mysqli_fetch_assoc($res)){
                             
                             $FoodPrice      = $row["FoodPrice"];
                             $FoodOfferPrice = $row["FoodOfferPrice"];
                             $FoodName       = $row["FoodName"];
                             
                             if($FoodOfferPrice==""){
                                 $FoodOfferPrice = $FoodPrice;
                             }
                             
                         }
                			
                			
                			if(strlen($FoodName) > 15){
                                $FoodName = substr($FoodName, 0, 15) . "...";
                            }
                			
                			 $total = $Quantity * $FoodOfferPrice;
                            $grandTotal += $total;
                            $pdf->Cell(30, 6, $FoodName, 0, 0);
                            $pdf->Cell(15, 6, $Quantity, 0, 0, 'C');
                            $pdf->Cell(20, 6, number_format($FoodOfferPrice, 0), 0, 0, 'C');
                            $pdf->Cell(15, 6, number_format($total, 0), 0, 1, 'R');
            			
            		}
        		
        		}
        			   
        	}			
	
            
            
            
            
            // خط فاصل
            $pdf->Ln(2);
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(3);
            
            // الإجمالي
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'TOTAL: ' . number_format($grandTotal, 0) . ' MAD', 0, 1, 'C');
            $pdf->Ln(3);
            
            // خط فاصل
            $pdf->Cell(0, 0, '', 'T');
            $pdf->Ln(5);
            
            // شكرًا
            $pdf->SetFont('Arial', 'I', 10);
            $pdf->Cell(0, 5, 'Thank you for your purchase!', 0, 1, 'C');
            $pdf->Cell(0, 5, 'Come again soon!', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Output
            //$pdf->Output('I', 'Invoice_POS_44.pdf');
            $pdf->Output('F', 'pdf/Invoice_POS_' . $OrdNum . '.pdf');
            
            
            
	   
	   
       
       
   }
	
	
	$res = mysqli_query($con,"SELECT * FROM Orders LEFT JOIN Shops ON Orders.ShopID = Shops.ShopID WHERE OrderID=$last_id");

    $result = array();

    while($row = mysqli_fetch_assoc($res)){

    //$data = $row[0];
    
    if($row["DestinationName"]=="Deliveryservice"){
        
        $row["DestinationName"] = "QOON Express";
    }
    
        $result[] = $row;
        $test=4;
    }
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
      $sql="UPDATE Users SET UserOrdersNum=UserOrdersNum+1 WHERE UserID=$UserID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	   if($OrderType=="Fast"){
	   
	   $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($DestnationLat)) * cos(radians(CurrentLat)) * cos(radians(CurrentLongt) - radians($DestnationLongt)) + sin(radians($DestnationLat)) * sin(radians(CurrentLat)))) AS distance FROM Drivers WHERE Online ='Online' HAVING distance <= 500");

        $result = array();

        while($row = mysqli_fetch_assoc($res)){

            //$data = $row[0];
            $result[] = $row;

            $FirebaseToken = $row["FirebaseDriverToken"];
			$LANG = $row["LANG"];
			
			
			
			
			if($LANG=="EN"){
				
				$Title = " New Order ! ";
				$MesBody = $DestinationName . "waiting for your offer";
				
			}else if($LANG=="FR"){
				
				$Title =  " Nouvel Commande ! ";
				$MesBody = $DestinationName;
				
			}else if($LANG=="AR"){
				
				$Title =  " طلب جديد ! ";
				$MesBody = $DestinationName . " ينتظر عرضك ";
				
			}
			
			
			if($DestinationName=="Deliveryservice"){
			    
			    
			    if($LANG=="EN"){
				
				$MesBody =  "QOON Express";
				
			}else if($LANG=="FR"){
				
				$MesBody =  "QOON Express";
				
			}else if($LANG=="AR"){
				
				$MesBody =  "QOON Express";
				
			}
			    
			    
			}
			

            send_notification($FirebaseToken,$Title,$MesBody);
			newNotfi($FirebaseToken,$Title,$MesBody,$accessToken,$ProgID);
            $test=4;

                }
                
	   }       
                
                
        $res = mysqli_query($con,"SELECT * FROM Users WHERE PhoneNumber = '+212707777721'");

        $result = array();

        while($row = mysqli_fetch_assoc($res)){

            //$data = $row[0];
            $result[] = $row;

            $FirebaseToken = $row["UserFirebaseToken"];

            send_notificationTonadim($FirebaseToken,$DestinationName);
            $test=4;

                }        
                
                
                
                
	   
    
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
	
	
	function newNotfi($tokens,$TitleW,$MesBodyW,$accessTokenw,$Pid)
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
   
 
function send_notification($tokens,$TitleW,$MesBodyW)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $TitleW,
			 'body' => $MesBodyW)
			);
			
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
				 'title' => $TitleW,
				 "sound"=> 'notification.wav', 
				"channel_id"=>"channel_id_6",
    		  "sound"=>'notification.wav',
             "android_channel_id"=>"channel_id_6", 
				 'body' => $MesBodyW),
			 'data'=>array(
			     'title' => $TitleW,
			     'body' => $MesBodyW,
			     'priority'  =>  'high',
                 'content-available'  =>  true,
                 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                 'Prod'  =>  $PlaceIDs,
                 'Type' => $contain,
                 "sound"=> 'notification.wav', 
				"channel_id"=>"channel_id_6",
    		  "sound"=>'notification.wav',
             "android_channel_id"=>"channel_id_6",
			     )
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
	
	
	function ResturantNotification($tokens,$ShopTitlew,$ShopBodyw)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $ShopTitlew,
			 'body' => $ShopBodyw)
			);
			
			
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
				 'title' => $ShopTitlew,
				  
				"channel_id"=>"channel_id_6",
    		  "sound"=>'notification.wav',
             "android_channel_id"=>"channel_id_6", 
				 'body' => $ShopBodyw),
			 'data'=>array(
			     'title' => $ShopTitlew,
			     'body' => $ShopBodyw,
			     'priority'  =>  'high',
                 'content-available'  =>  true,
                 'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                 'Prod'  =>  $PlaceIDs,
                 'Type' => $contain,
                 "sound"=> 'notification.wav', 
				"channel_id"=>"channel_id_6",
    		  "sound"=>'notification.wav',
             "android_channel_id"=>"channel_id_6",
			     )
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
	
	
	
	
	function send_notificationTonadim($tokens,$shop)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokensw,
			 'notification'=>array(
			 'title' => "New Order",
			 'body' => "MR Salaheldin a new order now to $shop")
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
   
   
   
   
    function AddOrderFirebase($OrderID)
	{
      
		$url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'.json/';
		$postData = array(
		      'OrderStatus' => "FOUND",

            );	
            
       // echo  $url;   
	   $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PATCH" );
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
           
       }else{
       }
       curl_close($ch);
       return $result;
	}
	
	
	
	class Point {
    public $x, $y;
 
    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
}
 
function isInsidePolygon($polygon, $position) {
    $n = count($polygon);
    $crossings = 0;
 
    for ($i = 0; $i < $n; $i++) {
        $j = ($i + 1) % $n;
 
        if (($polygon[$i]->x == $position->x && $polygon[$i]->y == $position->y) ||
            ($polygon[$j]->x == $position->x && $polygon[$j]->y == $position->y)) {
            return true;
        }
 
        if (($polygon[$i]->y > $position->y) == ($polygon[$j]->y > $position->y)) {
            continue;
        }
 
        if ($position->y >= min($polygon[$i]->y, $polygon[$j]->y) && $position->y <= max($polygon[$i]->y, $polygon[$j]->y)) {
            $xCrossing = ($position->y - $polygon[$i]->y) * ($polygon[$j]->x - $polygon[$i]->x) / ($polygon[$j]->y - $polygon[$i]->y) + $polygon[$i]->x;
 
            if ($position->x < $xCrossing) {
                $crossings++;
            }
        }
    }
 
    return $crossings % 2 == 1;
}


function NotiShop($ShoppID, $OrderID)
{
    $url = 'https://jibler-37339-default-rtdb.firebaseio.com/Shop/'.$ShoppID.'.json';

    $postData = array(
        'CurrentOrder' => date('Y-m-d H:i:s'),
        'ShopID' => $ShoppID, // معلومة إضافية مفيدة
        'UpdatedAt' => date('Y-m-d H:i:s')
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // ✅ التغيير هنا
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

    $result = curl_exec($ch);

    if ($result === FALSE) {
        // سجل الخطأ في الداتابيس بدل die
        error_log("Curl Error Firebase: " . curl_error($ch));
        return false;
    }

    curl_close($ch);

    return $result;
}
   
   
   
   
die;
mysqli_close($con);
?>