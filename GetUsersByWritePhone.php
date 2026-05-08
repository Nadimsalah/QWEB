<?php

require "conn.php";
$test=0;

$PhoneNumber = mysqli_real_escape_string($con, $_POST["PhoneNumber"]);

if (strpos($PhoneNumber, '@') !== false) {
    // It's an email
    $res = mysqli_query($con, "SELECT UserID,name,PhoneNumber,UserPhoto FROM `Users` WHERE Email = '$PhoneNumber'");
} else {
    // It's a phone or name
    $res = mysqli_query($con, "SELECT UserID,name,PhoneNumber,UserPhoto FROM `Users` WHERE (PhoneNumber LIKE '%$PhoneNumber%') OR (name LIKE '%$PhoneNumber%') LIMIT 50");
}

$result = array();



while($row = mysqli_fetch_assoc($res)){
	if (isset($row['UserPhoto'])) {
        $row['UserPhoto'] = resolvePhotoUrl($row['UserPhoto'], $row['name']);
    }
	$result[] = $row;
	$test = 4;
}


/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success, 'data' => $result ,"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success, 'Balance' => $Balance  ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>