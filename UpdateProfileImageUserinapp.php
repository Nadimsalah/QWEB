<?php
// =============================================================
// QOON - Universal Photo Upload API
// Works from mobile app AND web, stores relative paths in DB
// Compatible with all API naming conventions apps may use:
// UpdateProfileUserWithImageinapp.php
// UpdateProfileUserWithImage.php  
// upload_user_photo.php
// =============================================================
require_once 'conn.php';

// Allow cross-origin requests for local testing against the live server
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');
ob_start();

// ── 1. Get UserID (from POST, JSON body, or Cookie) ──
file_put_contents('upload_debug.txt', "POST: " . print_r($_POST, true) . "\nFILES: " . print_r($_FILES, true));
$UserID        = $_POST['UserID']        ?? $_POST['userId']  ?? $_COOKIE['qoon_user_id'] ?? null;
$fullname      = $_POST['fullname']      ?? $_POST['FullName'] ?? '';
$email         = $_POST['email']         ?? $_POST['Email']   ?? '';
$PhoneNumber   = $_POST['PhoneNumber']   ?? '';
$CityID        = $_POST['CityID']        ?? null;
$PersonalPhoto = $_POST['PersonalPhoto'] ?? $_POST['Photo']   ?? null;

// Try JSON body fallback
if (!$UserID) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if ($data) {
        $UserID        = $data['UserID']        ?? $data['userId']   ?? null;
        $fullname      = $data['fullname']      ?? $data['FullName'] ?? $fullname;
        $email         = $data['email']         ?? $data['Email']    ?? $email;
        $PhoneNumber   = $data['PhoneNumber']   ?? $PhoneNumber;
        $CityID        = $data['CityID']        ?? $CityID;
        $PersonalPhoto = $data['PersonalPhoto'] ?? $data['Photo']    ?? $PersonalPhoto;
    }
}

if (!$UserID) {
    ob_end_clean();
    echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'UserID is required.']);
    exit;
}

// ── 2. Decode Photo ──
$decodedPhoto = null;
$ext = 'jpg';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // Multipart file upload (from web browser / FormData)
    $decodedPhoto = file_get_contents($_FILES['photo']['tmp_name']);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION)) ?: 'jpg';

} elseif ($PersonalPhoto) {
    // Base64 string (from mobile app)
    // Strip data-URL prefix: "data:image/jpeg;base64,..."
    if (preg_match('/^data:image\/(\w+);base64,/', $PersonalPhoto, $match)) {
        $ext = strtolower($match[1]);
        $PersonalPhoto = substr($PersonalPhoto, strpos($PersonalPhoto, ',') + 1);
    }
    $PersonalPhoto = str_replace(' ', '+', $PersonalPhoto); // Fix URL-encoded base64
    $decodedPhoto = base64_decode($PersonalPhoto);
    
    if (!$decodedPhoto || strlen($decodedPhoto) < 100) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'Invalid Base64 image data.']);
        exit;
    }
}

// ── 3. If no photo provided, update text fields only ──
if (!$decodedPhoto) {
    // If we only received UserID from FormData without a photo, it's a failed web upload.
    if (!isset($_POST['fullname']) && !isset($data['fullname'])) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'No valid photo received or file too large.']);
        exit;
    }

    $stmt = $con->prepare("UPDATE Users SET name=?, Email=?, PhoneNumber=?, CityID=? WHERE UserID=?");
    $stmt->bind_param("ssssi", $fullname, $email, $PhoneNumber, $CityID, $UserID);
    
    if ($stmt->execute()) {
        $res = $con->query("SELECT * FROM Users WHERE UserID = $UserID");
        $user = $res->fetch_assoc();
        if ($user) {
            $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
            $user['Photo']     = $user['UserPhoto'];
        }
        ob_end_clean();
        echo json_encode(['status_code' => 200, 'success' => true, 'data' => $user, 'message' => 'Updated successfully']);
    } else {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'DB Error: ' . $stmt->error]);
    }
    exit;
}

// ── 4. Save photo file ──
$newFilename = 'user_' . (int)$UserID . '_' . time() . '.' . $ext;
$uploadDir   = __DIR__ . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$savePath = $uploadDir . $newFilename;

if (file_put_contents($savePath, $decodedPhoto) === false) {
    ob_end_clean();
    echo json_encode(['status_code' => 500, 'success' => false, 'message' => 'Failed to save photo. Check write permissions on /photo/ folder.']);
    exit;
}

// ── 5. Update DB ──
global $DomainNamee;
$fullUrlToSave = rtrim($DomainNamee, '/') . '/photo/' . $newFilename;

$fieldsToUpdate = "UserPhoto=?";
$params = [$fullUrlToSave];
$types = "s";

if ($fullname) { $fieldsToUpdate .= ", name=?";        $params[] = $fullname;    $types .= "s"; }
if ($email)    { $fieldsToUpdate .= ", Email=?";       $params[] = $email;       $types .= "s"; }
if ($PhoneNumber){ $fieldsToUpdate .= ", PhoneNumber=?"; $params[] = $PhoneNumber; $types .= "s"; }
if ($CityID)   { $fieldsToUpdate .= ", CityID=?";     $params[] = $CityID;      $types .= "s"; }

$params[] = (int)$UserID;
$types   .= "i";

$stmt = $con->prepare("UPDATE Users SET $fieldsToUpdate WHERE UserID=?");
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $res  = $con->query("SELECT * FROM Users WHERE UserID = " . (int)$UserID);
    $user = $res->fetch_assoc();
    if ($user) {
        $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
        $user['Photo']     = $user['UserPhoto'];
    }
    
    setcookie('qoon_user_photo', $newFilename, time() + (86400 * 30), '/');
    
    ob_end_clean();
    echo json_encode([
        'status_code' => 200,
        'success'     => true,
        'data'        => $user,
        'photoUrl'    => resolvePhotoUrl($newFilename),
        'filename'    => $newFilename,
        'message'     => 'Updated successfully'
    ]);
} else {
    @unlink($savePath); // Rollback file if DB failed
    ob_end_clean();
    echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'Database error: ' . $stmt->error]);
}
?>