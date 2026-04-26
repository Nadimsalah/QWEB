<?php
require "conn.php";

$UserID      = $_POST["UserID"] ?? null;
$fullname    = $_POST["fullname"] ?? "";
$email       = $_POST["email"] ?? "";
$PhoneNumber = $_POST["PhoneNumber"] ?? "";
$CityID      = $_POST["CityID"] ?? null;

if (!$UserID) {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    if ($data) {
        $UserID      = $data["UserID"] ?? $UserID;
        $fullname    = $data["fullname"] ?? $fullname;
        $email       = $data["email"] ?? $email;
        $PhoneNumber = $data["PhoneNumber"] ?? $PhoneNumber;
        $CityID      = $data["CityID"] ?? $CityID;
    }
}

if (!$UserID) {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "UserID is missing."));
    exit;
}

// Use prepared statements for security
$stmt = $con->prepare("UPDATE Users SET name=?, Email=?, PhoneNumber=?, CityID=? WHERE UserID=?");
$stmt->bind_param("ssssi", $fullname, $email, $PhoneNumber, $CityID, $UserID);

if ($stmt->execute()) {
    $res = mysqli_query($con, "SELECT * FROM Users WHERE UserID=$UserID");
    $user = mysqli_fetch_assoc($res);
    
    if ($user) {
        $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
        
        $message = "Updated successfully";
        $success = true;
        $status_code = 200;
        echo json_encode(array('status_code' => $status_code, 'success' => $success, "data" => $user, "message" => $message));
    } else {
        echo json_encode(array('status_code' => 404, 'success' => false, "message" => "User record not found after update"));
    }
} else {
    $message = "Database Update Error: " . $stmt->error;
    $success = false;
    $status_code = 400;
    echo json_encode(array('status_code' => $status_code, 'success' => $success, "message" => $message));
}

mysqli_close($con);
?>