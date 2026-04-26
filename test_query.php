<?php
$accessCode = '473ead5bb2944631b9c313ae4713a450';
$apiUrl = 'https://api.esimaccess.com/api/v1/open/esim/query';

$payload = json_encode([
    'transactionId' => 'test_123',
    'pager' => [
        'pageNum' => 1,
        'pageSize' => 10
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
$response = curl_exec($ch);
curl_close($ch);

echo json_encode(json_decode($response, true), JSON_PRETTY_PRINT);
?>
