<?php

require "conn.php";
header('Content-Type: application/json; charset=utf-8');

$test=0;

$ShopID = $_POST["ShopID"];

// Removed the UNION script. Reverted to only pulling standard Active ShopStories.
$res = mysqli_query($con,"SELECT * FROM ShopStory WHERE ShopID = '$ShopID' AND StoryStatus = 'ACTIVE'");

$result = array();

while($row = mysqli_fetch_assoc($res)){

    // Native logic kept intact
    // if($row["StotyType"]=='Video'){
    //     if($row["BunnyV"]!='-'){
    //         $row["StoryPhoto"] = $row["BunnyV"];
    //     }
    // }

    if ($row["ProductId"] == "0") {
        $row["ProductId"] = "";
    }

    $foodid = $row["ProductId"];
    $result233 = [];

    if ($foodid != "") {
        $res233 = mysqli_query($con, "SELECT * FROM Foods JOIN ShopsCategory ON Foods.FoodCatID = ShopsCategory.CategoryShopID JOIN Shops ON Shops.ShopID = ShopsCategory.ShopID JOIN Categories ON Categories.CategoryId = Shops.CategoryId WHERE FoodID= $foodid");
        while ($row233 = mysqli_fetch_assoc($res233)) {
            
            // ExtraCategory
            $res2334 = mysqli_query($con, "SELECT * FROM ExtraCategory WHERE ProductID='$foodid'");
            while ($row2334 = mysqli_fetch_assoc($res2334)) {
                $ExtraCategoryID = $row2334["ExtraCategoryID"];

                $result355 = [];
                $res355 = mysqli_query($con, "SELECT * FROM ExtraInSideCategoty WHERE ExtraCategoryID='$ExtraCategoryID'");
                while ($row355 = mysqli_fetch_assoc($res355)) {
                    $result355[] = $row355;
                }
                $row2334["Extras"] = $result355;
                $result2334[] = $row2334;
            }
            $row233["0"] = $result2334;
            $result233[] = $row233;
            
        }
        
        if(!empty($result233)){
             $row["0"] = $result233[0];
        } else {
             $row["0"] = [];
        }
    }

    $result[] = $row;
    $test=4;
}

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
