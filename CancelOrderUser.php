<?php
error_reporting(0);
require "conn.php";



$OrderID  = $_POST["OrderID"];


$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	
	$res = mysqli_query($con,"SELECT OrderState,ShopID,IsPrepared FROM Orders WHERE OrderID=$OrderID");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$OrderState=$row["OrderState"];
		$ShopID    =$row["ShopID"];
		$IsPrepared = $row["IsPrepared"];

	}
	
	//if($OrderState=="waiting"){
	
	if(true){
	
	if(true){

   $extraUpdates = "";
   if (isset($_POST['CancelLat']) && $_POST['CancelLat'] != '') {
       $cancelLat = mysqli_real_escape_string($con, $_POST['CancelLat']);
       $extraUpdates .= ", CancelLat='$cancelLat'";
   }
   if (isset($_POST['CancelLng']) && $_POST['CancelLng'] != '') {
       $cancelLng = mysqli_real_escape_string($con, $_POST['CancelLng']);
       $extraUpdates .= ", CancelLng='$cancelLng'";
   }
   if (isset($_POST['CancelPhoto']) && $_POST['CancelPhoto'] != '') {
       $cancelPhoto = mysqli_real_escape_string($con, $_POST['CancelPhoto']);
       $extraUpdates .= ", CancelPhoto='$cancelPhoto'";
   }

   if($IsPrepared=="NO"){
	  $sql="UPDATE Orders SET OrderState='Cancelled',ShowButtons='NO' $extraUpdates WHERE OrderID=$OrderID";
   }else{
	  $sql="UPDATE Orders SET OrderState='Cancelled' $extraUpdates WHERE OrderID=$OrderID"; 
   }
   
   $newStatus = 'Cancelled'; // For Firebase sync below
   if(mysqli_query($con,$sql))
   {
	   
	   
	   
	   	  $res = mysqli_query($con,"SELECT ShopID FROM Orders WHERE OrderID =$OrderID");
	while($row = mysqli_fetch_assoc($res)){
	    
    $ShopID = $row["ShopID"];
    NotiShop($ShopID);
	}
	   
	   
	    if($IsPrepared!="NO"){
	   
	   $res = mysqli_query($con,"SELECT * FROM Orders JOIN Shops ON Orders.ShopID = Shops.ShopID WHERE OrderID=$OrderID");
					while($row = mysqli_fetch_assoc($res)){
						
						

					$OrderPriceFromShop = $row["OrderPriceFromShop"];
					$ShopID = $row["ShopID"];
					
						$ShopName  = $row["DestinationName"];
						$ShopPhoto = $row["DestnationPhoto"];
						
						$DriverID =  $row["DelvryId"];
						$Method   =  $row["Method"];
						
						$UserID = $row["UserID"];
						
						$OrderPrice = $row["OrderPrice"];
						

					}
					
					// Notify Driver if assigned
					if ($DriverID && $DriverID != '0') {
					    $resD = mysqli_query($con, "SELECT FirebaseToken, LANG FROM Drivers WHERE DriverID = '$DriverID'");
					    if ($rowD = mysqli_fetch_assoc($resD)) {
					        $DriverToken = $rowD['FirebaseToken'];
					        $DriverLang = $rowD['LANG'] ?? 'en';
					        
					        if (strtolower($DriverLang) == "ar") {
					            $DriverTitle = "تم إلغاء الطلب 🚫";
					            $DriverBody  = "تم إلغاء الطلب برقم ". $OrderID ." من قبل المستخدم.";
					        } else if (strtolower($DriverLang) == "en") {
					            $DriverTitle = "Order Canceled 🚫";
					            $DriverBody  = "The order #". $OrderID ." has been canceled by the user.";
					        } else {
					            $DriverTitle = "Commande Annulée 🚫";
					            $DriverBody  = "La commande #". $OrderID ." a été annulée par l'utilisateur.";
					        }
					        
					        if (!empty($DriverToken)) {
					            ResturantNotification($DriverToken, $DriverTitle, $DriverBody);
					        }
					    }
					}
	  
	  
	  
	  $sql="UPDATE Shops SET Balance=Balance+$OrderPriceFromShop WHERE ShopID=$ShopID";
				   if(mysqli_query($con,$sql))
				   {}
	  
	  
	   $sql="UPDATE Orders SET PaidForDriver = 'NotPaid' WHERE OrderID=$OrderID";
		   if(mysqli_query($con,$sql))
		   {}
	   
	   
		}
	   
	   
	   
	   
	
	
	AddOrderFirebase($OrderID);
	AddOrderTrackerFirebase($OrderID);
	
	$message ="Updated Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
	
	
			$res = mysqli_query($con,"SELECT ShopFirebaseToken,LANG FROM Shops WHERE ShopID = $ShopID");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 //$ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 
				 $ShopLang = $row["LANG"]; 

            }	
						   
						   
				if($ShopLang=="AR"||$ShopLang=="ar"){
				   
				   $ShopTitle = "تم إلغاء طلبك.🚫";
				   $ShopBody  = "تم إلغاء الطلب برقم ". $OrderID ." من ِقبل العميل " . "يُرجى التأكد من استلام المنتجات المسترجعة من قبل مندوب التوصيل.";
				   
			   }else if($ShopLang=="EN"||$ShopLang=="en"){
				   
				   $ShopTitle = "Your order has been canceled 🚫";
				   $ShopBody  = "The order with ID  ". $OrderID ." has been canceled . " . "Please make sure to receive the returned products from the delivery man.";
				   
			   }else{
				   
				   $ShopTitle = "Votre commande a été annulé🚫";
				   $ShopBody  = "La commande avec l'identifiant ". $OrderID ." a été " . "Veuillez vous assurer de recevoir les produits retournés par le livreur.";
				   
			   }
       
				ResturantNotification($ShopFirebaseToken,$ShopTitle,$ShopBody);	
				//newNotfi($ShopFirebaseToken,$ShopTitle,$ShopBody,$accessToken,$ProgID);
	
	
	

   }
   else
   {

	$message ="Error Updated";
    $success = false;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

   }
   
	}
	
	else{
		
		
			$message ="Error Token Eror";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}
	
	
	}else{
		
		
		$message =" can not cancel";
			$success = false;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
		
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
	
	
	
	
	function AddOrderFirebase($OrderID)
	{
      
		$url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'.json/';
		$postData = array(
		      'OrderStatus' => 'CANCELLED',

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
	
	function AddOrderTrackerFirebase($OrderID)
	{
		$url = 'https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/'.$OrderID.'.json';
		$postData = array(
		    'current_status' => 'CANCELLED',
		    'cancelled_by' => 'User',
		    'cancel_reason' => 'Cancelled by the customer.'
        );	
	   $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
       curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
       $result = curl_exec($ch);           
       curl_close($ch);
       return $result;
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
	
	
	
	function NotiShop($ShoppID)
{
    $url = 'https://jibler-37339-default-rtdb.firebaseio.com/Shop/'.$ShoppID.'.json';

    $postData = array(
        'CurrentOrder' => date('Y-m-d H:i:s'),
        'ShopID' => $ShoppID, // معلومة إضافية مفيدة
        'UpdatedAt' => date('Y-m-d H:i:s')
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH"); // Changed from PUT to PATCH to avoid overwriting node
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