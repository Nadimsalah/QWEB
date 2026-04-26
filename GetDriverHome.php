<?php
require "conn.php";


$UserID     = $_POST["UserID"];
$DestinationName  = $_POST["DestinationName"];
$DestnationLat      = $_POST["DestnationLat"];
$DestnationLongt   = $_POST["DestnationLongt"];
$DestnationPhoto  = $_POST["DestnationPhoto"];
$DestnationAddress = $_POST["DestnationAddress"];
$OrderDetails    = $_POST["OrderDetails"];
$UserLat     = $_POST["UserLat"];
$UserLongt     = $_POST["UserLongt"];
$OrderDelvTime = $_POST["OrderDelvTime"];
$Token = "s";


echo $UserID;

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
} 

//	echo $Token;

$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
	if($test==4 || empty($result)){
    
    
    $ShopID = '0';
    
    	$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopName='$DestinationName' AND ShopLogo='$DestnationPhoto'");

            $result = array();
        
            while($row = mysqli_fetch_assoc($res)){
        
                 $ShopID = $row["ShopID"];
                 $ShopFirebaseToken = $row["ShopFirebaseToken"]; 

            }
	
$PriceForShop = 0;
$splits = explode("\n", $OrderDetails);
$splits = $splits[sizeof($splits)-1];
$search = 'MAD';
if(preg_match("/{$search}/i", $splits)) {
  $splits = chop($splits,"MAD");
  $PriceForShop = $splits;
}



    



   $sql="INSERT INTO Orders (UserID,DestinationName,DestnationAddress,DestnationLat,DestnationLongt,DestnationPhoto,
   OrderDetails,OrderState,UserLat,UserLongt,OrderDelvTime,ShopID,OrderPriceForOur) VALUES ('$UserID','$DestinationName','$DestnationAddress','$DestnationLat','$DestnationLongt','$DestnationPhoto',
   '$OrderDetails','waiting','$UserLat','$UserLongt','$OrderDelvTime','$ShopID','$PriceForShop');";
   if(mysqli_query($con,$sql))
   {


       

	$last_id = mysqli_insert_id($con);
	//echo $last_id;
	   
	$key['result'] = "done";
//	echo json_encode($key);	

	$message ="Posted Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = $last_id; 
	
	
	 $sql="INSERT INTO ShopNotification (ShopID,OrderID,NotificationText) VALUES ('$ShopID','$last_id','تم اضافة طلب جديد برقم $last_id');";
   if(mysqli_query($con,$sql))
   {
       
       
       
       ResturantNotification($ShopFirebaseToken);
       
       
   }
	
	
	$res = mysqli_query($con,"SELECT * FROM Orders WHERE OrderID=$last_id");

    $result = array();

    while($row = mysqli_fetch_assoc($res)){

    //$data = $row[0];
        $result[] = $row;
        $test=4;
    }
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
    
    
      $sql="UPDATE Users SET UserOrdersNum=UserOrdersNum+1 WHERE UserID=$UserID";
	   if(mysqli_query($con,$sql))
       {
		   
	   }
	   
	   
	   $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($DestnationLat)) * cos(radians(CurrentLat)) * cos(radians(CurrentLongt) - radians($DestnationLongt)) + sin(radians($DestnationLat)) * sin(radians(CurrentLat)))) AS distance FROM Drivers WHERE Online ='Online' HAVING distance <= 50");

        $result = array();

        while($row = mysqli_fetch_assoc($res)){

            //$data = $row[0];
            $result[] = $row;

            $FirebaseToken = $row["FirebaseDriverToken"];

            send_notification($FirebaseToken);
            $test=4;

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
   
 
function send_notification($tokens)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => "Nouvelle commande",
			 'body' => "Une nouvelle commande près de chez vous")
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
	
	
	function ResturantNotification($tokens)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => "Nouvelle commande",
			 'body' => "Une nouvelle commande près de chez vous")
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
			 'to' => $tokens,
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
   
   
   
die;
mysqli_close($con);
?>