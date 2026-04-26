<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;


$BoostPage = 0;

$sql="INSERT INTO UserOpenApp () VALUES ();";
					
				if(mysqli_query($con,$sql))
				{}
			

$addsNum = 0;
$i = 0;
$result4 = array();

	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC");

	while($row2 = mysqli_fetch_assoc($res2)){
					
					
					
					
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		
		$row2["SliderID"]  = "0";
		
		$row2["SliderPhoto"] = $row2["BoostPhoto"];
		$row2["priority"] = "0";
		$row2["SliderLat"] = "0";
		$row2["SliderLongt"] = "0";
		$row2["ShopID"] = "0";
		$row2["DefaultPhoto"] = "Yes";
		$row2["INHOME"] = "Yes";
		$row2["OpenType"] = "NO";
		$row2["OpenNow"] = "NO";
		$row2["ProductID"] = $row2["BoostLinkOrProductID"];
		$row2["TypeSlider"] = "BOOST";
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
			$ShopID      = $row2["ShopID"];
		}else{
			$row2["OpenType"] = "NO";
			$row2["OpenNow"] = "NO";
		}
		
		$result4[] = $row2;
		
        unset($result4[$i]['BoostsByShopID']);
		unset($result4[$i]['ShopID']);
		unset($result4[$i]['BoostName']);
		unset($result4[$i]['BoostTypeID']);
		unset($result4[$i]['BoostPrice']);
		unset($result4[$i]['BoostTimeDuration']);
		unset($result4[$i]['BoostTimeStarted']);
		unset($result4[$i]['BoostLat']);
		unset($result4[$i]['BoostLongt']);
		unset($result4[$i]['BoostCity']);
		unset($result4[$i]['BoostPhoto']);
		unset($result4[$i]['BoostAction']);
		unset($result4[$i]['BoostLinkOrProductID']);
		unset($result4[$i]['BoostStatus']);
		unset($result4[$i]['lastUpdatedBoostsByShop']);
		unset($result4[$i]['CreatedAtBoostsByShop']);
		unset($result4[$i]['distance']);
		
		
		$ProductID   = $row2["ProductID"];
		
		
		
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
			
			
			
			array_splice($result4[$i], 100, 101, array($result2));
		
		
		$res442 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID =$ShopID");

            $result22 = array();
            
            
            $ii = 0;
            while($row222 = mysqli_fetch_assoc($res442)){
            
            //$data = $row[0];
            $result22[] = $row222;
                
                
            }
        
        array_splice($result4[$i], 900, 1000, array($result22));
		
		$i++;
					
					
					
					
					
					
					
					
				//	$result4[] = $row2;
					
					$addsNum ++;
					
				}

	//	echo $addsNum;
//die;		
			
$countAds = 0; 

$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(SliderLat)) * cos(radians(SliderLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(SliderLat)))) AS distance FROM Sliders WHERE INHOME ='YES' HAVING distance <= 25 ORDER BY priority DESC , distance ASC");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();

$i = 0;

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];




$ShopID      = $row["ShopID"];
$ProductID   = $row["ProductID"];
$row["TypeSlider"] = "Slider"; 

$row["TypeSlider"] = "Slider"; 

