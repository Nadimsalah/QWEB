<?php

require "conn.php";

$pass="a";
$ShopLogName = $_POST["ShopLogName"];
$ShopPassword = $_POST["ShopPassword"];
//$ShopFirebaseToken = $_POST["ShopFirebaseToken"];



$test=0;
$res = mysqli_query($con,"SELECT * FROM Shops WHERE ShopLogName='$ShopLogName' AND ShopPassword='$ShopPassword'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];



$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="loged sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result[0],"message"=>$message));
}
else{
	$message ="Nom d'utilisateur ou mot de passe est incorrect";
    $success = false;
    $status_code = 200;
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
die;

mysqli_close($con);

?>