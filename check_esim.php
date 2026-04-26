<?php
header('Content-Type: application/json');

$transactionId = $_GET['transactionId'] ?? '';

if (empty($transactionId)) {
    echo json_encode(['status' => 'FAILED']);
    exit;
}

$accessCode = '473ead5bb2944631b9c313ae4713a450';
$apiUrl = "https://api.esimaccess.com/api/v1/open/esim/query";

$payload = json_encode([
    'transactionId' => $transactionId,
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
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    
    if (isset($data['success']) && $data['success'] == true) {
        $esimList = $data['obj']['esimList'] ?? [];
        
        if (count($esimList) > 0) {
            $esim = $esimList[0]; // Assuming one eSIM ordered
            
            // Is it provisioned? Usually checking iccid or qrcode
            $iccid = $esim['iccid'] ?? '';
            $smdpAddress = $esim['smdpAddress'] ?? '';
            $activationCode = $esim['activationCode'] ?? '';
            $status = $esim['status'] ?? ''; 
            
            if (!empty($iccid) && (!empty($smdpAddress) || !empty($activationCode))) {
                
                // GSMA Standard LPA String
                $smdp = str_replace(['https://', 'http://'], '', $smdpAddress);
                $lpaString = "LPA:1$" . $smdp . "$" . $activationCode;
                
                // Some APIs return 'qrCode' ready or 'matchingId'. eSIM Access returns smdpAddress and activationCode.
                if (!empty($esim['qrCode'])) {
                    $qrUrl = $esim['qrCode'];
                } else {
                    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($lpaString);
                }
                
                echo json_encode([
                    'status' => 'DONE',
                    'qrUrl' => $qrUrl,
                    'iccid' => $iccid,
                    'lpa' => $lpaString
                ]);
                exit;
            } else {
                echo json_encode(['status' => 'PENDING']);
                exit;
            }
        } else {
             // Depending on how fast it generates, it might be an empty list or it might exist without iccid
             echo json_encode(['status' => 'PENDING']);
             exit;
        }
    } else {
        // If API says success is false, maybe the transaction ID was wrong or order failed
        echo json_encode(['status' => 'PENDING']);
        exit;
    }
} else {
    echo json_encode(['status' => 'PENDING']);
    exit;
}
?>
