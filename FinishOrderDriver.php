<?php
require "conn.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


 $OrderID  = $_POST["OrderID"];
$DriverID  = $_POST["DriverID"];
$AppType = $_POST["AppType"];
$RealType = "";


	$res = mysqli_query($con,"SELECT RealType FROM Orders WHERE OrderID=$OrderID");
	while($row = mysqli_fetch_assoc($res)){
	    
	    
	    $RealType = $row["RealType"];
	    
	}


$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;
		$FName = $row["FName"];
		$PersonalPhoto = $row["PersonalPhoto"];
		

	}
	
	if(true){
	    
	    
	   if( $AppType ==""){
	       $AppType = "QOON";
	   }
	   
	   if($AppType=="QOON"){
	    
	    $res = mysqli_query($con,"SELECT * FROM Orders LEFT JOIN Shops ON Orders.ShopID = Shops.ShopID WHERE OrderID=$OrderID");
	while($row = mysqli_fetch_assoc($res)){
		
		
	$Type =	$row["Type"];
	$ShopName  = $row["DestinationName"];
	$ShopPhoto = $row["DestnationPhoto"];
	$OrderPriceFromShop = $row["OrderPriceFromShop"];
	$ShopID = $row["ShopID"];
	$Method = $row["Method"];
	$CategoryID = $row["CategoryID"];
	$ShopLogo   = $row["ShopLogo"];
	$UserID     = $row["UserID"];
	$OrderPrice     = $row["OrderPrice"];
	
		$test=4;

	}
	
	
	 $res = mysqli_query($con,"SELECT name,Email FROM Users WHERE UserID = $UserID");


        while($row = mysqli_fetch_assoc($res)){

            //$data = $row[0];
            
            
            $UserrrrName = $row["name"];
            $Email       = $row["Email"];

                }        
	
	
	if($ShopID!="0"){
	    
	    if($ShopID!=null){
	    
	    if($Type == "Other"){
	    
	    $Paid = 'Paid';
		$ShopRecive = 'YES';
		
		if($Method!="CASH"){
		   $Paid	   = 'NotPaid'; 
			
		}
	    
	    }else{
			
		   $res = mysqli_query($con,"SELECT disUser,percent FROM OrdersJiblerpercentage");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                                       
											$disUser = $row["disUser"];
											$percentShop = $row["percent"];
                                                               
                                        }
										
		    $res = mysqli_query($con,"SELECT PercForOrder FROM Categories WHERE CategoryId = $CategoryID");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                                       
											$percentShop = $row["PercForOrder"];
                                                               
                                        }							
										
										
			$ShopFees = $OrderPriceFromShop * $percentShop / 100;							
			
			$ChangedBalance = $OrderPriceFromShop - $ShopFees;
			
	       $Paid	= 'NotPaid'; 
		   $ShopRecive = 'NO';
		   
		   if($Method!="CASH"){
		   $Paid	   = 'NotPaid'; 
			
		   }
		   
		   
		   
		   
		   
		   if($Method=="CASH"){
			   $sql="UPDATE Shops SET Balance=Balance+$ChangedBalance WHERE ShopID=$ShopID";
			   if(mysqli_query($con,$sql))
			   {}
		   }else{
			   $sql="UPDATE Shops SET Balance=Balance-$ShopFees WHERE ShopID=$ShopID";
			   if(mysqli_query($con,$sql))
			   {}
		   }
		   
		   $sql="INSERT INTO SlasesRevTransaction (OrderID,ShopID,TotalPrice,CutPers) VALUES ('$OrderID','$ShopID','$OrderPriceFromShop','$ShopFees');";
			   if(mysqli_query($con,$sql))
			   {}
		   
		   $sql="UPDATE Money SET TotalIncome=TotalIncome+$ShopFees,SalesR=SalesR+$ShopFees";
			   if(mysqli_query($con,$sql))
			   {}
	        
	    }
	    
	    
	    }
	}
		
    $res = mysqli_query($con,"SELECT DriverCommesion FROM MoneyStop");
	while($row = mysqli_fetch_assoc($res)){
		
		$DriverCommesion = $row["DriverCommesion"];
		
	}	
		

   $sql="UPDATE Orders SET OrderState='Done',PaidForDriver = '$Paid',ShopRecive = '$ShopRecive',DriverOmola = '$DriverCommesion' WHERE OrderID=$OrderID";
   if(mysqli_query($con,$sql))
   {
	   
	   $sql="UPDATE Drivers SET DriverOrdersNum=DriverOrdersNum+1 WHERE DriverID=$DriverID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	   
	   $OrderPrice = "0";
	   $res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$OrderID");
	    while($row = mysqli_fetch_assoc($res)){
		
		
		$OrderPrice = $row["OrderPrice"];
		
		$test=4;

	    }
	
	   
	   
	   $sql="UPDATE Drivers SET DriverOrdersNum=DriverOrdersNum+1,TotolEarnMoney=TotolEarnMoney+$OrderPrice WHERE DriverID=$DriverID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	    
	    
	    
	    
	   
	
	$message ="Finished Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
    
    
    
   
     
            //////////////////////////// Email ///////////////////////////
            
        $currency = 'MAD';

$subjectFR = "Confirmation d'achat — QOON 🎉";
$from = "Zeewana <hi@zeewana.com>";
$returnPath = "hi@zeewana.com";

$productsHTML = '<table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:0;">';
$grandTotal = 0;



	    $res = mysqli_query($con,"SELECT FoodPrice,FoodOfferPrice,FoodName,FoodPhoto,OrderDetailsOrder.Quantity FROM Foods JOIN OrderDetailsOrder ON Foods.FoodID = OrderDetailsOrder.FoodID WHERE OrderDetailsOrder.OrderID = $OrderID");
	        while($row = mysqli_fetch_assoc($res)){

        $FoodPrice      = $row["FoodPrice"];
        $FoodOfferPrice = ($row["FoodOfferPrice"] > 0) ? $row["FoodOfferPrice"] : $row["FoodPrice"];
        $FoodName       = $row["FoodName"];
        $FoodPhoto      = $row["FoodPhoto"];
        $Quantity       = $row["Quantity"];

        if(strlen($FoodName) > 25){
            $FoodName = mb_substr($FoodName, 0, 25, "UTF-8") . "";
        }

        $total = $Quantity * $FoodOfferPrice;
        $grandTotal += $total;

        // ✅ Table layout compatible with email clients
        $productsHTML .= '    <tr>
        <td width="80" style="padding:10px 8px;">
            <img src="'.$FoodPhoto.'" alt="'.$FoodName.'" width="70" height="70" style="display:block;border-radius:6px;object-fit:cover;">
        </td>
        <td style="font-family:Arial,sans-serif;font-size:14px;color:#333;padding:10px 8px;">
            '.$FoodName.'<br>
            <span style="color:#777;font-size:12px;">x'.$Quantity.'</span>
        </td>
        <td align="right" style="font-family:Arial,sans-serif;font-size:14px;font-weight:bold;color:#000;padding:10px 8px; white-space:nowrap;">
            '.$currency.' '.number_format($FoodOfferPrice, 0).'
        </td>
    </tr>';
    
	        }
     
$productsHTML .='</table>';

// 	$sql="INSERT INTO Tesst (Keeyy,Valww)
// 			   VALUES ('$productsHTML','$productsHTML');";
// 			   if(mysqli_query($con,$sql))
// 			   {}


$subtotal      = $grandTotal;
$delivery_fee  = $OrderPrice;
$tax           = 0;
$total_price   = $subtotal + $delivery_fee + $tax;
$year = date("Y");

$messageFR = '
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Confirmation d’achat — QOON</title>
<style>
body{margin:0;padding:0;background:#f7f9fc;font-family:Arial,Helvetica,sans-serif;color:#111827;}
.container{max-width:640px;margin:32px auto;background:#fff;border-radius:14px;overflow:hidden;}
.header{text-align:center;padding:28px 24px;border-bottom:1px solid #f1f5f9;}
.shop-logo{width:150px;border-radius:12px;margin-bottom:10px;}
</style>
</head>
<body>
<div class="container">

<div class="header">
<img class="shop-logo" src="'.$ShopLogo.'" alt="Logo '.$ShopName.'">
<h2>Merci pour votre achat, '.$UserrrrName.' !</h2>
<p>Commande <strong>#'.$OrderID.'</strong></p>
<p><img src="https://qoon.app/userDriver/UserDriverApi/logos/QOONLOGO.png" alt="Logo Zeewana" style="width:180px; height:auto;"></p>

</div>

<div style="padding:20px;">
<h3>Vos articles</h3>
'.$productsHTML.'
<hr>


<div style="text-align:right; font-size:18px; line-height:1.6; margin-top:10px;">
  <p><strong>Frais de livraison :</strong> '.number_format($delivery_fee,0).' '.$currency.'</p>
  <p><strong>Total :</strong> '.number_format($total_price,0).' '.$currency.'</p>
</div>

</div>



<div style="text-align:center;padding:15px;font-size:12px;color:#666;">
© '.$year.' QOON — Tous droits réservés
</div>

</div>
</body>
</html>
';

$headersFR  = "From: $from\r\n";
$headersFR .= "Reply-To: $returnPath\r\n";
$headersFR .= "Return-Path: $returnPath\r\n";
$headersFR .= "MIME-Version: 1.0\r\n";
$headersFR .= "Content-Type: text/html; charset=UTF-8\r\n";

// ✅ ensures email deliverability
//mail($Email, $subjectFR, $messageFR, $headersFR);


$mail = new PHPMailer(true);

                        try {
                            // $mail->isSMTP();
                            // $mail->Host       = 'smtp.zeewana.com';
                            // $mail->SMTPAuth   = true;
                            // $mail->Username   = 'hi@zeewana.com';
                            // $mail->Password   = 'Qoon@102030++';
                        
                            // // ✅ Security & Port (الإختيار الأفضل)
                            // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            // $mail->Port       = 465;
                        
                            // $mail->setFrom('hi@zeewana.com', 'Zeewana');
                            // $mail->addAddress($Email, $name);
                        
                            // $mail->isHTML(true);
                            // $mail->CharSet = "UTF-8";
                            // $mail->Subject = $subjectFR;
                            // $mail->Body    = $messageFR;
                            
                            
                            $mail->isSMTP();
                            $mail->Host       = 'mail.qoon.app';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = 'info@qoon.app';
                            $mail->Password   = 'Qoon@102030++';
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                            $mail->Port       = 465;
                            $mail->SMTPAutoTLS = false;
                            
                            $mail->setFrom('info@qoon.app', 'QOON');
                            $mail->Sender = 'info@qoon.app';
                            $mail->addAddress($Email, $ShopName);
                            
                            $mail->isHTML(true);
                            $mail->CharSet = "UTF-8";
                            $mail->Subject = $subjectFR;
                            $mail->Body    = $messageFR;
                        
                            $mail->send();
                        } catch (Exception $e) {
                            // echo "Error: {$mail->ErrorInfo}";
                        }

            
            //////////////////////////////
   
   
    
    
    
    
    $res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$OrderID");

    $UserID = 0;
	$ShopID = 0;
    
        $result = array();

            while($row = mysqli_fetch_assoc($res)){
            //$data = $row[0];
            $result[] = $row;
            $UserID = $row["UserID"];
			$ShopID = $row["ShopID"];
			$DestinationName = $row["DestnationCitiesID"];
			$Method          = $row["Method"];
			$UserCitiesID          = $row["UserCitiesID"];

    }
    
    
     if($RealType!="QOON"){
        
                                                        
        	                $url = "https://zeewana.com/USERDRIVER/UserDriver/UserDriverApi/FinishOrderDriverKinz.php";
                            

                            
                            // البيانات التي سيتم إرسالها
                            $postData = [
                                'OrderID' => $_POST['OrderID'] ?? '',
                                'DriverID' => $_POST['DriverID'] ?? '',
                                'UserID' => $UserID ?? '',
                                'FName' => $FName ?? '',
                                'PersonalPhoto' => $PersonalPhoto ?? '',
                                'OrderPriceFromShop' => $OrderPriceFromShop ?? '',
                                'DestinationName' => $DestinationName ?? '', 
                                'DestnationPhoto' => $ShopPhoto ?? '', 
                                   
                                 
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
                            
                            // تحديد نوع المخرجات JSON
                            header('Content-Type: application/json');
                            
                            // طباعة الاستجابة كما هي
                        //    echo $response;
        
        
    }   
    
    
    
    
    $sql="INSERT INTO UserNotification (OrderID,UserID,NotificationText) VALUES ('$OrderID','$UserID','Votre numéro de commande $OrderID a été livré');";
   if(mysqli_query($con,$sql))
   {
       
       $res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");
			
			$UserFirebaseToken = "";

		$result = array();

			while($row = mysqli_fetch_assoc($res)){

			//$data = $row[0];
			$result[] = $row;
			$test=4;
			
			$UserFirebaseToken = $row["UserFirebaseToken"];
			$UserPhoto         = $row["UserPhoto"];
			$LANG = $row["LANG"];

			}
			
			
			
			/////////////transaction ///////
			
			$Moneys = $OrderPriceFromShop + $OrderPrice ;
			
			
			
			$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DriverID");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;
		$FName = $row["FName"];
		$PersonalPhoto = $row["PersonalPhoto"];
		

	}
	
							$disUser = 0;
				
				$res = mysqli_query($con,"SELECT disUser,percent FROM OrdersJiblerpercentage");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                        
                                       
										$disUser = $row["disUser"];
										$percentShop = $row["percent"];
                                                               
                                        }
										
										if($disUser<3){
											$disUser = 3;
										}
										
				$res = mysqli_query($con,"SELECT PercForOrder FROM Categories WHERE CategoryId = $CategoryID");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                                       
											$percentShop = $row["PercForOrder"];
                                                               
                                        }							
	
				$disUser = ($OrderPriceFromShop * $disUser / 100);
				$Moneys  = $Moneys + $disUser;
				
				$ShopFees= $OrderPriceFromShop *$percentShop / 100;
			
			 $sql="INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees) VALUES ('$UserID','$Moneys','$Method','$ShopName','$ShopPhoto','$DriverID','$OrderID','$FName','$PersonalPhoto','less','$disUser');";
			   if(mysqli_query($con,$sql))
			   {}
		   
		   $sql="INSERT INTO FeesTransaction (UserID,OrderID,Money) VALUES ('$UserID','$OrderID','$disUser');";
			   if(mysqli_query($con,$sql))
			   {}
		  
		    
		  $sql="UPDATE Money SET TotalIncome=TotalIncome+$disUser,ServComm=ServComm+$disUser";
			   if(mysqli_query($con,$sql))
			   {}
		   
		 //  $sql="INSERT INTO DriverTransactions (DriverID,Address,SubAddress,Money) 
		//	  VALUES ('$DriverID','$ShopName','jibler_payment','$Moneys');";
		//	  if(mysqli_query($con,$sql))
		//	  {}
	
				$res = mysqli_query($con,"SELECT * FROM MoneyStop");

							$result = array();
$DriverCommesionN = 0;
							while($row = mysqli_fetch_assoc($res)){


							$DriverCommesionN = $row["DriverCommesion"];
							
							
							}
			
			
		   $sql="INSERT INTO DriverRevTransaction (DriverID,OrderID,DeliveryZoneID,Money) VALUES ('$DriverID','$OrderID','$UserCitiesID','$DriverCommesionN');";
			   if(mysqli_query($con,$sql))
			   {}

		   $sql="UPDATE Money SET TotalIncome=TotalIncome+$DriverCommesionN,DeliveryR=DeliveryR+$DriverCommesionN";
			   if(mysqli_query($con,$sql))
			   {} 		
	
		   
		   $sql="INSERT INTO DriverTransactions (Address,SubAddress,Money,DriverID,TransToken,TransationPhoto,PayMethod,UserPhoto,OrderID,fees) VALUES ('$ShopName','$FName','$Moneys','$DriverID','NONO','$ShopPhoto','$Method','$UserPhoto','$OrderID','$DriverCommesionN');";
			   if(mysqli_query($con,$sql))
			   {}
			
			///////////// transaction /////
			
			$sql="INSERT INTO ShopLastTransaction (ShopID,Money,Method,TransactionName,TransactionPhoto,DriverPhoto,DriverName,TransactionStatus,OrderID,fees) VALUES ('$ShopID','$OrderPriceFromShop','$Method','$DestinationName','Shop','$FName','$PersonalPhoto','Done','$OrderID','$ShopFees');";
			   if(mysqli_query($con,$sql))  
			   {}
			
			
			if($Method=="WALLET"){
				

				

				
				$sql="UPDATE Users SET Balance = Balance-$Moneys WHERE UserID = $UserID";
				   if(mysqli_query($con,$sql))
				   {}
				  
			}
			
			
      if($LANG == "EN"){
            $Title = "Delivery Successful ✅";
            $messagebody = "Your order has been delivered successfully. Thank you for choosing us!";
        } else if($LANG == "FR"){
            $Title = "Livraison réussie ✅";
            $messagebody = "Votre commande a été livrée avec succès. Merci de nous avoir choisis !";
        } else {
            $Title = "تم التوصيل بنجاح ✅";
            $messagebody = "تم تسليم طلبك بنجاح. شكرًا لاختيارك لنا!";
        }

       send_notification($UserFirebaseToken,$Title,$messagebody,$OrderID);
	   newNotfi($UserFirebaseToken,$Title,$messagebody,$OrderID,$accessToken,$ProgID);
   }
    
    

    

   }
   else
   {

	$message ="Error Updated";
    $success = false;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

   }
   
   
	}else{
	    
	    
	                $url = "https://zeewana.com/QoonDriverApis/FinishOrderDriver.php";

// البيانات التي سيتم إرسالها
$postData = [
    'OrderID' => $_POST['OrderID'] ?? '',
    'DriverID' => $_POST['DriverID'] ?? ''
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
	
	function send_notification($UserFirebaseToken,$Titlew,$messagebodyw,$OrderID)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => $Titlew,
			 'body' => $messagebodyw,
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
	
	
	function newNotfi($tokens,$TitleW,$MesBodyW,$OrderID,$accessTokenw,$Pid)
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
	
	
die;
mysqli_close($con);
?>