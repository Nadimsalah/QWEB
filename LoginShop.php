<?php
// ============================================================
// QOON — Shop Login (LoginShop.php)
// SECURITY: Prepared statements, bcrypt, rate limiting
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

$ip = getClientIp();

// Rate limit: max 10 login attempts per minute per IP
if (!rateLimit($con, 'shop_login', $ip, 10, 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many attempts. Please wait a minute.']);
    exit;
}

$ShopLogName  = sanitizeString($_POST['ShopLogName']  ?? '', 128);
$ShopPassword = $_POST['ShopPassword'] ?? ''; // Don't alter passwords

if (empty($ShopLogName) || empty($ShopPassword)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

// ── Fetch shop by username only (never include password in WHERE) ──
$stmt = $con->prepare("SELECT * FROM Shops WHERE ShopLogName=? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
$stmt->bind_param("s", $ShopLogName);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    // Generic error — don't reveal if username exists
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

// ── Password verification with bcrypt migration ──────────────
if (!verifyPassword($ShopPassword, $row['ShopPassword'], $con, 'Shops', 'ShopID', $row['ShopID'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

// ── Success — return shop data (strip sensitive fields) ─────
unset($row['ShopPassword']); // Never return password to client
$row['ShopLogName'] = ''; // Don't return login name either

echo json_encode([
    'status_code' => 200,
    'success'     => true,
    'data'        => $row,
    'message'     => 'Logged in successfully'
]);