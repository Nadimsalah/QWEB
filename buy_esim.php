<?php
// ============================================================
// QOON — eSIM Purchase (buy_esim.php)
// SECURITY: Authentication required, input validation
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

// ── Auth required ────────────────────────────────────────────
$authUser = requireAuth($con);
$userID   = (int)$authUser['UserID'];

// ── Rate limit: max 5 purchases per 10 minutes per user ──────
if (!rateLimit($con, 'esim_purchase', "uid_$userID", 5, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many purchase attempts. Please wait.']);
    exit;
}

// ── Input ────────────────────────────────────────────────────
$data    = json_decode(file_get_contents('php://input'), true) ?? [];
$offerId = sanitizeString($data['offerId'] ?? '', 128);

if (empty($offerId)) {
    echo json_encode(['success' => false, 'message' => 'Missing offer ID.']);
    exit;
}

// ── API Key from environment ─────────────────────────────────
$token = getenv('ZENDIT_API_KEY');
if (!$token) {
    echo json_encode(['success' => false, 'message' => 'eSIM service not configured.']);
    exit;
}

$apiUrl       = "https://api.zendit.io/v1/esim/purchases";
$transactionId = "qoon_esim_{$userID}_" . time() . "_" . rand(1000, 9999);

$payload = json_encode([
    "offerId"       => $offerId,
    "transactionId" => $transactionId,
]);

$result = safePost(
    $apiUrl,
    ["Authorization: Bearer $token", "Content-Type: application/json", "Accept: application/json"],
    $payload,
    15
);

if ($result['code'] === 200 || $result['code'] === 201) {
    // Log the purchase against the user (for audit trail)
    $stmt = $con->prepare("INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,MoneyPlusOrLess) VALUES (?,0,'eSIM',?,'less') ");
    if ($stmt) { $stmt->bind_param("is", $userID, $offerId); $stmt->execute(); $stmt->close(); }

    echo json_encode(['success' => true, 'transactionId' => $transactionId]);
} else {
    $err = json_decode($result['body'] ?? '', true);
    echo json_encode(['success' => false, 'message' => $err['message'] ?? 'eSIM API error. Please try again.']);
}