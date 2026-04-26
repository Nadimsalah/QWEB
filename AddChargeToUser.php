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




$test=0;

	$res = mysqli_query($con,"SELECT * FROM Users WHERE UserToken='$Token'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$test=4;

	}
	
if($test==4 || empty($result)){	


$res = mysqli_query($con,"SELECT SendMoneyPerc FROM OrdersJiblerpercentage");
                        
                                        $result = array();
                        
                                        while($row = mysqli_fetch_assoc($res)){
                                       
											$SendMoneyPerc = $row["SendMoneyPerc"];
											
                                                               
                                        }

$SendMoneyPercww = $Money - ($SendMoneyPerc * $Money / 100); 
$MoneyM = $Money - $SendMoneyPercww;
$sql="UPDATE Users SET Balance = Balance + $MoneyM WHERE UserID = $ReceiverID";
  if(mysqli_query($con,$sql))
  {}

$sql="UPDATE Users SET Balance = Balance - $Money WHERE UserID = $UserID";
  if(mysqli_query($con,$sql))
  {}

	$res = mysqli_query($con,"SELECT name,UserPhoto,Email FROM Users WHERE UserID = $UserID");
	while($row = mysqli_fetch_assoc($res)){
		
		$SenderName  = $row["name"];
		$SenderPhoto = $row["UserPhoto"]; 
		$SenderEmai  = $row["Email"];
	}
	
	$res = mysqli_query($con,"SELECT name,UserFirebaseToken,UserPhoto,Email FROM Users WHERE UserID = $ReceiverID");
	while($row = mysqli_fetch_assoc($res)){
		
		$RecieverName 		= $row["name"];
		$ReceiverPhoto      = $row["UserPhoto"];
		$UserFirebaseToken  = $row["UserFirebaseToken"];
		$ResEmai            = $row["Email"]; 
	}
	
	
	
	
	
	
	$mes = $SenderName . " sent you $Money MAD";
	
	send_notification2($UserFirebaseToken,$mes);
	
	
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
	  
  }



$sql="INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees) VALUES ('$ReceiverID','$Money','QOON Pay','$SenderName','$SenderPhoto','0','0','$RecieverName','$ReceiverPhoto','Add funds','$SendMoneyPercww');";
  if(mysqli_query($con,$sql))
  {
         
         
         
         
         //////////////////////////////////
         
         
         $subjectFR = "Reçu QOON Pay 💸";
$from    = "info@qoon.app";

$messageFR = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>QOON Pay Receipt</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    body {
        background: #f7f8fa;
        font-family: "Inter", sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
    }
    .receipt-card {
        width: 390px;
        background: #ffffff;
        padding: 26px;
        border-radius: 18px;
        box-shadow: 0 8px 26px rgba(0,0,0,0.08);
    }
    .logo {
        width: 120px;
        display: block;
        margin: 0 auto 18px;
    }
    .amount {
        font-size: 34px;
        font-weight: 700;
        text-align: center;
        color: #111;
    }
    .sub {
        text-align: center;
        font-size: 14px;
        color: #6b7280;
        margin-bottom: 20px;
    }
    .profiles {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 18px 0;
    }
    .user {
        text-align: center;
    }
    .user img {
        width: 68px;
        height: 68px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #e5e7eb;
    }
    .user-name {
        margin-top: 8px;
        font-size: 14px;
        font-weight: 600;
    }
    .arrow {
        font-size: 24px;
        color: #6366f1;
        font-weight: 700;
    }
    .info-box {
        background: #f4f6ff;
        padding: 14px;
        border-radius: 12px;
        margin-top: 10px;
        font-size: 14px;
        color: #333;
    }
    .row {
        display: flex;
        justify-content: space-between;
        margin: 6px 0;
        font-size: 14px;
    }
    .label {
        color: #6b7280;
    }
    .status {
        color: #059669;
        font-weight: 600;
    }
    .footer {
        font-size: 12px;
        text-align: center;
        padding-top: 14px;
        color: #9ca3af;
    }
</style>
</head>
<body>

<div class="receipt-card">

    <img src="https://qoon.app/userDriver/UserDriverApi/logos/QOONLOGO.png" class="logo">

    <div class="amount">'.$Money.' MAD</div>
    <div class="sub">Instant Transfer Completed</div>

    <div class="profiles">
        <div class="user">
            <img src="'.$SenderPhoto.'">
            <div class="user-name">@'.$SenderName.'</div>
        </div>

        <div class="arrow">➡️</div>

        <div class="user">
            <img src="'.$ReceiverPhoto.'">
            <div class="user-name">@'.$RecieverName.'</div>
        </div>
    </div>

    <div class="info-box">
        <div class="row">
            <span class="label">Transfer Amount</span>
            <span>'.$Money.' MAD</span>
        </div>
        <div class="row">
            <span class="label">Fee</span>
            <span>'.$SendMoneyPercww.' MAD</span>
        </div>
        <div class="row">
            <span class="label">Total Debited</span>
            <span><b>'.$MoneyM.' MAD</b></span>
        </div>
        <div class="row">
            <span class="label">Transfer ID</span>
            <span>'.$TransID.'</span>
        </div>
        <div class="row">
            <span class="label">Status</span>
            <span class="status">✅ Completed</span>
        </div>
    </div>

    <div class="footer">
        © '.date("Y").' QOON Pay™ — Instant Digital Transfers
    </div>

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
	







die;
mysqli_close($con);
?>