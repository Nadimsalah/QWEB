<?php

require "conn.php";
$test=0;

$UserId = $_POST["UserId"];

$res = mysqli_query($con,"SELECT Orders.OrderDetails FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE UserID='$UserId' AND Orders.ShopID = '0' ORDER BY OrderID DESC");




$result = array();

while($row = mysqli_fetch_assoc($res)){

$OrderID = $row["OrderID"];

 if($row["DestinationName"]=="Deliveryservice"){
        
        $row["DestinationName"] = "QOON Express";
    }


//$data = $row[0];


$tags = explode(' ',$row["CreatedAtOrders"]);

$date1 =date_create($tags[0]);

$date2=date_create(date("Y-m-d"));
$diff=date_diff($date2,$date1);
$ss =  $diff->format("%R%a");
$row["TimeToCome"] = $row["ReadyTime"] - $ss;


$res2 = mysqli_query($con,"SELECT * FROM OrdersCancelledRes JOIN CancelOrderReasons ON OrdersCancelledRes.CancelOrderReasonsID= CancelOrderReasons.CancelOrderReasonsID WHERE OrdersCancelledRes.OrderID='$OrderID'");

$row["CancelledReason"] = "";

while($row2 = mysqli_fetch_assoc($res2)){
	$row["CancelledReason"] = $row2["Reason"];
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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	$message ="Error";
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>