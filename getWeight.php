<?php
require "conn.php";
$test=0;




$res = mysqli_query($con,"SELECT * FROM Weights WHERE Status= 'ACTIVE'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

$result[] = $row;

$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    

    $message ="FOUND";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	$message ="NOTFOUND";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}




die;
mysqli_close($con);
?>