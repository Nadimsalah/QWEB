<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$search = $_POST["search"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;
$Page = $_POST["Page"];

$Page = $_POST["Page"];

$UserID = $_POST["UserID"];

if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 20; 
}


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE ShopName LIKE '%$search%' AND Shops.Status = 'ACTIVE' ORDER BY distance ASC LIMIT $Page, 20");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();

$i = 0;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;


$ShopID = $row["ShopID"];



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
        
        $result[$i]['Follow'] = $result[$i]['0'];
        unset($result[$i]['0']);
        
    $i++;   


$test=4;

}

$res2 = mysqli_query($con,"SELECT count(*), (6372.797 * acos(cos(radians(29.984)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians(31.4098)) + sin(radians(29.984)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE ShopName LIKE '%$search%' ORDER BY distance ASC"); 
while($row2 = mysqli_fetch_assoc($res2)){

//$data = $row[0];
$count =  $row2["count(*)"];
$test=4;

}

$Page = (int)$Page/20;
$next = 0;
$next = 20 * $Page;
$next = $next +20;
$has = false;

if($next>=$count){
    
    $has = false;
}else{
    $has =  true;
}

$array = array(
    "allresult" => $count,
    "currentpage" => $Page,
    "hasNextPage" => $has,
);

/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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