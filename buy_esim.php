<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$offerId = $data['offerId'] ?? '';

if (empty($offerId)) {
    echo json_encode(['success' => false, 'message' => 'Missing offer ID']);
    exit;
}

$token = 'sand_81a14687-4596-4d2f-a3c5-e238d873057869ed4aebbf7c25ab4cfc9e19';
$apiUrl = "https://api.zendit.io/v1/esim/purchases";

$transactionId = "qoon_esim_" . time() . "_" . rand(1000, 9999);

$payload = json_encode([
    "offerId" => $offerId,
    "transactionId" => $transactionId
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 || $httpCode === 201) {
    echo json_encode([
        'success' => true,
        'transactionId' => $transactionId
    ]);
} else {
    $err = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $err['message'] ?? 'API Error'
    ]);
}
?>