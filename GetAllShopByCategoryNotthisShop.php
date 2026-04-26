<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryId"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$Page = $_POST["Page"];
$KeyWord = $_POST["KeyWord"];

$UserID = $_POST["UserID"];

$ShopID = $_POST["ShopID"];

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
$Key = "";

$res = mysqli_query($con,"SELECT * FROM GoogleKey");
                                
                                                $result = array();
                                
                                                while($row = mysqli_fetch_assoc($res)){
													
													$Key = $row["GKey"];
													
												}




if($KeyWord==""){
$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Shops.Status = 'ACTIVE' AND Shops.ShopID !='$ShopID' HAVING distance <= 50 ORDER BY priority DESC , distance ASC LIMIT $Page, 20");
}else{
$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND ShopName LIKE '%$KeyWord%' AND Shops.Status = 'ACTIVE' AND Shops.ShopID !='$ShopID' HAVING distance <= 50 ORDER BY priority DESC , distance ASC LIMIT $Page, 20");
}


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();

$i = 0 ;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;

$ShopID = $row["ShopID"];


$test=4;

$isIntrst = 0;
        $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        while($row4 = mysqli_fetch_assoc($res4)){
        

        
        $isIntrst = 1;
        
        }


        if($isIntrst==0){
            
            $result[$i] ->Follow = ('no');
             $array = [
            "isFollow" => "no",
            
            ];
        }else if($isIntrst==1){
            $result[$i] ->Follow = ('yes');
             $array = [
                "isFollow" => "yes",
            ];
        }




        array_splice($result[$i], 24, 25, $array);
        
        
        $result[$i]['Follow'] = $result[$i]['0'];
        unset($result[$i]['0']);
        
      //  $result[$i]['0'] = 'a7a';


    $i++;

}
if($KeyWord==""){
$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 25 ORDER BY priority DESC , distance ASC"); 
}else{
$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND ShopName LIKE '%$KeyWord%' HAVING distance <= 25 ORDER BY priority DESC , distance ASC"); 

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
    
//	$message ="No data";
//    $success = false;
//    $status_code = 200;
//		$result = []; 
 //  echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
   
   
   $loc = $UserLat . ',' . $UserLongt;
   $x = getShops($EnglishCategory,$Key,$loc,'');

   $arr = json_decode($x, true);
   
   $arrLength = count($arr["results"]);
   $ss = $arr["results"];
   $next_page_token = $arr["next_page_token"];
   
   
   foreach($arr["results"] as $result) {
    $ShopName = $result['name'];
//	echo 'Name :' .$ShopName;
	
	foreach($result['geometry'] as $geometry) {
		
		
		$locations = $geometry['viewport'];
		$ShopLat =  $geometry['lat'];
		$ShopLongt = $geometry['lng'];

		 
	//	echo 'location : '. $ShopLat . ' , ' .  $ShopLongt;
		break;
		
	}
	

	
	//echo ' Rate : '. $result['rating']; 
	$actualpath = '';
	foreach($result['photos'] as $photo) {
		$photow = 'https://maps.googleapis.com/maps/api/place/photo?photoreference='.$photo["photo_reference"].'&sensor=false&maxheight=400&maxwidth=400&key='.$Key;
		$actualpath = $photow;
//		$photo["photo_reference"];
	//	echo ' Photo ' .$actualpath; 
	}
	
 //	echo ' CategoryID ' . $CategoryID;
//	echo  '<br>';
	
	
	//$actualpath = 'https://maps.googleapis.com/maps/api/place/photo?photoreference=AW30NDw8XqjBCrnwmel3PqAQpnk7s1unc3vkSDr8WyyHm0JPik1chBMpyV5eDARARZeMEjpuSmP1iXBj5rRlUNzQsaHquCXC_11Ve0wUXQq7nPHrgJCbZoWdHf9nka2FmcLCJAR2jVw1zsEKfSerabdRQpO1ocJZdNq2WP6CeCoIzudK1WtN&sensor=false&maxheight=400&maxwidth=400&key=AIzaSyC2E7CGzA-t0WDY6VXvnvErSyI-emyMgok';
	
	//$ShopName = 'Shilvanti Patel';
	//echo $ShopName;
//	$ShopLat = floatval($ShopLat);
//	$ShopLongt = floatval($ShopLongt);
	
	//echo ' loc ';
	//echo  $ShopLat . ' ';
	//echo  $ShopLongt;
	
	$ShopName2 = str_replace(" ","",$ShopName);
	
	$sql="INSERT INTO Shops (ShopName,ShopPhone,ShopLogName,ShopPassword,ShopLat,ShopLongt,ShopLogo,ShopCover,CategoryID,Type,AdminID,googleshophkey) VALUES
  ('$ShopName','','$ShopName2','admin',$ShopLat,$ShopLongt,'$actualpath','$actualpath','$CategoryID','Other','1','YES')";
   if(mysqli_query($con,$sql))
   {
	   
	//   echo 'added';
	   
   }else{
	   
	//   echo $con -> error;
   }
   
   
   
   
	
	}



/////////////////second

//	sleep(2000); 

   $loc = $UserLat . ',' . $UserLongt;
   $x = getShops($EnglishCategory,$Key,$loc,$next_page_token);

   $arr = json_decode($x, true);
   
   $arrLength = count($arr["results"]);
   $ss = $arr["results"];
   $next_page_token = $arr["next_page_token"];
   
   
   foreach($arr["results"] as $result) {
    $ShopName = $result['name'];
//	echo 'Name :' .$ShopName;
	
	foreach($result['geometry'] as $geometry) {
		
		
		$locations = $geometry['viewport'];
		$ShopLat =  $geometry['lat'];
		$ShopLongt = $geometry['lng'];

		 
	//	echo 'location : '. $ShopLat . ' , ' .  $ShopLongt;
		break;
		
	}
	

	
	//echo ' Rate : '. $result['rating']; 
	$actualpath = '';
	foreach($result['photos'] as $photo) {
		$photow = 'https://maps.googleapis.com/maps/api/place/photo?photoreference='.$photo["photo_reference"].'&sensor=false&maxheight=400&maxwidth=400&key='.$Key;
		$actualpath = $photow;
//		$photo["photo_reference"];
	//	echo ' Photo ' .$actualpath; 
	}
	
 //	echo ' CategoryID ' . $CategoryID;
//	echo  '<br>';
	
	
	//$actualpath = 'https://maps.googleapis.com/maps/api/place/photo?photoreference=AW30NDw8XqjBCrnwmel3PqAQpnk7s1unc3vkSDr8WyyHm0JPik1chBMpyV5eDARARZeMEjpuSmP1iXBj5rRlUNzQsaHquCXC_11Ve0wUXQq7nPHrgJCbZoWdHf9nka2FmcLCJAR2jVw1zsEKfSerabdRQpO1ocJZdNq2WP6CeCoIzudK1WtN&sensor=false&maxheight=400&maxwidth=400&key=AIzaSyC2E7CGzA-t0WDY6VXvnvErSyI-emyMgok';
	
	//$ShopName = 'Shilvanti Patel';
	//echo $ShopName;
//	$ShopLat = floatval($ShopLat);
//	$ShopLongt = floatval($ShopLongt);
	
	//echo ' loc ';
	//echo  $ShopLat . ' ';
	//echo  $ShopLongt;
	
	$ShopName2 = str_replace(" ","",$ShopName);
	
	$sql="INSERT INTO Shops (ShopName,ShopPhone,ShopLogName,ShopPassword,ShopLat,ShopLongt,ShopLogo,ShopCover,CategoryID,Type,AdminID,googleshophkey) VALUES
  ('$ShopName','','$ShopName2','admin',$ShopLat,$ShopLongt,'$actualpath','$actualpath','$CategoryID','Other','1','YES')";
   if(mysqli_query($con,$sql))
   {
	   
	//   echo 'added';
	   
   }else{
	   
	//   echo $con -> error;
   }
   
   
   
   
	
	}




$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 25 ORDER BY priority DESC , distance ASC");


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
    
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
   
}
   
   
   
   
}
}




