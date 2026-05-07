
<?php


$file_path = "uploads/";
$KEY = 'uploaded_file';
$file_path = $file_path . basename( $_FILES[$KEY]['name']);

if(move_uploaded_file($_FILES[$KEY]['tmp_name'], $file_path)) {
    // ---- VIDEO OPTIMIZATION TRICK: Fast Start (moov atom relocation) ----
    // If the file is an mp4 video, we move the moov atom to the front.
    // This allows the video to start playing instantly before it finishes downloading.
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    if ($ext === 'mp4' || $ext === 'mov') {
        $temp_path = $file_path . "_temp." . $ext;
        // Using -c copy is extremely fast because it doesn't re-encode, just moves metadata
        $cmd = "ffmpeg -i " . escapeshellarg($file_path) . " -c copy -movflags +faststart " . escapeshellarg($temp_path) . " 2>&1";
        exec($cmd, $output, $return_var);
        
        // If successful, replace the original file with the optimized one
        if ($return_var === 0 && file_exists($temp_path)) {
            rename($temp_path, $file_path);
        } else {
            // Fallback: remove temp file if ffmpeg failed or isn't installed
            if(file_exists($temp_path)) unlink($temp_path);
        }
    }
    // ---------------------------------------------------------------------
    
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
   
   
  