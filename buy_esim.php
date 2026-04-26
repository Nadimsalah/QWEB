<?php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$offerId = $data['offerId'] ?? '';

if (empty($offerId)) {
    echo json_encode(['success' => false, 'message' => 'Missing offer ID']);
    exit;
}

$accessCode = '473ead5bb2944631b9c313ae4713a450';
$apiUrl = "https://api.esimaccess.com/api/v1/open/esim/order";

$transactionId = "qoon_esim_" . time() . "_" . rand(1000, 9999);

$payload = json_encode([
    "transactionId" => $transactionId,
    "packageInfoList" => [
        [
            "packageCode" => $offerId,
            "count" => 1
        ]
    ]
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "RT-AccessCode: $accessCode",
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['success']) && $data['success'] == true) {
    // The API might return the order details, esim details, etc.
    echo json_encode([
        'success' => true,
        'transactionId' => $data['obj']['orderNo'] ?? $transactionId, // Using their orderNo or our transactionId
        'providerResponse' => $data['obj'] ?? null
    ]);
} else {
    // Server-side logging for failed purchase payloads
    $logMsg = date('[Y-m-d H:i:s]') . " eSIM Access Purchase Failed - TransactionID: $transactionId - OfferID: $offerId - HTTP: $httpCode - Response: $response\n";
    file_put_contents(__DIR__ . '/esim_purchase_errors.log', $logMsg, FILE_APPEND);

    $errorDisplay = isset($data['errorMsg']) ? $data['errorMsg'] : 'API Error';
    
    echo json_encode([
        'success' => false,
        'message' => $errorDisplay,
        'debug' => 'Check server error logs for details.'
    ]);
}
?>
