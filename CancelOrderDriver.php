<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];

$AppType = $_POST["AppType"];


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
	    
	    
	     if($AppType==""){
            $AppType = "QOON";
        }
	  
	  
	  if($AppType=="QOON"){  

   if($IsPrepared=="NO"){
	  $sql="UPDATE Orders SET OrderState='waiting',DelvryId='0' WHERE OrderID=$OrderID";
   }else{
	  $sql="UPDATE Orders SET OrderState='waiting'DelvryId='0' WHERE OrderID=$OrderID"; 
	 
   }
   if(mysqli_query($con,$sql))
   {
       
        $sql="DELETE FROM DriversOffer WHERE OrderID=$OrderID";
	     if(mysqli_query($con,$sql))
            {}
	   
// 	    if($IsPrepared!="NO"){
	   
// 	   $res = mysqli_query($con,"SELECT * FROM Orders JOIN Shops ON Orders.ShopID = Shops.ShopID WHERE OrderID=$OrderID");
// 					while($row = mysqli_fetch_assoc($res)){
						
						

// 					$OrderPriceFromShop = $row["OrderPriceFromShop"];
// 					$ShopID = $row["ShopID"];
					
// 						$ShopName  = $row["DestinationName"];
// 						$ShopPhoto = $row["DestnationPhoto"];
						
// 						$DriverID =  $row["DelvryId"];
// 						$Method   =  $row["Method"];
						
// 						$UserID = $row["UserID"];
						
// 						$OrderPrice = $row["OrderPrice"];
						

// 					}
	  
	  
	  
// 	  $sql="UPDATE Shops SET Balance=Balance+$OrderPriceFromShop WHERE ShopID=$ShopID";
// 				   if(mysqli_query($con,$sql))
// 				   {}
	  
	  
// 	   $sql="UPDATE Orders SET PaidForDriver = 'NotPaid' WHERE OrderID=$OrderID";
// 		   if(mysqli_query($con,$sql))
// 		   {}
	   
	   
 	
	   
	   
	   
	   
	
	
	AddOrderFirebase($OrderID);
	AddOrderFirebase22($OrderID);
	DeleteOrderFirebase($OrderID);
	
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
       
				//ResturantNotification($ShopFirebaseToken,$ShopTitle,$ShopBody);	
				newNotfi($ShopFirebaseToken,$ShopTitle,$ShopBody,$accessToken,$ProgID);
	
	
	

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
	      
	      
	       $url = "https://zeewana.com/QoonDriverApis/CancelOrderDriver.php";

// البيانات التي سيتم إرسالها
            $postData = [
                'OrderID' => $_POST['OrderID'] ?? '',
               
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
	
	
	
	
	function AddOrderFirebaseZ($OrderID)
	{
      
		$url = 'https://jibler-37339-default-rtdb.firebaseio.com/OffersZ/'.$OrderID.'.json/';
		$postData = array(
		      'OrderStatus' => "CANCELLED",

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
	
		function AddOrderFirebase($OrderID)
	{
      
		$url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'.json/';
		$postData = array(
		      'OrderStatus' => "CANCELLED",

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
	
	
		function AddOrderFirebase22($OrderID)
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
	
	
	
	function DeleteOrderFirebase($OrderID)
{
    $url = 'https://jibler-37339-default-rtdb.firebaseio.com/Offers/'.$OrderID.'.json';

    $postData = array(
        'OrderStatus' => 'FOUND' // أو القيمة الحالية
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT"); // 👈 مهم
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

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