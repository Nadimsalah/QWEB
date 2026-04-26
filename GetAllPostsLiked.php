<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$UserID = $_POST["UserID"];

$BoostPage = 0;

$Page = $_POST["Page"];

$BoostPage = (int)$Page * 2;
$PageBoost = $Page;
if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 5; 
}

if($Pro==""){
	
	$Pro = "Normal";
}

$res = mysqli_query($con,"SELECT * FROM Posts JOIN Shops ON Posts.ShopID=Shops.ShopID LEFT JOIN Foods ON Posts.ProductID=Foods.FoodID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE'");


$res = mysqli_query($con,"SELECT Shops.*,Posts.*,Foods.*, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Posts ON Shops.ShopID=Posts.ShopID LEFT JOIN Foods ON Posts.ProductID = Foods.FoodID JOIN Likes ON Posts.PostID = Likes.PostID JOIN Categories ON Shops.CategoryID = Categories.CategoryId WHERE Shops.Status = 'ACTIVE' AND Categories.Pro = '$Pro' AND Posts.PostStatus='ACTIVE' AND Likes.UserID = '$UserID' HAVING distance <= 10050 ORDER BY distance ASC , rand() LIMIT $Page, 5");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();



$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


$row["TypeSlider"] = "Slider";

if($row["DeliveryTime"]==null){
	$row["DeliveryTime"] = "";
}
if(($i+1)%3==0){
	
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='5' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='5' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		
		
		

		
		$row2["PostText"] = "";
		$row2["PostPhoto"] = $row2["BoostPhoto"];
		
		$row2["PostLikes"] = "0";
		$row2["Postcomments"] = "0";
		$row2["PostStatus"] = "ACTIVE";
		$row2["lastUpdatedPosts"] = $row2["CreatedAtBoostsByShop"];
		$row2["CreatedAtPosts"] = $row2["CreatedAtBoostsByShop"];
		$row2["FoodID"] = $row2["BoostLinkOrProductID"];
		$row2["ProductID"] = $row2["ProductID"];
		$row2["PostId"] = $row2["BoostsByShopID"] . '-' . $row2["ShopID"];


		$ShopID      = $row2["ShopID"];
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		if($row2["DeliveryTime"]==null){
	$row2["DeliveryTime"] = "";
}
		
		
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
			$row2["ProductID"] = "0";
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
			$row2["ProductID"] = "0";
		}else{
			$row2["OpenType"] = "PRODUCT";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
			$row2["ProductID"] = $row2["ProductID"];
			
			
			$ProductID   = $row2["BoostLinkOrProductID"];
			$ProductID   = $row2["BoostLinkOrProductID"];
		}
		
		$result[] = $row2;
		
        unset($result[$i]['BoostsByShopID']);
		unset($result[$i]['BoostName']);
		unset($result[$i]['BoostTypeID']);
		unset($result[$i]['BoostPrice']);
		unset($result[$i]['BoostTimeDuration']);
		unset($result[$i]['BoostTimeStarted']);
		unset($result[$i]['BoostLat']);
		unset($result[$i]['BoostLongt']);
		unset($result[$i]['BoostCity']);
		unset($result[$i]['BoostPhoto']);
		unset($result[$i]['BoostAction']);
		unset($result[$i]['BoostLinkOrProductID']);
		unset($result[$i]['BoostStatus']);
		unset($result[$i]['lastUpdatedBoostsByShop']);
		unset($result[$i]['CreatedAtBoostsByShop']);
		
		
		
		$PostId    = $row2["BoostsByShopID"] . '-' . $row2["ShopID"];
		


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

$k = 0;

 $res24 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result3 = array();
        
        while($row33 = mysqli_fetch_assoc($res24)){
        
        //$data = $row[0];
        $result3[] = $row33;
        
        $test=4;
        
        
                $ExtraCategoryID = $row33["ExtraCategoryID"];
                
                
                ////////////////
                
                
                $res35 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
                    $result35 = array();
                    
                    while($row35 = mysqli_fetch_assoc($res35)){
                    
                    //$data = $row[0];
                    $result35[] = $row35;
                        
                        
                        
                    }
                
                
                array_splice($result3[$k], 190, 200, array($result35));
            $k++;   
                
                
                /////////////////

        
        }

        
        array_splice($result2[$ii], 90, 100, array($result3));

$ii++;


$test=4;

}
    ////////////////////
    
    
    
    array_splice($result[$i], 100, 101, array($array));
    
    

    
    array_splice($result[$i], 101, 102, array($result2[0]));

		
		
				
		$i++;
		
		break;
		
	}
	
	$BoostPage = $BoostPage + 1;
}


$ShopID = $row["ShopID"];

$res333 = mysqli_query($con,"SELECT count(*) FROM ShopStory WHERE ShopID = '$ShopID' AND StoryStatus = 'ACTIVE'");




while($row333 = mysqli_fetch_assoc($res333)){
	
	$storyCount = $row333["count(*)"];
	$row["StoryCount"] = $storyCount;
	
	if($storyCount==0){
		
		$row["HasStory"] = "NO";
		
	}
	
}

//$data = $row[0];



       $CreatedAtTrips =   $row["CreatedAtPosts"]; 
    $newDate = date('Y-m-d H:i:s', strtotime($CreatedAtTrips. ' + 1 hours'));
    	 
	  $row["CreatedAtPosts"] = $newDate;


$result[] = $row;

$test=4;



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

$k = 0;

 $res24 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result3 = array();
        
        while($row33 = mysqli_fetch_assoc($res24)){
        
        //$data = $row[0];
        $result3[] = $row33;
        
        $test=4;
        
        
                $ExtraCategoryID = $row33["ExtraCategoryID"];
                
                
                ////////////////
                
                
                $res35 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
                    $result35 = array();
                    
                    while($row35 = mysqli_fetch_assoc($res35)){
                    
                    //$data = $row[0];
                    $result35[] = $row35;
                        
                        
                        
                    }
                
                
                array_splice($result3[$k], 19, 20, array($result35));
            $k++;   
                
                
                /////////////////

        
        }

        
        array_splice($result2[$ii], 9, 10, array($result3));

$ii++;


$test=4;

}
    ////////////////////
    
    
    
    array_splice($result[$i], 100, 101, array($array));
    
    

    
    array_splice($result[$i], 101, 102, array($result2[0]));



$i++;

}
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
	
	
	
	
$res = mysqli_query($con,"SELECT count(*) FROM Posts JOIN Shops ON Shops.ShopID=Posts.ShopID JOIN Likes ON Posts.PostID = Likes.PostID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE' AND Likes.UserID = '$UserID'");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count =  $row["count(*)"];

}
	
	
$Page = (int)$Page/5;
$next = 0;
$next = 5 * $Page;
$next = $next +5;
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
	
	
	
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	$message ="No data";
    $success = true;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>