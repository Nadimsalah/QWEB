<?php

require "conn.php";
$test=0;

$UserID = $_POST["UserID"];

$Query = "SELECT * FROM UserSearchTable WHERE UserID ='$UserID' order by UserSearchTableID desc limit 5";

$res = mysqli_query($con,$Query);

$result = array();



while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];
$result[] = $row;



$test=4;

}
/////////////
//echo json_encode(array("result"=>$result));






if($test==4 || empty($result)){
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;
	
			
echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
else{
	$message ="No data";
    $success = true;
    $status_code = 200;
		$result = []; 
		
		
		

echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>