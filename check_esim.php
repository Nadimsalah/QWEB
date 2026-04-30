<?php
// ============================================================
// QOON — eSIM Status Check (check_esim.php)
// SECURITY: Authentication required, input sanitized
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

// ── Auth required ─────────────────────────────────────────────
$authUser = requireAuth($con);

// ── Input ─────────────────────────────────────────────────────
$transactionId = sanitizeString($_GET['transactionId'] ?? '', 128);

if (empty($transactionId)) {
    echo json_encode(['status' => 'FAILED', 'message' => 'Missing transaction ID.']);
    exit;
}

$token = getenv('ZENDIT_API_KEY');
if (!$token) {
    echo json_encode(['status' => 'FAILED', 'message' => 'eSIM service not configured.']);
    exit;
}

$apiUrl = "https://api.zendit.io/v1/esim/purchases/" . urlencode($transactionId);

$result = safeGet(
    $apiUrl,
    ["Authorization: Bearer $token", "Accept: application/json"],
    15
);

if ($result['code'] === 200 && !empty($result['body'])) {
    $data = json_decode($result['body'], true);

    if (($data['status'] ?? '') === 'DONE' && isset($data['confirmation'])) {
        $conf           = $data['confirmation'];
        $smdp           = str_replace(['https://', 'http://'], '', $conf['smdpAddress'] ?? '');
        $activationCode = $conf['activationCode'] ?? '';
        $iccid          = htmlspecialchars($conf['iccid'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
        $lpaString      = "LPA:1\$$smdp\$$activationCode";
        $qrUrl          = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($lpaString);

        echo json_encode(['status' => 'DONE', 'qrUrl' => $qrUrl, 'iccid' => $iccid, 'lpa' => $lpaString]);
        exit;
    } elseif (($data['status'] ?? '') === 'FAILED') {
        echo json_encode(['status' => 'FAILED']);
        exit;
    }
}

echo json_encode(['status' => 'PENDING']);
