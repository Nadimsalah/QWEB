
<?php


$file_path = "uploads/";
$KEY = 'uploaded_file';
$file_path = $file_path . basename( $_FILES[$KEY]['name']);

if(move_uploaded_file($_FILES[$KEY]['tmp_name'], $file_path)) {
    echo "success";
} else{
    echo "fail";
}

$KEY2 = 'Text';
$Text = $_POST['Text'];

/*
$sql="INSERT INTO medicine (DoctorEmail) VALUES ('$Text')";
   if(mysqli_query($con,$sql))
   {
//   echo "successfully";
   $key['Result'] = "successfully";

	
			
				
	
		echo json_encode($key);
   }
   else
   {
 //  echo "Email used before";
   $key['Result'] = "Email used before";
	echo json_encode($key);
   }
  */ 
   die;
mysqli_close($con);


 ?>
   
   
  