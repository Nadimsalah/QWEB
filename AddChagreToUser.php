<?php
require "conn.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

//$test=0;
//send_notification();
//die;

$UserID 	= $_POST["UserID"];
$Money      = $_POST["Money"];
$ReceiverID = $_POST["ReceiverID"];

$Token = "s";

foreach (getallheaders() as $name => $value) { 

	if($name == "token"){
		
		$Token = $value;
	
	}
	
}


// $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('$UserID','$ReceiverID');";
//   if(mysqli_query($con,$sql))
//   {}
  
// $sql="INSERT INTO Testt (Keyy,Valuee) VALUES ('$UserID','$Money');";
//   if(mysqli_query($con,$sql))
//   {}  


$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
if(true){	


$res = mysqli_query($con,"SELECT SendMoneyPerc FROM OrdersJiblerpercentage");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                                       
											$SendMoneyPerc = $row["SendMoneyPerc"];
											
                                                               
                                        }

$SendMoneyPercww = ($SendMoneyPerc * $Money / 100); 
$Moneym = $Money - $SendMoneyPercww;
$sql="UPDATE Users SET Balance = Balance + $Moneym WHERE UserID = $ReceiverID";
  if(mysqli_query($con,$sql))
  {}

$sql="UPDATE Users SET Balance = Balance - $Money WHERE UserID = $UserID";
  if(mysqli_query($con,$sql))
  {}

	$res = mysqli_query($con,"SELECT name,UserPhoto,LANG,Email FROM Users WHERE UserID = $UserID");
	while($row = mysqli_fetch_assoc($res)){
		
		$SenderName  = $row["name"];
		$SenderPhoto = $row["UserPhoto"]; 
		$LANG        = $row["LANG"];
		$SenderEmai  = $row["Email"];

	}
	
	$res = mysqli_query($con,"SELECT name,UserFirebaseToken,UserPhoto,LANG,Email FROM Users WHERE UserID = $ReceiverID");
	while($row = mysqli_fetch_assoc($res)){
		
		$RecieverName 		= $row["name"];
		$ReceiverPhoto      = $row["UserPhoto"];
		$UserFirebaseToken  = $row["UserFirebaseToken"];
		$LANG               = $row["LANG"];
		$ResEmai            = $row["Email"]; 

	}
	
	
	
	
	
	
//	$mes = $SenderName . " sent you $Money MAD";
	
//	send_notification2($UserFirebaseToken,$mes);
	
	
	               if($LANG == "EN"){
                        $Title = "QOON Pay";
                        $messagebody = $SenderName . " sent you " . $Money . " MAD";
                    } else if($LANG == "FR"){
                        $Title = "QOON Pay";
                        $messagebody = $SenderName . " vous a envoyé " . $Money . " MAD";
                    } else {
                        $Title = "QOON Pay";
                        $messagebody = $SenderName . " أرسل لك " . $Money . " درهم ";
                    }

	      
	
	newNotfi($UserFirebaseToken,$Title,$messagebody,$accessToken,$ProgID);
	
//	send_notification_all($UserFirebaseToken,'Money Send',$mes,$accessToken,$ProgID);
	
$sql="INSERT INTO SendMoneyTransactions (UserID,TotalMoney,CuttenMoney) VALUES ('$UserID','$Money','$SendMoneyPercww');";
  if(mysqli_query($con,$sql))
  {}
$sql="UPDATE Money SET TotalIncome=TotalIncome+$SendMoneyPercww,BalanceTraComm=BalanceTraComm+$SendMoneyPercww";
			   if(mysqli_query($con,$sql))
			   {}

