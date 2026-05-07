<?php

require "conn.php";
$test=0;

$DriverID = $_POST["DriverID"];

$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverID=$DriverID");

$result = array();

while($row = mysqli_fetch_assoc($res)){
    $result[] = $row;
    $test=4;
}

if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

    $driver = $result[0];
    if (isset($driver['PersonalPhoto'])) $driver['PersonalPhoto'] = resolvePhotoUrl($driver['PersonalPhoto'], $driver['Fname']);
    if (isset($driver['NationalIDPhoto'])) $driver['NationalIDPhoto'] = resolvePhotoUrl($driver['NationalIDPhoto'], $driver['Fname']);
    if (isset($driver['CarPhoto'])) $driver['CarPhoto'] = resolvePhotoUrl($driver['CarPhoto'], $driver['Fname']);
    if (isset($driver['licensePhoto'])) $driver['licensePhoto'] = resolvePhotoUrl($driver['licensePhoto'], $driver['Fname']);

    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$driver,"message"=>$message));
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