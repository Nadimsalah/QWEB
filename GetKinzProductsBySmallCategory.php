<?php

require "conn.php";
$test=0;


$KinzMadintySmallProductsID = $_POST["KinzMadintySmallProductsID"];

$res44 = mysqli_query($con,"SELECT * FROM `Foods` JOIN ShopsCategory on Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Shops.CategoryID = Categories.CategoryId JOIN ShopsAndKinzCategory ON Shops.ShopID = ShopsAndKinzCategory.ShopID  WHERE Categories.Type = 'Small' AND ShopsAndKinzCategory.KinzMadintySmallProductsID = '$KinzMadintySmallProductsID'");

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
    
    
    
    






/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result2,"message"=>$message));
}else{
    
    
    
    
    
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}

die;
mysqli_close($con);
?>