<?php

require "conn.php";
$test=0;

$UserID = $_POST["UserID"];


$res = mysqli_query($con,"SELECT Balance FROM `Users` WHERE UserID ='$UserID'");

$result = array();



while($row = mysqli_fetch_assoc($res)){
	
	$Balance = $row["Balance"];
	
	$Balance = round($Balance); 
	$Balance = (string)$Balance;
	
}


$res = mysqli_query($con,"SELECT * FROM UserTransaction WHERE UserID ='$UserID' order by UserTransactionID desc");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

if($row["DistnationName"]=="Jibler"){
	
	$row["DistnationName"] = "Qoon";
}

if($row["UserFees"]=="-"){
	$row["UserFees"] = 0;
}

$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success, 'Balance' => $Balance ,"data"=>$result,"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success, 'Balance' => $Balance  ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>