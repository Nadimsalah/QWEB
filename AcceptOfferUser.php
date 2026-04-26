<?php
require "conn.php";

//

$OrderID    = $_POST["OrderID"];
$DelvryId   = $_POST["DelvryId"];
$OrderPrice = $_POST["OrderPrice"];
$OfferKey   = $_POST["OfferKey"];



   $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('OrderID','$OrderID');";
   if(mysqli_query($con,$sql))
   {
       
   }
   
   $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('DelvryId','$DelvryId');";
   if(mysqli_query($con,$sql))
   {
       
   }
   
   $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('OrderPrice','$OrderPrice');";
   if(mysqli_query($con,$sql))
   {
       
   }
   
   $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('OfferKey','$OfferKey');";
   if(mysqli_query($con,$sql))
   {
       
   }

$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		$Token = $value;
	}
	
} 

$test=0;

// 	$res = mysqli_query($con,"SELECT Token FROM Company WHERE Token='$Token'");
// 	while($row = mysqli_fetch_assoc($res)){
		
		
// 		$test=4;

// 	}
	
	if(true){
		
	
		
		

   $sql="UPDATE Orders SET OrderState='Doing' ,OfferKey='$OfferKey', DelvryId = '$DelvryId', OrderPrice = '$OrderPrice' WHERE OrderID=$OrderID";
   if(mysqli_query($con,$sql))
   {
	   
	   
	   $res = mysqli_query($con,"SELECT ShopAccept FROM Orders WHERE OrderID=$OrderID");
			while($row = mysqli_fetch_assoc($res)){
				
				
				
				$ShopAccept = $row["ShopAccept"];
				
				if($ShopAccept=='YES'){
					AcceptOrderFirebase($OrderID,$OfferKey);
				}else{
					AcceptOrderFirebaseT($OrderID,$OfferKey);
				}
			}
	  			
	  $res = mysqli_query($con,"SELECT ShopID FROM Orders WHERE OrderID =$OrderID");
	while($row = mysqli_fetch_assoc($res)){
	    
    $ShopID = $row["ShopID"];
    NotiShop($ShopID);
	}
	   
	   
	   
	
	$message ="Updated Sucssessfully";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
    
    
   $sql="INSERT INTO DriverNotification (OrderID,DriverID,NotificationText) VALUES ('$OrderID','$DelvryId','Vous avez été accepté pour le numéro de commande $OrderID');";
   if(mysqli_query($con,$sql))
   {
       
   }
   
   $DriverToken = "";
   
   $res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DelvryId");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$DriverToken = $row["FirebaseDriverToken"];
		
		$test=4;
		send_notification($DriverToken,$OrderID);
		newNotfi($DriverToken,$OrderID,$accessToken,$ProgID);
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
   
	}
	
	else{
		
		
			$message ="Error Token Eror";
			$success = true;
			$status_code = 200;
			$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
	}
	
	
	function send_notification($DriverToken,$OrderID)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
			 'title' => "Votre offre acceptée",
			 'body' => "Vous avez été accepté pour le numéro de commande $OrderID")
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
	
	
		function newNotfi($DriverToken,$OrderID,$accessTokenw,$Pid)
	{

		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
				 'title' => "Votre offre acceptée", 
				 'body' => "Vous avez été accepté pour le numéro de commande $OrderID")
			);

        $fields = array(
            'message' => array(
                'token' => $DriverToken,
                'notification' => array(
                    'title' => "Votre offre acceptée",
                    'body' => "Vous avez été accepté pour le numéro de commande $OrderID"
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
	
	
	
	function AcceptOrderFirebase($OrderID,$OfferID)
	{
      
	//	$url = 'https://gibler-9590e-default-rtdb.firebaseio.com/Offers/'.$OrderID.'/'.$OfferID.'.json/';
	   $url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'/'.$OfferID.'.json/';

		$postData = array(
		      'driverorderstate' => "Confirmed",

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
	
	function AcceptOrderFirebaseT($OrderID,$OfferID)
	{
      
	//	$url = 'https://gibler-9590e-default-rtdb.firebaseio.com/Offers/'.$OrderID.'/'.$OfferID.'.json/';
	    $url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'/'.$OfferID.'.json/';

		$postData = array(
		      'driverorderstate' => "",
			  'isOfferAccepted' => "yes",
			  'state' => "accepted",

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