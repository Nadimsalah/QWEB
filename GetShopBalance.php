<?php

require "conn.php";
$test=0;

$ShopID = $_POST["ShopID"];
$Balance = "0";
$res = mysqli_query($con,"SELECT Balance FROM `Shops` WHERE ShopID = $ShopID");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


        $Balance = $row["Balance"];



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    
    
    
    
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$Balance,"message"=>$message));
}
else{
	$message ="NoSliders";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>