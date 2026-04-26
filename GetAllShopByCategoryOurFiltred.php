<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$Page = $_POST["Page"];

$UserID = $_POST["UserID"];
$SearchWord = $_POST["SearchWord"];


$KinzMadintySmallProductsID = $_POST["KinzMadintySmallProductsID"];

$BoostPage = 0;

$ShowAdds = false;


$Today = date("l");
$CurrentTime = date("H:i", strtotime($CreatedAtTrips. ' + 1 hours'));


if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 20; 
}

$DistanceValue = 0;
$res = mysqli_query($con,"SELECT * FROM `Distance`");
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;

$DistanceValue = $row["DistanceValue"];

}
$DistanceValue = 100;

if($SearchWord==""){
    $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN ShopsAndKinzCategory ON Shops.ShopID = ShopsAndKinzCategory.ShopID WHERE ShopsAndKinzCategory.KinzMadintySmallProductsID ='$KinzMadintySmallProductsID' AND Type='Our' AND Shops.Status = 'ACTIVE' HAVING distance <= 100000 ORDER BY distance ASC ,priority DESC, BakatID DESC  LIMIT $Page, 20");
    
    if($KinzMadintySmallProductsID=="0"){
    	
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Type='Our' AND Shops.Status = 'ACTIVE' HAVING distance <= 100000 ORDER BY distance ASC ,priority DESC, BakatID DESC LIMIT $Page, 20");
    
    }

}else{
    
    $res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops JOIN ShopsAndKinzCategory ON Shops.ShopID = ShopsAndKinzCategory.ShopID WHERE ShopsAndKinzCategory.KinzMadintySmallProductsID ='$KinzMadintySmallProductsID' AND Type='Our' AND Shops.Status = 'ACTIVE' AND Shops.ShopName LIKE '%$SearchWord%' HAVING distance <= 100000 ORDER BY distance ASC ,priority DESC, BakatID DESC  LIMIT $Page, 20");
    
    if($KinzMadintySmallProductsID=="0"){
    	
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Type='Our' AND Shops.Status = 'ACTIVE' AND Shops.ShopName LIKE '%$SearchWord%' HAVING distance <= 100000 ORDER BY distance ASC ,priority DESC, BakatID DESC LIMIT $Page, 20");
    
    }

    
    
}

//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();


$i = 0;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$row["TypeSlider"] = "Slider";

$ShopID = $row["ShopID"];
$Online = $row["ShopOpen"];



if($row["ShopCover"]==""){
    $row["ShopCover"] = $row["ShopLogo"];
}


		$res6 = mysqli_query($con,"SELECT * FROM ShopTimes WHERE ShopID='$ShopID'");
    
            $result6 = array();

            while($row6 = mysqli_fetch_assoc($res6)){
            
			
			
            //$data = $row[0];
            $result6[] = $row6;
            $Times = $row6["Times"];
            $Day = $row6["Day"];
            
            if($Online=="Closed"){
  
				$row["ShopOpen"] = "Closed";
							
                        break;
            }
            
            
			  if($Today==$Day){
			      
			      if($Times=="Closed"){
                	
							$row["ShopOpen"] = "Closed";
						
                        break;
                    }
				  
				
				  
				  $Split2 = explode("-",$Times);
				  
				  $Start = $Split2[0];
				  $End = $Split2[1];
				  
				  if($End<$Start){
					 
					$End = "23:59";					 
				  }
				  
				  if ($CurrentTime >  $Start && $CurrentTime < $End)
					{
					//	echo 'FondDay';
					 
							
							$row["ShopOpen"] = "Open";
	
					}else{
					    
					    
							
							$row["ShopOpen"] = "Closed";
							
					
					}
				  break;
			  }

            }



//$row["ShopOpen"] = "Closed";


