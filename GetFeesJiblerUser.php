<?php

require "conn.php";
$test=0;


$OrderID = $_POST["OrderID"];

$res = mysqli_query($con,"SELECT OrderPriceFromShop FROM Orders WHERE OrderID = $OrderID");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$OrderPriceFromShop = $row["OrderPriceFromShop"];
$test=4;

}

$res22 = mysqli_query($con,"SELECT disUser FROM OrdersJiblerpercentage");
                        
                        
                                        while($row22 = mysqli_fetch_assoc($res22)){
                        
                                       
										$UserFees = $row22["disUser"];
                                                               
                                        }

$fees = $OrderPriceFromShop * $UserFees / 100;

/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
	
	if($fees<3){
		$fees = "3";
	}

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$fees,"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>"0","message"=>$message));
}
die;
mysqli_close($con);
?>