//if(false){
if(false){
	
	
	$countAds++;
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		/*
		$row["TypeSlider"] = "BOOST";
		$row["SliderID"]  = "0";
		
		$row["SliderPhoto"] = $row2["BoostPhoto"];
		$row["priority"] = "0";
		$row["SliderLat"] = "0";
		$row["ShopID"] = "0";
		$row["DefaultPhoto"] = "Yes";
		$row["INHOME"] = "Yes";
		$row["OpenType"] = "NO";
		$row["OpenNow"] = "NO";
		$row["ProductID"] = "0";
*/

/*
		array_push($result[], (object)[

			
			"TypeSlider" => "BOOST",
			"SliderID"  => "0",
			"SliderPhoto" => $row2["BoostPhoto"],
			"priority" => "0",
			"SliderLat" => "0",
			"ShopID" => "0",
			"DefaultPhoto" => "Yes",
			"INHOME" => "Yes",
			"OpenType" => "NO",
			"OpenNow" => "NO",
			"ProductID" => "0",
		]);


		*/
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		
		$row2["SliderID"]  = "0";
		
		$row2["SliderPhoto"] = $row2["BoostPhoto"];
		$row2["priority"] = "0";
		$row2["SliderLat"] = "0";
		$row2["SliderLongt"] = "0";
		$row2["ShopID"] = "0";
		$row2["DefaultPhoto"] = "Yes";
		$row2["INHOME"] = "Yes";
		$row2["OpenType"] = "NO";
		$row2["OpenNow"] = "NO";
		$row2["ProductID"] = $row2["BoostLinkOrProductID"];
		$row2["TypeSlider"] = "BOOST";
		
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
			$ShopID      = $row2["ShopID"];
		}else{
			$row2["OpenType"] = "NO";
			$row2["OpenNow"] = "NO";
		}
		
		$result[] = $row2;
		
        unset($result[$i]['BoostsByShopID']);
		unset($result[$i]['ShopID']);
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
		unset($result[$i]['distance']);
		
		
		$ProductID   = $row2["ProductID"];
		
		
		
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
		
		
		$res442 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID =$ShopID");

            $result22 = array();
            
            
            $ii = 0;
            while($row222 = mysqli_fetch_assoc($res442)){
            
            //$data = $row[0];
            $result22[] = $row222;
                
                
            }
        
        array_splice($result[$i], 900, 1000, array($result22));
		
		$i++;
		
		break;
		
	}
	
	$BoostPage = $BoostPage + 1;
}



$result[] = $row;

//  $res2 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID=$ShopID");

//         $result2 = array();
        
//         $test2=0;
//         while($row = mysqli_fetch_assoc($res2)){
        
//         //$data = $row[0];
//         $result2[] = $row;
        
//         $test=4;
        
//         $test2 = 4;
        
//         }
        
//       if($test2==0){
//           $result2 = null;
//       }
        
//         array_splice($result[$i], 10, 11, $result2);



//echo ' lol ' . $ProductID;
    /////////////////////
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
                
                
                array_splice($result3[$k], 200, 201, array($result35));
            $k++;   
        
        
        
        }

        
        array_splice($result2[$ii], 200, 201, array($result3));
        
        
        
        
        
        
$ii++;


$test=4;

}
    ////////////////////
    
    
    
    array_splice($result[$i], 100, 101, array($result2));
    
    
     $res442 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID =$ShopID");

            $result22 = array();
            
            
            $ii = 0;
            while($row222 = mysqli_fetch_assoc($res442)){
            
            //$data = $row[0];
            $result22[] = $row222;
                
                
            }
        
        array_splice($result[$i], 900, 1000, array($result22));

    
    
    
    

		$result4[] = $result[$i];