if(($i+1)%3==0){
	
	
	
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostCity='MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
	
		$ShowAdds = true;

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		$row2["ShopCover"] = $row2["BoostPhoto"];

		
		$row2["HasStory"]   =  "YES";
        $row2["StoryCount"] = "1";
		
		$row2["FoodName"] = $row2["ShopName"];
		$row2["FoodPhoto"] = $row2["BoostPhoto"];

		
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		
		
		
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
		}else{
			$row2["OpenType"] = "PRODUCT";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
			
			$ProductID   = $row2["BoostLinkOrProductID"];
			$FoodID   = $row2["BoostLinkOrProductID"];
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
				


$ShopID = $row2["ShopID"];



// $isIntrst = 0;
//         $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
//         while($row4 = mysqli_fetch_assoc($res4)){
        

        
//         $isIntrst = 1;
        
//         }


        // if($isIntrst==0){
             $array = [
            "isFollow" => "no",
            
            ];
        // }else if($isIntrst==1){
        //      $array = [
        //         "isFollow" => "yes",
        //     ];
        // }


        array_splice($result[$i], 40, 41, $array);
        
        $result[$i]['Follow'] = $result[$i]['0'];
        unset($result[$i]['0']);

$result2 = array();
    $res44 = mysqli_query($con,"SELECT * FROM Foods WHERE FoodID ='$ProductID'");




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
    
    
    
       
    array_splice($result[$i], 1010, 1020, array($result2[0]));
		
		        $result[$i]['Product'] = $result[$i]['0'];
        unset($result[$i]['0']);		
		
					
		

		
				
		$i++;
		
		
		
		

		
		break;
		
	}
	
	$BoostPage = $BoostPage + 1;
}


$result[] = $row;



$ShopID = $row["ShopID"];



$isIntrst = 0;
        // $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        // while($row4 = mysqli_fetch_assoc($res4)){
        

        
        // $isIntrst = 1;
        
        // }


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


$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Type='Our' HAVING distance <= 100000 ORDER BY priority DESC , distance ASC"); 
while($row2 = mysqli_fetch_assoc($res2)){

//$data = $row[0];
$result2[] = $row2;
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
	
	if($ShowAdds==false){
		
		
		
		
			$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostCity='MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
	
		$ShowAdds = true;

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		$row2["ShopCover"] = $row2["BoostPhoto"];

		
		$row2["HasStory"]   =  "YES";
        $row2["StoryCount"] = "1";
		
		$row2["FoodName"] = $row2["ShopName"];
		$row2["FoodPhoto"] = $row2["BoostPhoto"];

		
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		
		
		
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
		}else{
			$row2["OpenType"] = "PRODUCT";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
			
			$ProductID   = $row2["BoostLinkOrProductID"];
			$FoodID   = $row2["BoostLinkOrProductID"];
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
				


$ShopID = $row2["ShopID"];



$isIntrst = 0;
        // $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        // while($row4 = mysqli_fetch_assoc($res4)){
        

        
        // $isIntrst = 1;
        
        // }


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

$result2 = array();
    $res44 = mysqli_query($con,"SELECT * FROM Foods WHERE FoodID ='$ProductID'");




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
    
    
    
       
    array_splice($result[$i], 1010, 1020, array($result2[0]));
		
		        $result[$i]['Product'] = $result[$i]['0'];
        unset($result[$i]['0']);		
		
					
		

		
				
		$i++;
		
		
		
		

		
		
		
	}
		
		
		
		
	}
	
	$array = array(
    "allresult" => $count,
    "currentpage" => $Page,
    "hasNextPage" => false,
);
	
	

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	$message ="No data";
    $success = true;
    $status_code = 200;
		$result = []; 
		
		
		if($ShowAdds==false){
		
		
		
		
			$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop LEFT JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostCity='MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
	
		$ShowAdds = true;

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		$row2["ShopCover"] = $row2["BoostPhoto"];

		
		$row2["HasStory"]   =  "YES";
        $row2["StoryCount"] = "1";
		
		$row2["FoodName"] = $row2["ShopName"];
		$row2["FoodPhoto"] = $row2["BoostPhoto"];

		
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		
		
		
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
		}else{
			$row2["OpenType"] = "PRODUCT";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
			
			$ProductID   = $row2["BoostLinkOrProductID"];
			$FoodID   = $row2["BoostLinkOrProductID"];
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
				


$ShopID = $row2["ShopID"];



$isIntrst = 0;
        // $res4 = mysqli_query($con,"SELECT * FROM Following WHERE UserID='$UserID' AND ShopID='$ShopID'");
        
        // while($row4 = mysqli_fetch_assoc($res4)){
        

        
        // $isIntrst = 1;
        
        // }


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

$result2 = array();
    $res44 = mysqli_query($con,"SELECT * FROM Foods WHERE FoodID ='$ProductID'");




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
    
    
    
       
    array_splice($result[$i], 1010, 1020, array($result2[0]));
		
		        $result[$i]['Product'] = $result[$i]['0'];
        unset($result[$i]['0']);		
		
					
		

		
				
		$i++;
		
		
		
		

		
		
		
	}
		
		
		
		
	}	
	
	$array = array(
    "allresult" => $count,
    "currentpage" => $Page,
    "hasNextPage" => false,
);
		
		
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message,"PageObject"=>$array), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>