$sql="INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees) VALUES ('$UserID','$Money','QOON Pay','$SenderName','$SenderPhoto','0','0','$RecieverName','$ReceiverPhoto','less','$SendMoneyPercww');";
  if(mysqli_query($con,$sql))
  {
	     
	  $TransID = $con->insert_id;
	  
	  
	  
	  
	  
	  
	  
	    //////////////////////////////////
         
         
         $subjectFR = "Reçu QOON Pay 💸";
$from    = "info@qoon.app";

$messageFR = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu QOON Pay</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
  body{background:#f7f8fa;font-family:"Inter",sans-serif;margin:0;padding:40px 0;text-align:center;}
  .receipt-card{max-width:420px;background:#fff;padding:28px;border-radius:18px;box-shadow:0 8px 26px rgba(0,0,0,0.08);margin:0 auto;text-align:center;}
  .logo{width:120px;display:block;margin:0 auto 18px;}
  .amount{font-size:36px;font-weight:700;color:#111;margin-bottom:6px;}
  .sub{font-size:14px;color:#6b7280;margin-bottom:20px;}
  
  .profiles-table{width:100%; margin:18px 0; text-align:center;}
  .profile-left, .profile-right { text-align:center; vertical-align:middle; }
  .profile-left img, .profile-right img{
    width:72px;
    height:72px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid #e5e7eb;
    display:block;
    margin:0 auto;
  }
  .profile-left .name, .profile-right .name{
    font-size:14px;
    font-weight:600;
    color:#111;
    margin-top:8px;
    text-align:center;
    display:block;
    width:100%;
    line-height:1.4;
  }

  .arrow-cell{width:60px;text-align:center;font-size:30px;color:#4f46e5;}
  .info-box{background:#f4f6ff;padding:16px 18px;border-radius:12px;margin-top:10px;font-size:14px;color:#333;width:90%;margin:0 auto;text-align:center;}
  table.info{width:100%;border-collapse:collapse;margin:0 auto;}
  td.label{text-align:left;color:#6b7280;padding:6px 0;}
  td.value{text-align:right;font-weight:500;padding:6px 0;}
  .status{color:#059669;font-weight:600;}
  .footer{font-size:12px;text-align:center;padding-top:14px;color:#9ca3af;}
  
  @media (max-width:380px){
    .profiles-table td{display:block;width:100%;text-align:center;}
    .arrow-cell{display:block;margin:10px 0;}
    .profile-left .name, .profile-right .name{display:block;}
  }
</style>
</head>
<body>

<div class="receipt-card">

<img
  src="https://qoon.app/userDriver/UserDriverApi/logos/QOONLOGO8.png"
  alt="Logo QOON"
  class="logo"
  style="height:50px; width:auto; display:block; margin:0 auto;">
  <br>
  <div class="amount">'.$Money.' MAD</div>
  
  <div class="sub">Transfert instantané effectué</div>

  <!-- profils : gauche = émetteur, centre = flèche, droite = bénéficiaire -->
  <table class="profiles-table" role="presentation" cellpadding="0" cellspacing="0" align="center">
    <tr>
      <td class="profile-left">
        <img src="'.$SenderPhoto.'" alt="Expéditeur">
        <div class="name">@'.$SenderName.'</div>
      </td>
    
      <td class="arrow-cell" style="vertical-align:middle; text-align:center;">
      </td>
    
      <td class="profile-right">
        <img src="'.$ReceiverPhoto.'" alt="Bénéficiaire">
        <div class="name">@'.$RecieverName.'</div>
      </td>
    </tr>
  </table>

  <div class="info-box">
    <table class="info" role="presentation" cellpadding="0" cellspacing="0">
     <tr>
      <td class="label">Émetteur</td>
      <td class="value"><b>'.$SenderName.'</b></td>
    </tr>
    <tr>
      <td class="label">Bénéficiaire</td>
      <td class="value"><b>'.$RecieverName.'</b></td>
    </tr>
      <tr>
        <td class="label">Montant du transfert</td>
        <td class="value">'.$Money.' MAD</td>
      </tr>
      <tr>
        <td class="label">Frais</td>
        <td class="value">'.$SendMoneyPercww.' MAD</td>
      </tr>
      <tr>
        <td class="label">Montant total</td>
        <td class="value"><b>'.$Money-$SendMoneyPercww.' MAD</b></td>
      </tr>
      <tr>
        <td class="label">Identifiant du transfert</td>
        <td class="value">'.$TransID.'</td>
      </tr>
      <tr>
        <td class="label">Statut</td>
        <td class="value status">✅ Terminé</td>
      </tr>
    </table>
  </div>

  <div class="footer">© '.date("Y").' QOON Pay™ — Transferts numériques instantanés</div>

</div>

</body>
</html>
';









$headersFR  = "From: QOON <".$from.">\r\n";
$headersFR .= "Reply-To: ".$from."\r\n";
$headersFR .= "MIME-Version: 1.0\r\n";
$headersFR .= "Content-Type: text/html; charset=UTF-8\r\n";

$mail = new PHPMailer(true);

try {
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
    $mail->addAddress($ResEmai, $SenderName);
    
    $mail->isHTML(true);
    $mail->CharSet = "UTF-8";
    $mail->Subject = $subjectFR;
    $mail->Body    = $messageFR;

    $mail->send();
} catch (Exception $e) {
    // echo "Error: {$mail->ErrorInfo}";
}


$headersFR  = "From: QOON <".$from.">\r\n";
$headersFR .= "Reply-To: ".$from."\r\n";
$headersFR .= "MIME-Version: 1.0\r\n";
$headersFR .= "Content-Type: text/html; charset=UTF-8\r\n";

$mail = new PHPMailer(true);

try {
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
    $mail->addAddress($SenderEmai, $RecieverName);
    
    $mail->isHTML(true);
    $mail->CharSet = "UTF-8";
    $mail->Subject = $subjectFR;
    $mail->Body    = $messageFR;

    $mail->send();
} catch (Exception $e) {
    // echo "Error: {$mail->ErrorInfo}";
}

         
         
         
         
         
         /////////////////////////////////
	  
	  
	  
	  
	  
  }



$sql="INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees,Type) VALUES ('$ReceiverID','$Money','QOON Pay','$SenderName','$SenderPhoto','0','0','$RecieverName','$ReceiverPhoto','Add funds','$SendMoneyPercww','SENDMONEY');";
  if(mysqli_query($con,$sql))
  {
         
	 
		 
    $message ="Added sucssesfully";
    $success = true;
    $status_code = 200;
	 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$TransID,"message"=>$message));		
  }
  else
  {
	   
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = "added";
	
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

  }


}else{

 	$message ="Error Token Eror";
 	$success = false;
 	$status_code = 200;
 	$result = []; 
 	echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));

}


	function send_notification2($UserFirebaseToken,$Message)
	{
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields =array(
			 'to' => $UserFirebaseToken,
			 'notification'=>array(
			 'title' => "QOON Pay",
			 'body' => $Message,
			  "link"=> "http://sae-marketing.com",
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
	
	
	
		function newNotfi($DriverToken,$Title,$Body,$accessTokenw,$Pid)
	{

		


		$url = 'https://fcm.googleapis.com/v1/projects/'.$Pid.'/messages:send';
		$fields =array(
			 'to' => $DriverToken,
			 'notification'=>array(
				 'title' => $Title, 
				 'body' => $Body)
			);

        $fields = array(
            'message' => array(
                'token' => $DriverToken,
                'notification' => array(
                    'title' => $Title,
                    'body' => $Body
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