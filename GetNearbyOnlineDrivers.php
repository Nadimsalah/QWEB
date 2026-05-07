<?php
require "conn.php";

$UserLat = $_POST["UserLat"];
$UserLongt = $_POST["UserLongt"];

if($UserLat == "" || $UserLongt == ""){
    echo json_encode(array('status_code' => 400, 'success' => false, "data" => [], "message" => "Missing coordinates"));
    die;
}

$res = mysqli_query($con,"SELECT DriverID, FName, LName, DriverProfileImage, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(CurrentLat)) * cos(radians(CurrentLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(CurrentLat)))) AS distance FROM Drivers WHERE Online ='Online' HAVING distance <= 500 ORDER BY distance ASC LIMIT 10");

$result = array();
while($row = mysqli_fetch_assoc($res)){
    $result[] = $row;
}

echo json_encode(array('status_code' => 200, 'success' => true, "data" => $result, "message" => "Success"));
?>
