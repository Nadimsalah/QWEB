<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryId = $_POST["CategoryId"];

$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$SearchWord = $_POST["SearchWord"];


$BoostPage = 0;

$KinzMadintySmallProductsID = $_POST["KinzMadintySmallProductsID"];
$ShowAdds = false;


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
if($SearchWord==""){
    if($KinzMadintySmallProductsID==""){
    
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE Shops.Status = 'ACTIVE' AND Shops.CategoryId = $CategoryId GROUP BY Shops.ShopID HAVING distance <= 100000 ORDER BY distance ASC LIMIT $Page, 20");
    
    }else{
    	
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE Shops.Status = 'ACTIVE' AND Shops.CategoryId = $CategoryId AND Foods.KinzMadintySmallProductsID = $KinzMadintySmallProductsID GROUP BY Shops.ShopID HAVING distance <= $DistanceValue ORDER BY distance ASC LIMIT $Page, 20");
    	
    }
}else{
if($KinzMadintySmallProductsID==""){
    
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE Shops.Status = 'ACTIVE' AND Shops.CategoryId = $CategoryId AND (Shops.ShopName LIKE '%$SearchWord%' OR Foods.FoodName LIKE '%$SearchWord%') AND  GROUP BY Shops.ShopID HAVING distance <= 100000 ORDER BY distance ASC LIMIT $Page, 20");
    
    }else{
    	
    	$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE Shops.Status = 'ACTIVE' AND Shops.CategoryId = $CategoryId AND Foods.KinzMadintySmallProductsID = $KinzMadintySmallProductsID AND (Shops.ShopName LIKE '%$SearchWord%' OR Foods.FoodName LIKE '%$SearchWord%') GROUP BY Shops.ShopID HAVING distance <= $DistanceValue ORDER BY distance ASC LIMIT $Page, 20");
    	
    }    
}    
//$res = mysqli_query($con,"SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.ShopID WHERE Foods.TodayOffer ='YES'");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$row["TypeSlider"] = "Slider";

if(($i+1)%3==0){
	
	
	$ShowAdds = true;
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostStatus = 'Active' AND Shops.Status = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND Shops.Status = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		

		
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
				


		
				
		$k = 0;    

 $res2 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result2 = array();
        
        while($row2 = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row2;
        
        $test=4;
        
        
        $ExtraCategoryID = $row2["ExtraCategoryID"];
        
        
        
             $res3 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
            $result3 = array();
            
            while($row3 = mysqli_fetch_assoc($res3)){
            
            //$data = $row[0];
            $result3[] = $row3;
                
                
                
            }
        
        
        array_splice($result2[$k], 200, 201, array($result3));
    $k++;   
        
        }

        
        array_splice($result[$i], 200, 201, array($result2));		
					
		

		
				
		$i++;
		
		
		
		

		
		break;
		
	}
	
	$BoostPage = $BoostPage + 1;
}


$result[] = $row;


$FoodID = $row["FoodID"];






//ProductExtra

$k = 0;    

 $res2 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result2 = array();
        
        while($row2 = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row2;
        
        $test=4;
        
        
        $ExtraCategoryID = $row2["ExtraCategoryID"];
        
        
        
             $res3 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
            $result3 = array();
            
            while($row3 = mysqli_fetch_assoc($res3)){
            
            //$data = $row[0];
            $result3[] = $row3;
                
                
                
            }
        
        
        array_splice($result2[$k], 200, 201, array($result3));
    $k++;   
        
        }

        
        array_splice($result[$i], 200, 201, array($result2));

$i++;


$test=4;

}
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
	
	
		if($ShowAdds==false){
		
		
		
		
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		

		
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
				


		
				
		$k = 0;    

 $res2 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result2 = array();
        
        while($row2 = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row2;
        
        $test=4;
        
        
        $ExtraCategoryID = $row2["ExtraCategoryID"];
        
        
        
             $res3 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
            $result3 = array();
            
            while($row3 = mysqli_fetch_assoc($res3)){
            
            //$data = $row[0];
            $result3[] = $row3;
                
                   
                
            }
        
        
        array_splice($result2[$k], 200, 201, array($result3));
    $k++;   
        
        }

        
        array_splice($result[$i], 200, 201, array($result2));		
					
				
		$i++;
		
		
	}
		
		
	}
	
	
	
	

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	
	if($ShowAdds==false){
		
		
		
		
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID LEFT JOIN Foods ON BoostsByShop.BoostLinkOrProductID=Foods.FoodID WHERE BoostTypeID ='4' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		

		$ShopID         = $row2["ShopID"];
		$BoostsByShopID = $row2["BoostsByShopID"];
		
		

		
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
				


		
				
		$k = 0;    

 $res2 = mysqli_query($con,"SELECT * FROM ExtraCategory WHERE ProductID='$FoodID'");

        $result2 = array();
        
        while($row2 = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row2;
        
        $test=4;
        
        
        $ExtraCategoryID = $row2["ExtraCategoryID"];
        
        
        
             $res3 = mysqli_query($con,"SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
    
            $result3 = array();
            
            while($row3 = mysqli_fetch_assoc($res3)){
            
            //$data = $row[0];
            $result3[] = $row3;
                
                   
                
            }
        
        
        array_splice($result2[$k], 200, 201, array($result3));
    $k++;   
        
        }

        
        array_splice($result[$i], 200, 201, array($result2));		
					
		

		
				
		$i++;
		
		
		
		

		
		
		
	}
		
		
		
		
		
	}
	
	
	
	
	$message ="No data";
    $success = true;
    $status_code = 200;
	//	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
die;
mysqli_close($con);
?>