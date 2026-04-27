<?php
// ============================================================
// QOON — Secure Profile Photo Upload
// SECURITY: MIME validation, size limit, safe filename,
//           auth required, no executable extensions
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

// CORS: restrict to known origins
secureCors(['https://qoon.app', 'https://www.qoon.app']);

ob_start();

// ── Auth ─────────────────────────────────────────────────────
// Allow cookie-based auth as fallback for web UI
$authUser = requireAuth($con, false);
$UserID   = $authUser ? (int)$authUser['UserID'] : sanitizeInt($_POST['UserID'] ?? $_COOKIE['qoon_user_id'] ?? 0);

if (!$UserID) {
    ob_end_clean();
    echo json_encode(['status_code' => 401, 'success' => false, 'message' => 'Authentication required.']);
    exit;
}

// ── Profile text fields ───────────────────────────────────────
$fullname    = sanitizeString($_POST['fullname']    ?? $_POST['FullName'] ?? '', 100);
$email       = sanitizeEmail($_POST['email']        ?? $_POST['Email']   ?? '');
$PhoneNumber = sanitizeString($_POST['PhoneNumber'] ?? '', 30);
$CityID      = sanitizeInt($_POST['CityID'] ?? 0);

// Try JSON body fallback
if (!$fullname && !isset($_FILES['photo'])) {
    $raw  = file_get_contents('php://input');
    $jd   = json_decode($raw, true) ?? [];
    $fullname    = sanitizeString($jd['fullname']    ?? $jd['FullName'] ?? '', 100);
    $email       = sanitizeEmail($jd['email']        ?? $jd['Email']   ?? '');
    $PhoneNumber = sanitizeString($jd['PhoneNumber'] ?? '', 30);
    $CityID      = sanitizeInt($jd['CityID'] ?? 0);
}

// ── Handle Photo ─────────────────────────────────────────────
$decodedPhoto = null;
$ext          = 'jpg';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    // ── Multipart upload ──────────────────────────────────────
    $check = validateFileUpload($_FILES['photo'], 5 * 1024 * 1024); // 5MB max
    if (!$check['ok']) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => $check['error']]);
        exit;
    }
    $ext          = $check['ext'];
    $decodedPhoto = file_get_contents($_FILES['photo']['tmp_name']);

} elseif (!empty($_POST['PersonalPhoto'] ?? $_POST['Photo'] ?? '')) {
    // ── Base64 from mobile app ────────────────────────────────
    $PersonalPhoto = $_POST['PersonalPhoto'] ?? $_POST['Photo'] ?? '';
    if (preg_match('/^data:image\/(\w+);base64,/', $PersonalPhoto, $match)) {
        $ext           = strtolower($match[1]);
        $PersonalPhoto = substr($PersonalPhoto, strpos($PersonalPhoto, ',') + 1);
    }
    // Validate extension
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExts)) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'Invalid image type.']);
        exit;
    }

    $PersonalPhoto = str_replace(' ', '+', $PersonalPhoto);
    $decodedPhoto  = base64_decode($PersonalPhoto);

    if (!$decodedPhoto || strlen($decodedPhoto) < 100) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'Invalid Base64 image.']);
        exit;
    }
    // Validate decoded content is actually an image
    if (!@imagecreatefromstring($decodedPhoto)) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'Uploaded file is not a valid image.']);
        exit;
    }
}

// ── Update text fields only (no photo) ───────────────────────
if (!$decodedPhoto) {
    if (!$fullname && !$email && !$PhoneNumber) {
        ob_end_clean();
        echo json_encode(['status_code' => 400, 'success' => false, 'message' => 'No data to update.']);
        exit;
    }

    $stmt = $con->prepare("UPDATE Users SET name=?, Email=?, PhoneNumber=?, CityID=? WHERE UserID=?");
    $stmt->bind_param("sssii", $fullname, $email, $PhoneNumber, $CityID, $UserID);
    if ($stmt->execute()) {
        $res  = $con->query("SELECT * FROM Users WHERE UserID = " . (int)$UserID);
        $user = $res->fetch_assoc();
        if ($user) {
            unset($user['Password']); // Never return password
            $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
        }
        ob_end_clean();
        echo json_encode(['status_code' => 200, 'success' => true, 'data' => $user, 'message' => 'Updated successfully']);
    } else {
        ob_end_clean();
        echo json_encode(['status_code' => 500, 'success' => false, 'message' => 'Database update failed.']);
    }
    exit;
}

// ── Save photo file with safe random name ─────────────────────
// Only allowed extensions + random component to prevent guessing
$allowedExts = ['jpg', 'png', 'gif', 'webp'];
$ext         = in_array($ext, $allowedExts) ? $ext : 'jpg';
$newFilename = 'user_' . $UserID . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
$uploadDir   = __DIR__ . DIRECTORY_SEPARATOR . 'photo' . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true); // 0755 not 0777
}

$savePath = $uploadDir . $newFilename;

if (file_put_contents($savePath, $decodedPhoto) === false) {
    ob_end_clean();
    echo json_encode(['status_code' => 500, 'success' => false, 'message' => 'Failed to save photo.']);
    exit;
}

// ── Update DB ─────────────────────────────────────────────────
global $DomainNamee;
$fullUrlToSave = rtrim($DomainNamee, '/') . '/photo/' . $newFilename;

$fieldsToUpdate = "UserPhoto=?";
$params         = [$fullUrlToSave];
$types          = "s";

if ($fullname)    { $fieldsToUpdate .= ", name=?";        $params[] = $fullname;    $types .= "s"; }
if ($email)       { $fieldsToUpdate .= ", Email=?";       $params[] = $email;       $types .= "s"; }
if ($PhoneNumber) { $fieldsToUpdate .= ", PhoneNumber=?"; $params[] = $PhoneNumber; $types .= "s"; }
if ($CityID)      { $fieldsToUpdate .= ", CityID=?";      $params[] = $CityID;      $types .= "i"; }

$params[] = $UserID;
$types   .= "i";

$stmt = $con->prepare("UPDATE Users SET $fieldsToUpdate WHERE UserID=?");
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $res  = $con->query("SELECT * FROM Users WHERE UserID = " . (int)$UserID);
    $user = $res->fetch_assoc();
    if ($user) {
        unset($user['Password']);
        $user['UserPhoto'] = resolvePhotoUrl($user['UserPhoto'], $user['name']);
        $user['Photo']     = $user['UserPhoto'];
    }

    // Secure cookie (HttpOnly)
    $cookieOpts = ['expires' => time() + 86400 * 30, 'path' => '/',
                   'secure' => true, 'httponly' => true, 'samesite' => 'Strict'];
    setcookie('qoon_user_photo', $newFilename, $cookieOpts);

    ob_end_clean();
    echo json_encode([
        'status_code' => 200, 'success' => true,
        'data'        => $user,
        'photoUrl'    => resolvePhotoUrl($newFilename),
        'filename'    => $newFilename,
        'message'     => 'Updated successfully',
    ]);
} else {
    @unlink($savePath); // Rollback file
    ob_end_clean();
    echo json_encode(['status_code' => 500, 'success' => false, 'message' => 'Database error.']);
}