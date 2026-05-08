<?php

require "conn.php";
$test=0;

$UserId = isset($_POST["UserId"]) ? mysqli_real_escape_string($con, $_POST["UserId"]) : '0';
$Email = isset($_POST["Email"]) ? mysqli_real_escape_string($con, $_POST["Email"]) : '';
$Phone = isset($_POST["Phone"]) ? mysqli_real_escape_string($con, $_POST["Phone"]) : '';

// Resolve Firebase string UID to internal numeric UserID
if (!is_numeric($UserId) && strlen($UserId) > 5) {
    $resolvedId = '0';
    if (!empty($Phone) && $Phone !== '00000000') {
        $check = mysqli_query($con, "SELECT UserID FROM Users WHERE PhoneNumber='$Phone' LIMIT 1");
        if ($r = mysqli_fetch_assoc($check)) $resolvedId = $r['UserID'];
    }
    if ($resolvedId === '0' && !empty($Email) && $Email !== 'user@qoon.app') {
        $check = mysqli_query($con, "SELECT UserID FROM Users WHERE Email='$Email' LIMIT 1");
        if ($r = mysqli_fetch_assoc($check)) $resolvedId = $r['UserID'];
    }
    $UserId = $resolvedId;
}

if ($UserId === '0' || $UserId === 0) {
    echo json_encode(array('status_code' => 200, 'success' => true, 'data' => [], 'message' => "No linked user account found"));
    die;
}

$res = mysqli_query($con,"SELECT Orders.*,Drivers.*,Shops.Type FROM Orders LEFT JOIN Drivers ON Drivers.DriverID = Orders.DelvryId LEFT JOIN Shops ON Shops.ShopID = Orders.ShopID WHERE UserID='$UserId' ORDER BY OrderID DESC");




$result = array();
$i=0;
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




$result3 = array();
$res3 = mysqli_query($con,"SELECT * FROM OrderDetailsOrder Join Foods ON OrderDetailsOrder.FoodID = Foods.FoodID WHERE OrderDetailsOrder.OrderID='$OrderID'");
while($row3 = mysqli_fetch_assoc($res3)){
	$result3[] = $row3;
}




$result[] = $row;

array_splice($result[$i], 1000, 1010, array($result3));
    
$result[$i]["Food"] = $result[$i]["0"];
unset($result[$i]["0"]);



$test=4;
$i++;
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