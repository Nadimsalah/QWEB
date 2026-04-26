<?php

require "conn.php";
$test=0;

$UserID = $_POST["UserID"];
$ShopID = $_POST["ShopID"];

$res = mysqli_query($con,"SELECT * FROM Shops JOIN Following ON Shops.ShopID = Following.ShopID WHERE Following.UserID ='$UserID' AND Following.ShopID ='$ShopID'");

$result = array();


//echo $UserID.'  ' .$ShopID;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
else{
	$message ="No ";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
die;
mysqli_close($con);
?>