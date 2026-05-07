<?php
require "conn.php";

$DriverId         = $_POST["DriverID"] ?? $_POST["DriverId"] ?? "";
$DriverEmail      = $_POST["DriverEmail"] ?? "";
$Fname            = $_POST["FName"] ?? $_POST["Fname"] ?? "";
$LName            = $_POST["LName"] ?? "";
$DriverPhone      = $_POST["DriverPhone"] ?? "";
$City             = $_POST["City"] ?? "";
$AGE              = $_POST["AGE"] ?? "";

// Base64 Images
$PersonalPhoto    = $_POST["PersonalPhoto"] ?? "";
$NationalIDPhoto  = $_POST["NationalIDPhoto"] ?? "";
$CarPhoto         = $_POST["CarPhoto"] ?? "";
$licensePhoto     = $_POST["licensePhoto"] ?? "";

if (!$DriverId) {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "Missing Driver ID"));
    exit;
}

$uploadDir = "photo/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

function saveBase64($base64Data, $prefix) {
    if (!$base64Data || strlen($base64Data) < 20) return null;
    global $uploadDir;
    
    $ext = "png";
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        $ext = strtolower($type[1]);
    }
    $decoded = base64_decode($base64Data);
    if (!$decoded) return null;

    $filename = $prefix . "_" . rand(100000, 999999) . "." . $ext;
    $filePath = $uploadDir . $filename;
    
    if (file_put_contents($filePath, $decoded)) {
        return "https://qoon.app/userDriver/UserDriverApi/photo/" . $filename;
    }
    return null;
}

$paths1 = saveBase64($PersonalPhoto, "driver_" . $DriverId);
$paths2 = saveBase64($NationalIDPhoto, "nid_" . $DriverId);
$paths3 = saveBase64($CarPhoto, "car_" . $DriverId);
$paths4 = saveBase64($licensePhoto, "license_" . $DriverId);

$stmt = $con->prepare("UPDATE Drivers SET DriverEmail=?, Fname=?, LName=?, DriverPhone=?, City=?, AGE=?, PersonalPhoto=COALESCE(?, PersonalPhoto), NationalIDPhoto=COALESCE(?, NationalIDPhoto), CarPhoto=COALESCE(?, CarPhoto), licensePhoto=COALESCE(?, licensePhoto) WHERE DriverID=?");
$stmt->bind_param("ssssssssssi", $DriverEmail, $Fname, $LName, $DriverPhone, $City, $AGE, $paths1, $paths2, $paths3, $paths4, $DriverId);

if ($stmt->execute()) {
    $res = mysqli_query($con, "SELECT * FROM Drivers WHERE DriverID=$DriverId");
    $driver = mysqli_fetch_assoc($res);
    
    if (isset($driver['PersonalPhoto'])) $driver['PersonalPhoto'] = resolvePhotoUrl($driver['PersonalPhoto'], $driver['Fname']);
    if (isset($driver['NationalIDPhoto'])) $driver['NationalIDPhoto'] = resolvePhotoUrl($driver['NationalIDPhoto'], $driver['Fname']);
    if (isset($driver['CarPhoto'])) $driver['CarPhoto'] = resolvePhotoUrl($driver['CarPhoto'], $driver['Fname']);
    if (isset($driver['licensePhoto'])) $driver['licensePhoto'] = resolvePhotoUrl($driver['licensePhoto'], $driver['Fname']);

    echo json_encode(array('status_code' => 200, 'success' => true, "data" => $driver, "message" => "Updated successfully"));
} else {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "Database Update Error: " . $stmt->error));
}
?>