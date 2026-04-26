<?php
 require "conn.php";


$UserID = $_POST["UserID"] ;
 
 
 if($UserID==""){
     
     $UserID = "0";
 }

  $sql="UPDATE Users SET UserFirebaseToken='' WHERE UserID=$UserID";
   

   
   if(mysqli_query($con,$sql))
   {


   
   


   
  
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

