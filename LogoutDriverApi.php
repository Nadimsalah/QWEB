<?php
 require "conn.php";


$DriverID = $_POST["DriverID"] ;
 

  $sql="UPDATE Drivers SET FirebaseDriverToken='' WHERE DriverID=$DriverID";
   

   
   if(mysqli_query($con,$sql))
   {

   $key['Result'] = "success";
   
   
   


   
  
	$message ="Done";
    $success = true;
    $status_code = 200;
 
    echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
   

	
//	echo json_encode($key);
   }
   else
   {

       $message ="Error";
    $success = true;
    $status_code = 200;

    echo json_encode(array('status_code' => $status_code,'success' => $success ,"message"=>$message));
   }
die;
mysqli_close($con);

?>

