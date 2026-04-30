<?php
require "conn.php";

$test=0;

$DriverID = $_POST["DriverID"];

// foreach (getallheaders() as $name => $value) { 
//     if(strtolower($name) == "drivertoken"){
//         $Token = $value;
//     }
//     if(strtolower($name) == "lang"){
//         $lang = $value;
//     }
// }


//  $foundPhone =0;
 
//  $foundToken = "NO";
 
$res = mysqli_query($con,"SELECT count(*) FROM Orders WHERE DelvryId = '$DriverID' AND (OrderState = 'Done' OR OrderState = 'Rated')");

while($row = mysqli_fetch_assoc($res)){
    
   $CountTRIPDONE = $row["count(*)"];
    
}

//  if($foundToken=="FOUND"){

// $res = mysqli_query($con,"SELECT Balance,DriverRate FROM Drivers WHERE DriverID = '$DriverID'");

// $result = array();


// $i = 0;
// while($row = mysqli_fetch_assoc($res)){
	
// 	$Balance    = $row["Balance"];
// 	$DriverRate = $row["DriverRate"];
	
	
	
// 	$DriverRate = number_format( $DriverRate , 2 );
// 	$Hwafez     = number_format( $Hwafez , 2 );
	

	
// }


$res = mysqli_query($con,"SELECT DriverRate,DriverOrdersNum FROM Drivers WHERE DriverID = $DriverID;");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$DriverRate       = $row["DriverRate"];
	$DriverOrdersNum  = $row["DriverOrdersNum"];

}



$res = mysqli_query($con,"SELECT sum(OrderPrice) FROM Orders WHERE DelvryId = '$DriverID' AND (OrderState = 'Done' OR OrderState = 'Rated')");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$Balance       = $row["sum(OrderPrice)"];
	//$DriverOrdersNum  = $row["DriverOrdersNum"];

}


// $res = mysqli_query($con,"SELECT count(*) FROM `Trip` WHERE Trip.DriverID = $DriverID AND Trip.TripStatus = 'TRIPDONE';");

// $result = array();


// $i = 0;
// while($row = mysqli_fetch_assoc($res)){
	
// 	$CountTRIPDONE = $row["count(*)"];

// }
// if($CountAll!=0){
// $Pers = $CountTRIPDONE / $CountAll * 100;
// }else{
//   $Pers = 0; 
// }
// $res = mysqli_query($con,"SELECT Title,MoneyQcaps,CreatedAtDriverWallet FROM DriverWallet WHERE DriverID = '$DriverID'");

// $result = array();

// while($row = mysqli_fetch_assoc($res)){

// //$data = $row[0];




// $result[] = $row;



// $test=4;

// }
/////////////
//echo json_encode(array("result"=>$result));

//$Balance    = "177";
//$DriverRate = "5"; 
$Hwafez     = "0";
$Pers = empty($DriverOrdersNum) ? 0 : ($CountTRIPDONE/$DriverOrdersNum*100);
$DriversLimitMoney = "350";
$qLimit = mysqli_query($con, "SELECT MoneyStopNumber FROM MoneyStop LIMIT 1");
if($qLimit && $r = mysqli_fetch_assoc($qLimit)) {
    if (!empty($r['MoneyStopNumber'])) {
        $DriversLimitMoney = $r['MoneyStopNumber'];
    }
}

$CountAll = $DriverOrdersNum;
//$CountTRIPDONE = "22";





if(true){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"Balance"=>$Balance,"Rate"=>$DriverRate,"Hwafez"=>$Hwafez,"Comp"=>$Pers,"Limit"=>$DriversLimitMoney,"TripNums"=>$CountAll,"TripNumsComp"=>$CountTRIPDONE,"message"=>$message));
}
else{
	$message ="sucssesfully";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"Balance"=>"0","Rate"=>$DriverRate,"Hwafez"=>$Hwafez,"Comp"=>$Pers,"Limit"=>$DriversLimitMoney,"TripNums"=>$CountAll,"TripNumsComp"=>$CountTRIPDONE,"message"=>$message));
}



die;
mysqli_close($con);
?>