<?php
require "conn.php";
$test=0;

foreach (getallheaders() as $name => $value) { 
    if(strtolower($name) == "drivertoken"){
        $Token = $value;
    }
    if(strtolower($name) == "lang"){
        $lang = $value;
    }
}

$DriverID = $_POST["DriverID"];
$Week = $_POST["Week"];


$res = mysqli_query($con,"SELECT OrderPrice, OrderID
FROM Orders
WHERE DelvryId = '$DriverID'
AND YEARWEEK(CreatedAtOrders, 1) = YEARWEEK(NOW() - INTERVAL $Week WEEK, 1)
AND (OrderState = 'Rated' OR OrderState = 'Done')
ORDER BY CreatedAtOrders DESC
");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){


$result[] = $row;

$test=4;


}

if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	    $message ="no data";
    $success = true;
    $status_code = 200;
	
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>