function getShops($Category,$Key,$loca,$next_page_token)
	{
		$url = 'https://maps.googleapis.com/maps/api/place/nearbysearch/json?location='.$loca.'&type='.$Category.'&key='.$Key.'&pagetoken='.$next_page_token.'&language=ar&rankby=distance';
		$fields =array(
			 'to' => $tokens,
			 'notification'=>array(
			 'title' => $PostTitle,
			 'body' => $Message)
			);

			$headers = array(
			'Authorization:key=AAAAEDOF67k:APA91bFMPNwvWHetPtqc1i--ztKxrPdSd7ZbTXvrm0LWFV6KHlkw5I-9yOdt6ZtBq1PXo3uVEDcJnFmbAKpNH7tTS9wiKLjAaeLzB0J0KMI6xvsZ5z0C-4Kn98VzSLp_fJs-ibpmOJY2',
			'Content-Type:application/json'
			);

	   $ch = curl_init();
       curl_setopt($ch, CURLOPT_URL, $url);
       curl_setopt($ch, CURLOPT_POST, true);
       curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);  
       curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
       $result = curl_exec($ch);           
       if ($result === FALSE) {
           die('Curl failed: ' . curl_error($ch));
       }
       curl_close($ch);
       return $result;
	}





die;
mysqli_close($con);
?>