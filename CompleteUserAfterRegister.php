<?php
require "conn.php";

// Reconcile input from POST or JSON
$UserID      = $_POST["UserID"] ?? null;
$fullname    = $_POST["fullname"] ?? "";
$PhoneNumber = $_POST["PhoneNumber"] ?? "";
$Gender      = $_POST["Gender"] ?? "";
$BirthDate   = $_POST["BirthDate"] ?? "";
$CityID      = $_POST["CityID"] ?? null;
$PersonalPhoto = $_POST["PersonalPhoto"] ?? "";

if (!$UserID) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if ($data) {
        $UserID      = $data["UserID"] ?? $UserID;
        $fullname    = $data["fullname"] ?? $fullname;
        $PhoneNumber = $data["PhoneNumber"] ?? $PhoneNumber;
        $Gender      = $data["Gender"] ?? $Gender;
        $BirthDate   = $data["BirthDate"] ?? $BirthDate;
        $CityID      = $data["CityID"] ?? $CityID;
        $PersonalPhoto = $data["PersonalPhoto"] ?? $PersonalPhoto;
    }
}

if (!$UserID) {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "UserID is missing."));
    exit;
}

// Handle photo regardless of whether it's sent as Base64 in POST or as a file in multipart form
$decodedPhoto = null;
$ext = "png"; // default

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Handling traditional multipart file upload
    $decodedPhoto = file_get_contents($_FILES['photo']['tmp_name']);
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
} elseif (!empty($PersonalPhoto)) {
    // Handling Base64
    // Strip prefix if present (e.g., "data:image/png;base64,")
    if (preg_match('/^data:image\/(\w+);base64,/', $PersonalPhoto, $type)) {
        $PersonalPhoto = substr($PersonalPhoto, strpos($PersonalPhoto, ',') + 1);
        $ext = strtolower($type[1]);
    }
    $decodedPhoto = base64_decode($PersonalPhoto);
}

if (!$decodedPhoto) {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "No photo data received. Profile completion requires a photo."));
    exit;
}

$photo1name = rand(1, 700000) . rand(1, 700000);
$paths1 = "$photo1name.$ext";

// Use absolute path for file saving
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$absolutePath = $uploadDir . $paths1;

// Use prepared statements for security and consistency with the relative path model
$stmt = $con->prepare("UPDATE Users SET name=?, PhoneNumber=?, Gender=?, BirthDate=?, CityID=?, UserPhoto=? WHERE UserID=?");
$stmt->bind_param("ssssssi", $fullname, $PhoneNumber, $Gender, $BirthDate, $CityID, $paths1, $UserID);

if ($stmt->execute()) {
    // Save the physical file and check for success
    if (file_put_contents($absolutePath, $decodedPhoto) !== false) {
        // Fetch the updated user data
        $res = mysqli_query($con, "SELECT * FROM Users WHERE UserID=$UserID");
        $user = mysqli_fetch_assoc($res);
        
        if ($user) {
            // Resolve the photo URL for the app response
            $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
            
            $message = "Profile completed successfully";
            $success = true;
            $status_code = 200;
            echo json_encode(array('status_code' => $status_code, 'success' => $success, "data" => $user, "message" => $message));
        } else {
            echo json_encode(array('status_code' => 404, 'success' => false, "message" => "User record not found after update"));
        }
    } else {
        $message = "Failed to save photo file to server disk. Check permissions on 'photo' folder.";
        $success = false;
        $status_code = 500;
        echo json_encode(array('status_code' => $status_code, 'success' => $success, "message" => $message));
    }
} else {
    $message = "Database Update Error: " . $stmt->error;
    $success = false;
    $status_code = 400;
    echo json_encode(array('status_code' => $status_code, 'success' => $success, "message" => $message));
}

mysqli_close($con);
?>