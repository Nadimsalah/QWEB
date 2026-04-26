<?php

require "conn.php";
$test=0;

$UserID = $_POST["UserID"];

$res = mysqli_query($con,"SELECT * FROM Users WHERE UserID=$UserID");

$result = array();

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

    $userObj = $result[0];
    if (isset($userObj['UserPhoto'])) {
        $userObj['UserPhoto'] = resolvePhotoUrl($userObj['UserPhoto'], $userObj['name']);
        $userObj['Photo'] = $userObj['UserPhoto']; // Always provide Photo since Flutter might expect it
    }

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$userObj,"message"=>$message));
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