<?php

require "conn.php";
$test=0;

$PhoneNumbers = $_POST["PhoneNumbers"];
$UserID       = $_POST["UserID"];

$Contacts = "NO";

if($UserID!=""){
	$res = mysqli_query($con,"SELECT Contacts FROM `Users` WHERE UserID = $UserID");
	while($row = mysqli_fetch_assoc($res)){
		
		$Contacts = $row["Contacts"];
		
	}
	
}

$Contacts = "NO";  // update when work

if($Contacts=="NO"){
$res = mysqli_query($con,"SELECT UserID, name, PhoneNumber, UserPhoto 
FROM `Users` 
WHERE PhoneNumber IN ($PhoneNumbers);");

$result = array();



while($row = mysqli_fetch_assoc($res)){
	
	$ContactsID = $row["UserID"];
	
	if($UserID != ""){
		$sql="INSERT INTO UserContacts (UserID,ContactsID) VALUES ('$UserID','$ContactsID');";
				   if(mysqli_query($con,$sql))
				   {}
	}	   
	
	$result[] = $row;
	$test = 4;
}

}else{
	$res = mysqli_query($con,"SELECT Users.UserID, Users.name, Users.PhoneNumber, Users.UserPhoto 
	FROM `Users` JOIN UserContacts ON Users.UserID = UserContacts.ContactsID
	WHERE UserContacts.UserID = $UserID order by lastUpdatedUserContacts desc");

	$result = array();
	while($row = mysqli_fetch_assoc($res)){
	   
		
		$result[] = $row;
		$test = 4;
		
	}

}


/////////////
//echo json_encode(array("result"=>$result));
if($test==4 || empty($result)){
	
	if($UserID != ""){
		
		$sql="UPDATE Users SET Contacts = 'YES' WHERE UserID = $UserID";
		  if(mysqli_query($con,$sql))
		  {}
	}
	
    $message ="sucssesfully";
    $success = true;
    $status_code = 200;

echo json_encode(array('status_code' => $status_code,'success' => $success, 'data' => $result ,"message"=>$message));
}
else{
	$message ="No data";
    $success = false;
    $status_code = 200;
		$result = []; 
   echo json_encode(array('status_code' => $status_code,'success' => $success  ,"data"=>$result,"message"=>$message));
}
die;
mysqli_close($con);
?>