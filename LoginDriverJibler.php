<?php

require "conn.php";

$pass="a";
$DriverPhone = $_POST["DriverPhone"];
$DriverPassword = $_POST["DriverPassword"];
$FirebaseDriverToken = $_POST["FirebaseDriverToken"];

$test=0;
$res = mysqli_query($con,"SELECT * FROM Drivers WHERE DriverPhone='$DriverPhone' AND DriverPassword='$DriverPassword'");

$result = array();

while($row = mysqli_fetch_assoc($res)){
    $result[] = $row;
    $sql="UPDATE Drivers SET FirebaseDriverToken='$FirebaseDriverToken' WHERE DriverPhone='$DriverPhone'";
    mysqli_query($con,$sql);
    $test=4;
}

if($test==4 && !empty($result)){
    $message ="loged sucssesfully";
    $success = true;
    $status_code = 200;

    $data = isset($result[0]) ? $result[0] : new stdClass();
    if (isset($data['PersonalPhoto'])) $data['PersonalPhoto'] = resolvePhotoUrl($data['PersonalPhoto'], $data['Fname']);
    if (isset($data['NationalIDPhoto'])) $data['NationalIDPhoto'] = resolvePhotoUrl($data['NationalIDPhoto'], $data['Fname']);
    if (isset($data['CarPhoto'])) $data['CarPhoto'] = resolvePhotoUrl($data['CarPhoto'], $data['Fname']);
    if (isset($data['licensePhoto'])) $data['licensePhoto'] = resolvePhotoUrl($data['licensePhoto'], $data['Fname']);

    echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$data,"message"=>$message));
}
else{
	$message ="الجوال او كلمة المرور خاطئ";
    $success = false;
    $status_code = 200;
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
}
die;
mysqli_close($con);
?>