$test=4;
$i++;
}
/////////////
//echo json_encode(array("result"=>$result), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result4,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
else{
    
	$BoostPage = 0;
    
    $res = mysqli_query($con,"SELECT * FROM Sliders WHERE DefaultPhoto ='YES'");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

$row["TypeSlider"] = "Slider"; 

if(($i+1)%2==0){
	
	
	
	$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' HAVING distance <= 125 UNION SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(BoostLat)) * cos(radians(BoostLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(BoostLat)))) AS distance FROM BoostsByShop WHERE BoostTypeID ='1' AND BoostStatus = 'Active' AND BoostCity = 'MOROCCO' HAVING distance <= 22125 ORDER BY distance ASC LIMIT $BoostPage, 1");

	while($row2 = mysqli_fetch_assoc($res2)){
		/*
		$row["TypeSlider"] = "BOOST";
		$row["SliderID"]  = "0";
		
		$row["SliderPhoto"] = $row2["BoostPhoto"];
		$row["priority"] = "0";
		$row["SliderLat"] = "0";
		$row["ShopID"] = "0";
		$row["DefaultPhoto"] = "Yes";
		$row["INHOME"] = "Yes";
		$row["OpenType"] = "NO";
		$row["OpenNow"] = "NO";
		$row["ProductID"] = "0";
*/

/*
		array_push($result[], (object)[

			
			"TypeSlider" => "BOOST",
			"SliderID"  => "0",
			"SliderPhoto" => $row2["BoostPhoto"],
			"priority" => "0",
			"SliderLat" => "0",
			"ShopID" => "0",
			"DefaultPhoto" => "Yes",
			"INHOME" => "Yes",
			"OpenType" => "NO",
			"OpenNow" => "NO",
			"ProductID" => "0",
		]);


		*/
		
		if($row2['BoostAction']=="PRODUCT"){
			
		}else if($row2['BoostAction']=="LINK"){
			
			$row2["OpenType"] = "LINK";
		}
		
		
		
		$row2["SliderID"]  = "0";
		
		$row2["SliderPhoto"] = $row2["BoostPhoto"];
		$row2["priority"] = "0";
		$row2["SliderLat"] = "0";
		$row2["SliderLongt"] = "0";
		$row2["ShopID"] = "0";
		$row2["DefaultPhoto"] = "Yes";
		$row2["INHOME"] = "Yes";
		if($row2["BoostAction"]=="LINK"){
			$row2["OpenType"] = "LINK";
			$row2["OpenNow"] = $row2["BoostLinkOrProductID"];
		}else if($row2["BoostAction"]=="STORE"){
			$row2["OpenType"] = "SHOP";
			$row2["ShopID"] = $row2["BoostLinkOrProductID"];
			$ShopID      = $row2["ShopID"];
		}else{
			$row2["OpenType"] = "NO";
			$row2["OpenNow"] = "NO";
		}
		$row2["ProductID"] = $row2["BoostLinkOrProductID"];
		$row2["TypeSlider"] = "BOOST";
		
		$result[] = $row2;
		
        unset($result[$i]['BoostsByShopID']);
		unset($result[$i]['ShopID']);
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
		unset($result[$i]['distance']);
		
		$ProductID   = $row2["ProductID"];
		
		
		
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
		
		
		$res442 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID =$ShopID");

            $result22 = array();
            
            
            $ii = 0;
            while($row222 = mysqli_fetch_assoc($res442)){
            
            //$data = $row[0];
            $result22[] = $row222;
                
                
            }
        
        array_splice($result[$i], 900, 1000, array($result22));
		
		
		
		$i++;
		
		break;
		
	}
	
	$BoostPage = $BoostPage + 1;
}





$result[] = $row;





 $ShopID = $row["ShopID"];



//  $res2 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID=$ShopID");

//         $result2 = array();
        
//         $test2=0;
//         while($row = mysqli_fetch_assoc($res2)){
        
//         //$data = $row[0];
//         $result2[] = $row;
        
//         $test=4;
        
//         $test2 = 4;
        
//         }
        
//       if($test2==0){
//           $result2 = null;
//       }
        
//         array_splice($result[$i], 10, 11, $result2);


$ProductID   = $row["ProductID"];



//  $res2 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID=$ShopID");

//         $result2 = array();
        
//         $test2=0;
//         while($row = mysqli_fetch_assoc($res2)){
        
//         //$data = $row[0];
//         $result2[] = $row;
        
//         $test=4;
        
//         $test2 = 4;
        
//         }
        
//       if($test2==0){
//           $result2 = null;
//       }
        
//         array_splice($result[$i], 10, 11, $result2);


    /////////////////////
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


$test=4;



     $res442 = mysqli_query($con,"SELECT * FROM Shops WHERE ShopID =$ShopID");

            $result22 = array();
            
            
            $ii = 0;
            while($row222 = mysqli_fetch_assoc($res442)){
            
            //$data = $row[0];
            $result22[] = $row222;
                
                
            }
        
        array_splice($result[$i], 900, 1000, array($result22));


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
}else{
    
    
    
    
    
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}
}
die;
mysqli_close($con);
?>