<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;




$res = mysqli_query($con,"SELECT count(*) FROM Posts JOIN Shops ON Shops.ShopID=Posts.ShopID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE' AND (Posts.Video!='' AND Posts.Video!='0')");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count10 =  $row["count(*)"];

}

$res = mysqli_query($con,"SELECT count(*) FROM Shops JOIN ShopStory ON Shops.ShopID=ShopStory.ShopID WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus='ACTIVE' AND ShopStory.StotyType='Video'");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count102 =  $row["count(*)"];

}
	
		$count10 = $count10 + $count102;



$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$UserID = $_POST["UserID"];

if($UserLat==""){
    
  $UserLat  =30.4;
}

if($UserLongt==""){
    
  $UserLongt  =30.4;
}

if($UserID==""){
    
  $UserID  ="1";
}


$BoostPage = 0;

$Page = $_POST["Page"];

$BoostPage = (int)$Page * 2;
$PageBoost = $Page;
if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 5; 
}

$foundrealsbefore = "NO";

$RealID = 0;

if($Page==0){
	$RealID = rand ( 0 , $count10 );
	
	 $sql="UPDATE UserReals SET RealID = '$RealID' WHERE UserID = '$UserID';";
   if(mysqli_query($con,$sql))
   {}
	
}


$res = mysqli_query($con,"SELECT * FROM `UserReals` WHERE UserID = $UserID");
while($row = mysqli_fetch_assoc($res)){
	
	$foundrealsbefore = "YES";
	
	$RealID = $row["RealID"];
}

if($foundrealsbefore=="NO"){
	
	$RealID = rand ( 0 , $count10 );

	
	   $sql="INSERT INTO UserReals (UserID,RealID) VALUES ('$UserID','$RealID');";
   if(mysqli_query($con,$sql))
   {}
	
}


/*
$R = rand(1,10);
if($R==1){
	$orderby = 'Shops.ShopLat';
}else if($R==2){
	$orderby = 'Shops.ShopLongt';
}else if($R==3){
	$orderby = 'Shops.ShopName';
}else if($R==4){
	$orderby = 'Shops.ShopID';
}else if($R==5){
	$orderby = 'Shops.ShopID';
}else if($R==6){
	$orderby = 'distance';
}else if($R==7){
	$orderby = 'Shops.BakatID';
}else if($R==8){
	$orderby = 'Shops.Type';
}else{
	$orderby = 'distance';
}
*/



$res = mysqli_query($con,"SELECT Shops.ShopLat,Shops.ShopLongt,Posts.Video,Posts.BunnyV,Posts.BunnyS,Shops.ShopID,Shops.ShopName,Shops.ShopLogo,Shops.ShopCover,Posts.ProductID,Posts.PostID,Shops.BakatID,Shops.Type,Shops.ShopOpen, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN Posts ON Shops.ShopID=Posts.ShopID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE' AND (Posts.Video!='' AND Posts.Video!='0') HAVING distance <= 10050 UNION SELECT Shops.ShopLat,Shops.ShopLongt,ShopStory.StoryPhoto as Video,ShopStory.BunnyV,ShopStory.BunnyS,Shops.ShopID,Shops.ShopName,Shops.ShopLogo,Shops.ShopCover,ShopStory.ProductId as ProductID,ShopStory.StotyID as PostID,Shops.BakatID,Shops.Type,Shops.ShopOpen,(6372.797 * acos(cos(radians($UserLat)) * cos(radians($UserLongt)) * cos(radians(ShopLongt) - radians(31.7081448)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN ShopStory ON Shops.ShopID=ShopStory.ShopID WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus='ACTIVE' AND ShopStory.StotyType='Video' ORDER BY PostID desc LIMIT $Page, 5");
$res = mysqli_query($con, "SELECT * FROM ( SELECT Shops.ShopLat, Shops.ShopLongt, Posts.Video, Posts.BunnyV, Posts.BunnyS, Shops.ShopID, Shops.ShopName, Shops.ShopLogo, Shops.ShopCover, Posts.ProductID, Posts.PostID, Shops.BakatID, Shops.Type, Shops.ShopOpen, (6372.797 * acos( cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)) )) AS distance, Posts.PostID AS SortID FROM Shops JOIN Posts ON Shops.ShopID = Posts.ShopID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus = 'ACTIVE' AND (Posts.Video != '' AND Posts.Video != '0') UNION ALL SELECT Shops.ShopLat, Shops.ShopLongt, ShopStory.StoryPhoto AS Video, ShopStory.BunnyV, ShopStory.BunnyS, Shops.ShopID, Shops.ShopName, Shops.ShopLogo, Shops.ShopCover, ShopStory.ProductId AS ProductID, ShopStory.StotyID AS PostID, Shops.BakatID, Shops.Type, Shops.ShopOpen, (6372.797 * acos( cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)) )) AS distance, ShopStory.StotyID AS SortID FROM Shops JOIN ShopStory ON Shops.ShopID = ShopStory.ShopID WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus = 'ACTIVE' AND ShopStory.StotyType = 'Video' ) AS all_data HAVING distance <= 10050 ORDER BY SortID DESC LIMIT $Page, 5 ");
$result = array();



