<?php
header('Content-Type: application/json');

$transactionId = $_GET['transactionId'] ?? '';

if (empty($transactionId)) {
    echo json_encode(['status' => 'FAILED']);
    exit;
}

$token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
$apiUrl = "https://api.zendit.io/v1/esim/purchases/" . urlencode($transactionId);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);

    if ($data['status'] === 'DONE' && isset($data['confirmation'])) {
        $conf = $data['confirmation'];
        $smdp = str_replace(['https://', 'http://'], '', $conf['smdpAddress'] ?? '');
        $activationCode = $conf['activationCode'] ?? '';
        $iccid = $conf['iccid'] ?? 'Unknown';

        // GSMA Standard LPA String
        $lpaString = "LPA:1$" . $smdp . "$" . $activationCode;

        // Generate QR code using public API
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($lpaString);

        echo json_encode([
            'status' => 'DONE',
            'qrUrl' => $qrUrl,
            'iccid' => $iccid,
            'lpa' => $lpaString
        ]);
        exit;
    } elseif ($data['status'] === 'FAILED') {
        echo json_encode(['status' => 'FAILED']);
        exit;
    } else {
        echo json_encode(['status' => 'PENDING']);
        exit;
    }
} else {
    echo json_encode(['status' => 'PENDING']);
    exit;
}
?>