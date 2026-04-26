<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$CategoryID = $_POST["CategoryID"];
$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;
$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;

$Page = $_POST["Page"];

$UserID = $_POST["UserID"];

$ShopID = $_POST["ShopID"];


$Today = date("l");
$CurrentTime = date("H:i", strtotime($CreatedAtTrips. ' + 1 hours'));

if($Page==""){
   $Page =0; 
}else{
   $Page = (int)$Page * 20; 
}


$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Type='Our' AND Shops.ShopID !='$ShopID' AND Shops.Status = 'ACTIVE' HAVING distance <= 25 ORDER BY priority DESC , distance ASC LIMIT $Page, 20");


//$res = mysqli_query($con,"SELECT * FROM Shops WHERE CategoryID ='$CategoryID'");
//$res = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLongt)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' HAVING distance <= 50 || distance=NULL ORDER BY CategoryID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];


$ShopID = $row["ShopID"];

$Online = $row["ShopOpen"];



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

$result[] = $row;


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


$res2 = mysqli_query($con,"SELECT *, (6372.797 * acos(cos(radians($UserLat)) * cos(radians(ShopLat)) * cos(radians(ShopLongt) - radians($UserLongt)) + sin(radians($UserLat)) * sin(radians(ShopLat)))) AS distance FROM Shops WHERE CategoryID ='$CategoryID' AND Type='Our' HAVING distance <= 25 ORDER BY priority DESC , distance ASC"); 
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