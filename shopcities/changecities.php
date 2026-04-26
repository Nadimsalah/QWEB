<?php

require "conn.php";
$test=0;

class Point {
    public $x, $y;
 
    public function __construct($x, $y) {
        $this->x = $x;
        $this->y = $y;
    }
}
 
function isInsidePolygon($polygon, $position) {
    $n = count($polygon);
    $crossings = 0;
 
    for ($i = 0; $i < $n; $i++) {
        $j = ($i + 1) % $n;
 
        if (($polygon[$i]->x == $position->x && $polygon[$i]->y == $position->y) ||
            ($polygon[$j]->x == $position->x && $polygon[$j]->y == $position->y)) {
            return true;
        }
 
        if (($polygon[$i]->y > $position->y) == ($polygon[$j]->y > $position->y)) {
            continue;
        }
 
        if ($position->y >= min($polygon[$i]->y, $polygon[$j]->y) && $position->y <= max($polygon[$i]->y, $polygon[$j]->y)) {
            $xCrossing = ($position->y - $polygon[$i]->y) * ($polygon[$j]->x - $polygon[$i]->x) / ($polygon[$j]->y - $polygon[$i]->y) + $polygon[$i]->x;
 
            if ($position->x < $xCrossing) {
                $crossings++;
            }
        }
    }
 
    return $crossings % 2 == 1;
}

$UserLat = $_POST["UserLat"];
$UserLongt = $_POST["UserLongt"];


$res = mysqli_query($con,"SELECT * FROM `Shops`");

$result = array();


$i = 0;
$Found = "NO";

while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$ShopLat   = $row["ShopLat"];
$ShopLongt = $row["ShopLongt"];
$ShopID    = $row["ShopID"];


	$res44 = mysqli_query($con,"SELECT DeliveryZoneID FROM DeliveryZone");
	while($row44 = mysqli_fetch_assoc($res44)){
		$DeliveryZoneID = $row44["DeliveryZoneID"];
	
		$res2 = mysqli_query($con,"SELECT * FROM CityBoders WHERE DeliveryZoneID = $DeliveryZoneID");

		$result2 = array();
		$i = 0;
		$Found = "NO";

		while($row2 = mysqli_fetch_assoc($res2)){
			
			array_push($result2, new Point($row2["CityLat"], $row2["CityLongt"]));
			
			
		}

		$position = new Point($ShopLat, $ShopLongt);

		if (isInsidePolygon($result2, $position)) {
			$Found = "YES";
			$test=4;
			
			$sql22="UPDATE Shops SET CityID='$DeliveryZoneID' WHERE ShopID='$ShopID'";
			if(mysqli_query($con,$sql22))
				{}
			
			break;
		} else {
			$Found = "NO";
		}
		
	}
	
}
/////////////
//echo json_encode(array("result"=>$result));
if($test==4){
    
    
    
    
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$CityID,"message"=>$message));
}
else{
	$message ="NoSliders";
    $success = true;
    $status_code = 200;
	$result = []; 
	
	$result = "0";
	
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>