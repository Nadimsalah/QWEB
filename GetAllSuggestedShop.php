<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$UserID = $_POST["UserID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$UserID = $_POST["UserID"];


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE Shops.Type='Our' AND Shops.Status = 'ACTIVE' HAVING distance <= 50 ORDER BY priority DESC , distance ASC");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;

$ShopID = $row["ShopID"];

 $res2 = mysqli_query($con,"SELECT * FROM ShopStory WHERE ShopID='$ShopID'");

        $result2 = array();
        
        while($row = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row;
        
        $test=4;
        
        }
        
        array_splice($result[$i], 20, 21, array($result2));
        
        
        $isIntrst = 0;
        $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        while($row4 = mysqli_fetch_assoc($res4)){
        

        
        $isIntrst = 1;
        
        }


        if($isIntrst==0){
             $array = [
            "isFollow" => "no",
            
            ];
        }else if($isIntrst==1){
             $array = [
                "isFollow" => "yes",
            ];
        }


        array_splice($result[$i], 40, 41, $array);
        
        $result[$i]['Follow'] = $result[$i]['1'];
        unset($result[$i]['1']);

$i++;


$test=4;

}
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>