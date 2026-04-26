<?php
require_once 'conn.php';
header('Content-Type: application/json');

$mode = $_POST['Mode'] ?? '';
$accountType = $_POST['AccountType'] ?? 'Email';
$email = $_POST['Email'] ?? '';
$password = $_POST['Password'] ?? '';
$firebaseToken = $_POST['UserFirebaseToken'] ?? '';

// Signup / Social fields
$name = $_POST['name'] ?? 'User';
$phone = $_POST['UserPhone'] ?? '';
$city = $_POST['City'] ?? '';
$gender = $_POST['Gender'] ?? '';
$socialId = $_POST['SocialID'] ?? '';

// Setup common response
$response = ['success' => false, 'message' => 'Invalid request'];

if (!$con) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Function to set cookies and return success
function loginSuccess($row) {
    global $con, $firebaseToken;
    $uid = $row['UserID'];
    $uName = $row['name'] ?? 'User';
    $uPhoto = $row['UserPhoto'] ?? '';
    
    // Set cookies for 30 days
    setcookie('qoon_user_id', $uid, time() + (86400 * 30), '/');
    setcookie('qoon_user_name', $uName, time() + (86400 * 30), '/');
    if (!empty($uPhoto)) {
        setcookie('qoon_user_photo', $uPhoto, time() + (86400 * 30), '/');
    }
    
    // Update Firebase token
    if (!empty($firebaseToken)) {
        $stmt = $con->prepare("UPDATE Users SET UserFirebaseToken=? WHERE UserID=?");
        if ($stmt) {
            $stmt->bind_param("si", $firebaseToken, $uid);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Logged in successfully']);
    exit;
}

if ($accountType === 'Email') {
    if ($mode === 'Login') {
        $stmt = $con->prepare("SELECT * FROM Users WHERE Email=? AND Password=? AND AccountType='Email'");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                loginSuccess($row);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
                exit;
            }
        }
    } else if ($mode === 'Signup') {
        // Check if email exists
        $stmt = $con->prepare("SELECT * FROM Users WHERE Email=? AND AccountType='Email'");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
                exit;
            }
        }
        
        // Insert new user
        $stmt2 = $con->prepare("INSERT INTO Users (name, Email, Password, PhoneNumber, City, Gender, AccountType, UserFirebaseToken, AccountState) VALUES (?, ?, ?, ?, ?, ?, 'Email', ?, 'Active')");
        if ($stmt2) {
            $stmt2->bind_param("sssssss", $name, $email, $password, $phone, $city, $gender, $firebaseToken);
            if ($stmt2->execute()) {
                $newId = $stmt2->insert_id;
                $row = ['UserID' => $newId, 'name' => $name, 'UserPhoto' => ''];
                loginSuccess($row);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account']);
                exit;
            }
        }
    }
} else if ($accountType === 'Google' || $accountType === 'Apple') {
    // Social Login or Signup
    $socialField = ($accountType === 'Google') ? 'GoogleID' : 'FaceID';
    
    $stmt = $con->prepare("SELECT * FROM Users WHERE $socialField=? AND AccountType=?");
    if ($stmt) {
        $stmt->bind_param("ss", $socialId, $accountType);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            // User exists, login
            $row = $result->fetch_assoc();
            loginSuccess($row);
        } else {
            // User doesn't exist, register and login
            $stmt2 = $con->prepare("INSERT INTO Users (name, Email, $socialField, AccountType, UserFirebaseToken, AccountState) VALUES (?, ?, ?, ?, ?, 'Active')");
            if ($stmt2) {
                $stmt2->bind_param("sssss", $name, $email, $socialId, $accountType, $firebaseToken);
                if ($stmt2->execute()) {
                    $newId = $stmt2->insert_id;
                    $row = ['UserID' => $newId, 'name' => $name, 'UserPhoto' => ''];
                    loginSuccess($row);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create social account']);
                    exit;
                }
            }
        }
    }
}

echo json_encode($response);
?>
