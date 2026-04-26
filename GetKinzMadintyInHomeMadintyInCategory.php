<?php

require "conn.php";
$test=0;



 $CategoryID = $_POST["CategoryID"];
 $UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
 $UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

 $Page = $_POST["Page"];
 $KeyWord = $_POST["KeyWord"];

// $UserID = $_POST["UserID"];


 if($Page==""){
   $Page =0; 
 }else{
   $Page = (int)$Page * 20; 
 }


if($KeyWord==""){
$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Categories ON Categories.CategoryId = Shops.CategoryID WHERE Categories.Type = 'Small' AND Shops.CategoryID  ='$CategoryID' HAVING distance <= 50 ORDER BY distance ASC LIMIT $Page, 20");
}else{
$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Categories ON Categories.CategoryId = Shops.CategoryID WHERE Categories.Type = 'Small' AND Shops.ShopName LIKE '%$KeyWord%' AND Shops.CategoryID  ='$CategoryID' HAVING distance <= 50 ORDER BY distance ASC LIMIT $Page, 20");
}


$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");

$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Categories ON Categories.CategoryId = Shops.CategoryID WHERE Categories.Type = 'Small' AND Shops.CategoryID  ='$CategoryID' HAVING distance <= 88850 ORDER BY distance ASC LIMIT $Page, 20");


//$res = mysqli_query($con,"SELECT * FROM Shops JOIN Categories ON Categories.CategoryId = Shops.CategoryID WHERE Categories.Type = 'Small' LIMIT 0, 20");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;


$ShopID = $row["ShopID"];



$isIntrst = 0;
        $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='0' AND ShopID='$ShopID'");
        
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


        array_splice($result[$i], 140, 141, $array);
        
        $result[$i]['Follow'] = $result[$i]['0'];
        unset($result[$i]['0']);
        
    $i++;   


$test=4;

}
if($KeyWord==""){
$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 8888 ORDER BY priority DESC , distance ASC"); 
}else{
$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND ShopName LIKE '%$KeyWord%' HAVING distance <= 9999 ORDER BY priority DESC , distance ASC"); 

}
while($row2 = mysqli_fetch_assoc($res2)){

//$data = $row[0];

$result2[] = $row2;
$count =  $row2["count(*)"];
$test=4;

}

$count = count($result2);
$Page = (int)$Page/20;
$next = 0;
$next = 20 * $Page;
$has = false;
$next = $next +20;

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
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array));
}
else{
    
    
    $res = mysqli_query($con,"SELECT * FROM Shops JOIN Categories ON Shops.CategoryID = Categories.CategoryId WHERE Shops.CategoryID ='$CategoryID' AND Categories.DisplayAll='YES'");


    $result = array();



    while($row = mysqli_fetch_assoc($res)){
    
    //$data = $row[0];
    $result[] = $row;
    
    
    
    $test=4;
    
    }
    
    
    if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
    
    
    
    

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array));
}
else{
    
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
   
}

}


die;
mysqli_close($con);
?>