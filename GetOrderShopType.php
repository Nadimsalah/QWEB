<?php
require "conn.php";



$OrderID  = $_POST["OrderID"];


$Token = "s";


$Type = "Other";
$DriverPhone = "";

	$res = mysqli_query($con,"SELECT Shops.Type,Drivers.DriverPhone FROM  Orders LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID LEFT JOIN Drivers ON Orders.DelvryId = Drivers.DriverID  WHERE Orders.OrderID='$OrderID'");
	while($row = mysqli_fetch_assoc($res)){
		
		
		$Type 			= $row["Type"];
		$DriverPhone = $row["DriverPhone"];

	}
	
	if($Type==null){
		
		$Type = "Other";
	}

   
	   
	   
	   
	
	$message ="Sucssessfuly";
    $success = true;
    $status_code = 200;
	$result = []; 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$Type,"data2"=>$DriverPhone,"message"=>$message));

   
	
	
die;
mysqli_close($con);
?>