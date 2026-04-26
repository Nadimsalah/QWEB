<?php

require "conn.php";

$StotyID = $_POST["StoryID"];
$ProductId = $_POST["ProductId"];


$sql="UPDATE ShopStory SET ProductId='$ProductId' WHERE StotyID=$StotyID";
   if(mysqli_query($con,$sql))
   {
	   
	   $test=4;
   }

//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="Updated";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
else{
    
    http_response_code(400);

// Get the new response code
//var_dump(http_response_code());
    
	$message ="Error";
    $success = false;
    $status_code = 400;
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
die;
mysqli_close($con);
?>