<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$FoodCatID = $_POST["FoodCatID"];


$res = mysqli_query($con,"SELECT * FROM Foods WHERE FoodCatID ='$FoodCatID'");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;


$FoodID = $row["FoodID"];



//ProductExtra

$k = 0;    

 $res2 = mysqli_query($con,"SELECT * FROM ExtraCategory JOIN ProdExtraCat ON ExtraCategory.ExtraCategoryID = ProdExtraCat.ExtraCategoryID WHERE ProdExtraCat.FoodID='$FoodID'");

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
        
        
        array_splice($result2[$k], 190, 200, array($result3));
    $k++;   
        
        }

        
        array_splice($result[$i], 90, 100, array($result2));

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