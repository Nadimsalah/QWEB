<?php
require "conn.php";

$DriverId         = $_POST["DriverId"] ?? null;
$Fname            = $_POST["Fname"] ?? "";
$LName           = $_POST["LName"] ?? "";
$DriverEmail      = $_POST["DriverEmail"] ?? "";
$DriverPhone      = $_POST["DriverPhone"] ?? "";
$PersonalPhoto    = $_POST["PersonalPhoto"] ?? "";
$NationalIDPhoto  = $_POST["NationalIDPhoto"] ?? "";
$CarPhoto         = $_POST["CarPhoto"] ?? "";
$licensePhoto     = $_POST["licensePhoto"] ?? "";

if (!$DriverId) {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "DriverId is missing"));
    exit;
}

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }

function saveBase64($base64Data, $prefix) {
    if (!$base64Data) return null;
    global $uploadDir;
    
    $ext = "png";
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        $ext = strtolower($type[1]);
    }
    $decoded = base64_decode($base64Data);
    if (!$decoded) return null;
    
    $filename = $prefix . "_" . rand(1, 999999) . "." . $ext;
    if (file_put_contents($uploadDir . $filename, $decoded)) {
        return $filename;
    }
    return null;
}

$paths1 = saveBase64($PersonalPhoto, "driver_" . $DriverId);
$paths2 = saveBase64($NationalIDPhoto, "nid_" . $DriverId);
$paths3 = saveBase64($CarPhoto, "car_" . $DriverId);
$paths4 = saveBase64($licensePhoto, "lic_" . $DriverId);

// Update Drivers table with the new paths
$stmt = $con->prepare("UPDATE Drivers SET DriverEmail=?, Fname=?, LName=?, PersonalPhoto=COALESCE(?, PersonalPhoto), NationalIDPhoto=COALESCE(?, NationalIDPhoto), CarPhoto=COALESCE(?, CarPhoto), LicensePhoto=COALESCE(?, LicensePhoto) WHERE DriverID=?");
$stmt->bind_param("sssssssi", $DriverEmail, $Fname, $LName, $paths1, $paths2, $paths3, $paths4, $DriverId);

if ($stmt->execute()) {
    $res = mysqli_query($con, "SELECT * FROM Drivers WHERE DriverID=$DriverId");
    $driver = mysqli_fetch_assoc($res);
    
    // Resolve paths for response
    $driver['PersonalPhoto'] = resolvePhotoUrl($driver['PersonalPhoto'], $driver['Fname']);
    
    echo json_encode(array('status_code' => 200, 'success' => true, "data" => $driver, "message" => "Updated successfully"));
} else {
    echo json_encode(array('status_code' => 400, 'success' => false, "message" => "Database Update Error: " . $stmt->error));
}

mysqli_close($con);
?>