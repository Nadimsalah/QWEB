<?php

require "conn.php";
$test=0;

$OrderID = $_POST["OrderID"];
$res = mysqli_query($con,"SELECT Orders.OrderState,Categories.CategoryId,Categories.ArabCategory,Categories.EnglishCategory,Categories.FrenchCategory FROM Categories JOIN Shops ON Categories.CategoryId = Shops.CategoryId JOIN Orders ON Orders.ShopID = Shops.ShopID WHERE Orders.OrderID = $OrderID");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
if($row["OrderState"]!="Cancelled"){
    $row["CategoryId"] = "0";
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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
die;
mysqli_close($con);
?>