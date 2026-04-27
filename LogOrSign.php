<?php
// ============================================================
// QOON — User Login / Signup (LogOrSign.php)
// SECURITY: Prepared statements, bcrypt hashing, rate limiting
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

$ip = getClientIp();

// ── Rate limit: max 10 auth attempts per minute per IP ──────
if (!rateLimit($con, 'auth', $ip, 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please wait a minute.']);
    exit;
}

$mode        = sanitizeString($_POST['Mode'] ?? '');
$accountType = sanitizeString($_POST['AccountType'] ?? 'Email', 20);
$email       = sanitizeEmail($_POST['Email'] ?? '');
$password    = $_POST['Password'] ?? '';          // Don't trim passwords
$firebaseToken = sanitizeString($_POST['UserFirebaseToken'] ?? '', 512);

// Signup / Social fields
$name     = sanitizeString($_POST['name'] ?? 'User', 100);
$phone    = sanitizeString($_POST['UserPhone'] ?? '', 30);
$city     = sanitizeString($_POST['City'] ?? '', 100);
$gender   = sanitizeString($_POST['Gender'] ?? '', 20);
$socialId = sanitizeString($_POST['SocialID'] ?? '', 256);

// Validate password length (min 6 chars)
if ($mode === 'Signup' && $accountType === 'Email' && strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

if (!$con) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// ── Helper: Set cookies and return success response ─────────
function loginSuccess($row) {
    global $con, $firebaseToken;
    $uid    = $row['UserID'];
    $uName  = $row['name'] ?? 'User';
    $uPhoto = $row['UserPhoto'] ?? '';

    // HttpOnly + Secure + SameSite cookies
    $opts = ['expires' => time() + 86400 * 30, 'path' => '/', 'domain' => 'qoon.app',
             'secure' => true, 'httponly' => true, 'samesite' => 'Strict'];

    // In localhost dev mode, don't force secure/domain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        $opts['secure'] = false;
        $opts['domain'] = '';
        $opts['samesite'] = 'Lax';
    }

    setcookie('qoon_user_id',    (string)$uid,  $opts);
    setcookie('qoon_user_name',  $uName,         $opts);
    if (!empty($uPhoto)) setcookie('qoon_user_photo', $uPhoto, $opts);

    // Update Firebase token
    if (!empty($firebaseToken) && $con) {
        $stmt = $con->prepare("UPDATE Users SET UserFirebaseToken=? WHERE UserID=?");
        if ($stmt) { $stmt->bind_param("si", $firebaseToken, $uid); $stmt->execute(); $stmt->close(); }
    }

    echo json_encode(['success' => true, 'message' => 'Logged in successfully', 'UserID' => $uid]);
    exit;
}

// ── Email Login / Signup ────────────────────────────────────
if ($accountType === 'Email') {

    if ($mode === 'Login') {
        $stmt = $con->prepare("SELECT UserID, name, UserPhoto, Password, AccountState FROM Users WHERE Email=? AND AccountType='Email' LIMIT 1");
        if (!$stmt) { echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && verifyPassword($password, $row['Password'], $con, 'Users', 'UserID', $row['UserID'])) {
            if (($row['AccountState'] ?? 'Active') !== 'Active') {
                echo json_encode(['success' => false, 'message' => 'Account suspended.']);
                exit;
            }
            loginSuccess($row);
        } else {
            // Generic message — don't reveal if email exists or not
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
            exit;
        }

    } elseif ($mode === 'Signup') {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
            exit;
        }

        // Check email uniqueness
        $stmt = $con->prepare("SELECT UserID FROM Users WHERE Email=? AND AccountType='Email' LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Email already registered.']);
            exit;
        }
        $stmt->close();

        // Hash the password before storing
        $hashedPassword = hashPassword($password);

        $stmt2 = $con->prepare("INSERT INTO Users (name, Email, Password, PhoneNumber, City, Gender, AccountType, UserFirebaseToken, AccountState) VALUES (?, ?, ?, ?, ?, ?, 'Email', ?, 'Active')");
        if (!$stmt2) { echo json_encode(['success' => false, 'message' => 'Server error']); exit; }
        $stmt2->bind_param("sssssss", $name, $email, $hashedPassword, $phone, $city, $gender, $firebaseToken);
        if ($stmt2->execute()) {
            $newId = $stmt2->insert_id;
            $stmt2->close();
            $row = ['UserID' => $newId, 'name' => $name, 'UserPhoto' => ''];
            loginSuccess($row);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create account.']);
            exit;
        }
    }

// ── Social Login (Google / Apple) ──────────────────────────
} elseif ($accountType === 'Google' || $accountType === 'Apple') {
    if (empty($socialId)) {
        echo json_encode(['success' => false, 'message' => 'Social ID required.']);
        exit;
    }

    $socialField = ($accountType === 'Google') ? 'GoogleID' : 'FaceID';

    $stmt = $con->prepare("SELECT UserID, name, UserPhoto FROM Users WHERE $socialField=? AND AccountType=? LIMIT 1");
    $stmt->bind_param("ss", $socialId, $accountType);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        loginSuccess($row);
    } else {
        // Register new social user
        $stmt2 = $con->prepare("INSERT INTO Users (name, Email, $socialField, AccountType, UserFirebaseToken, AccountState) VALUES (?, ?, ?, ?, ?, 'Active')");
        $stmt2->bind_param("sssss", $name, $email, $socialId, $accountType, $firebaseToken);
        if ($stmt2->execute()) {
            $newId = $stmt2->insert_id;
            $stmt2->close();
            $row = ['UserID' => $newId, 'name' => $name, 'UserPhoto' => ''];
            loginSuccess($row);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create social account.']);
            exit;
        }
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid account type.']);
}
