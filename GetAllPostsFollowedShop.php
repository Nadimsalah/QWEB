<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;
$UserID   = $_POST["UserID"];


$res = mysqli_query($con,"SELECT * FROM Posts JOIN Shops ON Posts.ShopID=Shops.ShopID LEFT JOIN Foods ON Posts.ProductID=Foods.FoodID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE'");


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Posts ON Shops.ShopID=Posts.ShopID LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID JOIN Following ON Shops.ShopID= Following.ShopID WHERE Following.UserID = '$UserID' AND Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE' HAVING distance <= 50 ORDER BY Posts.PostId DESC , distance ASC");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

       $CreatedAtTrips =   $row["CreatedAtPosts"]; 
    $newDate = date('Y-m-d H:i:s', strtotime($CreatedAtTrips. ' + 1 hours'));
    	 
	  $row["CreatedAtPosts"] = $newDate;


$result[] = $row;

$ProductID = $row["ProductID"];

$PostId    = $row["PostId"];
$ProductID = $row["ProductID"];


$isLiked = 0;
    $res5 = mysqli_query($con,"SELECT * FROM Likes WHERE PostID='$PostId' AND UserID='$UserID'");
        
        while($row5 = mysqli_fetch_assoc($res5)){
        
        //$data = $row[0];
        $result3[] = $row3;
        
        $isLiked = 1;
        
        }



     if($isLiked==0){
             $array = [
            "isLiked" => "no",
            ];
    }else{
         $array = [
            "isLiked" => "yes",
            ];
    }
    
    
    
    /////////////////////
    $res44 = mysqli_query($con,"SELECT * FROM Foods WHERE FoodID ='$ProductID'");

$result2 = array();


$ii = 0;
while($row22 = mysqli_fetch_assoc($res44)){

//$data = $row[0];
$result2[] = $row22;


$FoodID = $row22["FoodID"];



 $res24 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result3 = array();
        
        while($row33 = mysqli_fetch_assoc($res24)){
        
        //$data = $row[0];
        $result3[] = $row33;
        
        $test=4;
        
        }

        
        array_splice($result2[$ii], 9, 10, array($result3));

$ii++;


$test=4;

}
    ////////////////////
    
    
    
    array_splice($result[$i], 100, 101, array($array));
    
    

    
    array_splice($result[$i], 101, 102, array($result2[0]));



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