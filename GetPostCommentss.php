<?php

require "conn.php";
$test=0;


$PostID = $_POST['PostID']; 

$res = mysqli_query($con,"SELECT * FROM Comments JOIN Users ON Comments.UserID = Users.UserID WHERE Comments.PostID='$PostID' ORDER BY CommentID DESC");

$result = array();


$i = 0;
while($row = mysqli_fetch_assoc($res)){

//$data = $row[0];

       $CreatedAtTrips =   $row["CreatedAtComments"]; 
    $newDate = date('Y-m-d H:i:s', strtotime($CreatedAtTrips. ' + 1 hours'));
    	 
	  $row["CreatedAtComments"] = $newDate;

$result[] = $row;
$CommentID = $row["CommentID"];


$res2 = mysqli_query($con,"SELECT * FROM Replies WHERE CommentID='$CommentID'");

        $result2 = array();
        
        while($row = mysqli_fetch_assoc($res2)){
        
        //$data = $row[0];
        $result2[] = $row;
        
        $test=4;
        
        }

        
        array_splice($result[$i], 20, 21, array($result2));



$test=4;
$i++;
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
    $success = false;
    $status_code = 200;
	$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>