$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

	$PostID    = $row["PostID"];
	$ProductID = $row["ProductID"];
	$row["BunnyV"] = $row["Video"];
	//hjhj
	//$row["Video"] = 'https://vz-92ee1d34-d8a.b-cdn.net/5d27e24d-4e85-40d7-a7bb-9025f507f702/play_360p.mp4';
	//$row["Video"] = 'https://vz-92ee1d34-d8a.b-cdn.net/c666a716-e8aa-48ef-917b-e164b3ba7416/play_360p.mp4';
// 	if($row["BunnyV"]!='-'){
// 		$row["Video"] = $row["BunnyV"];
// 	}
	//$row["Thumbnail"] = 'https://vz-92ee1d34-d8a.b-cdn.net/87f94045-225b-4284-8f2c-6e1363de3979/thumbnail.jpg';
//	if($row["BunnyS"]!='-'){
		$row["Thumbnail"]= $row["BunnyS"];
//	}
	$isLiked = 0;
	
	

	$PostId2 = 'r' . $PostID;
	
//	$row["PostID"] = $PostId2;

	
    $res5 = mysqli_query($con,"SELECT * FROM Likes WHERE PostID='$PostId2' AND UserID='$UserID'");
        
        while($row5 = mysqli_fetch_assoc($res5)){
        
        //$data = $row[0];

        
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
	
	$result3 = array();
	$res5 = mysqli_query($con,"SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE FoodID='$ProductID'");
        
		
		
		$ii = 0;
        while($row5 = mysqli_fetch_assoc($res5)){
        
        //$data = $row[0];
        $result3[] = $row5;
		
		$FoodID = $row5["FoodID"];
		
		
				$k = 0;
		 $res24 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

				$result24 = array();
				
				while($row24 = mysqli_fetch_assoc($res24)){
				
				//$data = $row[0];
				$result24[] = $row24;
				$ExtraCategoryID = $row24["ExtraCategoryID"];
						
						
						////////////////
						
						
						$res35 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
			
							$result35 = array();
							
							while($row35 = mysqli_fetch_assoc($res35)){
							
							//$data = $row[0];
							$result35[] = $row35;
								
								
								
							}
						
						
						array_splice($result24[$k], 19, 20, array($result35));
					$k++;   
				
				
				
				
				}

				
				array_splice($result3[$ii], 90, 100, array($result24));

		$ii++;
		
		
		

        
        }

		$row["like"] = $array;
		$row["prod"] = $result3[0];

		$result[] = $row;
		
	//	array_splice($result[$i], 100, 101, array($array));
// 		if($result3[0]==null){
// 		    $result3[0] = null;
// 		 array_splice($result[$i], 200, 201, array($result3[0]));   
// 		}else{
// 		array_splice($result[$i], 200, 201, array($result3[0]));
//         }
$test=4;
$i++;
}








/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
	
	
	
	
$res = mysqli_query($con,"SELECT count(*) FROM Posts JOIN Shops ON Shops.ShopID=Posts.ShopID WHERE Shops.Status = 'ACTIVE' AND Posts.PostStatus='ACTIVE' AND (Posts.Video!='' AND Posts.Video!='0')");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count =  $row["count(*)"];

}

$res = mysqli_query($con,"SELECT count(*) FROM Shops JOIN ShopStory ON Shops.ShopID=ShopStory.ShopID WHERE Shops.Status = 'ACTIVE' AND ShopStory.StoryStatus='ACTIVE' AND ShopStory.StotyType='Video'");



$i = 0;
while($row = mysqli_fetch_assoc($res)){
	
	$count2 =  $row["count(*)"];

}
	
		$count = $count + $count2;
	
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
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>