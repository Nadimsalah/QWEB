<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$Page = $_POST["Page"];
$KeyWord = $_POST["KeyWord"];

$SearchWord = $_POST["SearchWord"];

$UserID = $_POST["UserID"];

$Today = date("l");
$CurrentTime = date("H:i", strtotime($CreatedAtTrips. ' + 1 hours'));

$six_digit_random_number = random_int(100000, 999999);
$sql="UPDATE Users SET sentcode = '$six_digit_random_number' WHERE UserID = $UserID;";
					
				if(mysqli_query($con,$sql))
				{}



if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 20; 
}

$EnglishCategory = "";

$res = mysqli_query($con,"SELECT * FROM Categories WHERE CategoryID = $CategoryID");
                                
                                                $result = array();
                                
                                                while($row = mysqli_fetch_assoc($res)){
													
													$EnglishCategory = $row["EnglishCategory"];
												}




$DistanceValue = 0;
$res = mysqli_query($con,"SELECT * FROM `Distance`");
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;

$DistanceValue = $row["DistanceValue"];

}


if($SearchWord==""){
    $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Shops.Status = 'ACTIVE' HAVING distance <= 10000 ORDER BY priority DESC , distance ASC LIMIT $Page, 20");
}else{
    $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND ShopName LIKE '%$SearchWord%' AND Shops.Status = 'ACTIVE' HAVING distance <= 10000 ORDER BY priority DESC , distance ASC LIMIT $Page, 20");
}


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();

$i = 0 ;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


$ShopID = $row["ShopID"];
$Online = $row["ShopOpen"];


if($ShopID==""){
    $ShopID = 0;
}

//$row["ShopOpen"] = "Closed";



            
        //    $row["ShopOpen"] = "Open";

$result[] = $row;



$test=4;

$isIntrst = 0;




        // $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        // while($row4 = mysqli_fetch_assoc($res4)){
        

        
        // $isIntrst = 1;
        
        // }





            $array = [
            "isFollow" => "no",
            
            ];



        array_splice($result[$i], 24, 25, $array);
        
        
        $result[$i]['Follow'] = $result[$i]['0'];
        unset($result[$i]['0']);
        
      //  $result[$i]['0'] = 'a7a';


    $i++;

}


if($KeyWord==""){
$res2 = mysqli_query($con,"SELECT count(*) FROM Shops WHERE CategoryID ='$CategoryID'"); 
}else{
$res2 = mysqli_query($con,"SELECT count(*) FROM Shops WHERE CategoryID ='$CategoryID' AND ShopName LIKE '%$KeyWord%'"); 

}
while($row2 = mysqli_fetch_assoc($res2)){

//$data = $row[0];

$result2[] = $row2;
$count =  $row2["count(*)"];
//if($count>20){
//$test=4;
//}else{
//	$test = 0;
//}

}

//$count = count($result2);
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
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
    



if($SearchWord==""){

$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 100000 ORDER BY priority DESC , distance ASC");


    $result = array();



    while($row = mysqli_fetch_assoc($res)){
    
    //$data = $row[0];
    $result[] = $row;
    
    
    
    $test=4;
    
    }
    
}else{
    
}    
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
   
   
   
   
}
}









die;
mysqli_close($con);
?>