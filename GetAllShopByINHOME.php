<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$UserID = $_POST["UserID"];

$BoostPage = 0;

$enterBosts = false;


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE (InHome ='YES' OR Type = 'Our') AND Shops.Status = 'ACTIVE' HAVING distance <= 50 ORDER BY priority DESC , distance ASC");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE (InHome ='YES') AND Shops.Status = 'ACTIVE' HAVING distance <= 50 ORDER BY priority DESC , distance ASC");



$result = array();

$i = 0;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$row["TypeSlider"] = "Slider"; 

if(($i+1)%3==0){
	
	$enterBosts  = true;
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){


		$ShopID      = $row2["ShopID"];
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		$row2["ShopLogo"] =  $row2["BoostPhoto"];
		
		
		
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
		
		
				
		
		
		
		
		$res44 = mysqli_query($con,"SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID WHERE Foods.FoodID ='$ProductID'");

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
				
				
				
				
				}

				
				array_splice($result2[$ii], 90, 100, array($result3));

		$ii++;


		$test=4;

		}
			////////////////////
			
			array_splice($result[$i], 100, 101, array($result2));
				
		$i++;
		
		break;
		
	}
	$BoostPage = $BoostPage + 1;
}



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
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
	
		if($enterBosts==false){
		
		
		
		
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC");

	while($row2 = mysqli_fetch_assoc($res2)){


		$ShopID      = $row2["ShopID"];
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		$row2["ShopLogo"] =  $row2["BoostPhoto"];
		
		
		
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
		
		
				
		
		
		
		
		$res44 = mysqli_query($con,"SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID WHERE Foods.FoodID ='$ProductID'");

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
				
				
				
				
				}

				
				array_splice($result2[$ii], 90, 100, array($result3));

		$ii++;


		$test=4;

		}
			////////////////////
			
			array_splice($result[$i], 100, 101, array($result2));
				
		$i++;
		
		
		
	}
		
		
		
		
		
		
		
		
	}
	
	
	

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
	
	
	if($enterBosts==false){
		
		
		
		
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop JOIN Shops ON BoostsByShop.ShopID = Shops.ShopID WHERE BoostTypeID ='2' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC");

	while($row2 = mysqli_fetch_assoc($res2)){


		$ShopID      = $row2["ShopID"];
		
		$row2["TypeSlider"] = "Boost"; 
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		

		$row2["ShopLogo"] =  $row2["BoostPhoto"];
		
		
		
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
		
		
				
		
		
		
		
		$res44 = mysqli_query($con,"SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID WHERE Foods.FoodID ='$ProductID'");

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
				
				
				
				
				}

				
				array_splice($result2[$ii], 90, 100, array($result3));

		$ii++;


		$test=4;

		}
			////////////////////
			
			array_splice($result[$i], 100, 101, array($result2));
				
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