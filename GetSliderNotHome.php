<?php

require "conn.php";
$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(SliderLat)) * cos(radians(SliderLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(SliderLat)))) AS distance FROM Sliders WHERE INHOME ='No' HAVING distance <= 50 ORDER BY priority DESC , distance ASC");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
    
    
    
    $res = mysqli_query($con,"SELECT * FROM Sliders WHERE DefaultPhoto ='YES'");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}else{
    
    
    
    
    
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
    
    
    
// 	$message ="No data";
//     $success = false;
//     $status_code = 200;
// 		$result = []; 
//   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>