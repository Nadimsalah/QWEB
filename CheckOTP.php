<?php


  require "conn.php";
  
  $UserID     = $_POST["UserID"];
  $sentcode   = $_POST["sentcode"];
  
  $UserName= "";
  
  $res = mysqli_query($con,"SELECT sentcode FROM Users WHERE UserID = $UserID");

$result = array();

$Nowsentcode = "";

while($row = mysqli_fetch_assoc($res)){
	
	$Nowsentcode = $row["sentcode"];
}
  
  
 if($Nowsentcode==$sentcode){
	 
		$message ="right";
		$success = true;
		$status_code = 200;
		//$result = []; 
		echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
	 
	 
 }else{
	 
	
	 
		$message ="Wrong number";
		$success = false;
		$status_code = 200;
		//$result = []; 
		echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
	 
	 
 }


  

  


?>
