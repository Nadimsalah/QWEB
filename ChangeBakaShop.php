<?php
require "conn.php";


$ShopID     = $_POST["ShopID"];

$BakatID    = $_POST["BakatID"];




foreach (getallheaders() as $name => $value) { 

	if($name == "Token"){
		
		$Token = $value;
	
	}
	
}


$test=4;

	
	
//	if($test==4 || empty($result)){
	
	$res = mysqli_query($con,"SELECT Bakat.Price FROM Bakat WHERE Bakat.BakatID = '$BakatID'");

		$result = array();



		while($row = mysqli_fetch_assoc($res)){

		//$data = $row[0];
		
		$Price = $row["Price"];
		$result[] = $row;

		$test=4;

		}
		
	$res = mysqli_query($con,"SELECT Balance FROM Bakat WHERE ShopID = '$ShopID '");

		$result = array();



		while($row = mysqli_fetch_assoc($res)){

		//$data = $row[0];
		
		$Balance = $row["Balance"];
		$result[] = $row;

		$test=4;

		}	
	
	
	if($Balance<$Price){
		
		$PaySub = "NO";
		$Status = "NO";
		
	}else{
		
		$PaySub = "YES";
		$Status = "ACTIVE";
		
	}
		
		
	$sql22="UPDATE Shops SET BakatID='$BakatID',PaySub='$PaySub',Status='$Status' WHERE ShopID=$ShopID";
	if(mysqli_query($con,$sql22))
	{	
		
	$message ="Updated sucssesfully";
    $success = true;
    $status_code = 200;
	//$result = []; 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));		
	}
	else
   {
	   
	$message ="Date Used Before";
    $success = false;
    $status_code = 400;
	$result = []; 
	echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
//	echo json_encode($key);
	
   }
   
//	}
	
// 	else{
		
		
// 			$message ="Error Token Eror";
// 			$success = false;
// 			$status_code = 200;
// 			$result = []; 
// 			echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
		
// 	}


   
   
   
   
   
   
die;
mysqli_close($con);
?>