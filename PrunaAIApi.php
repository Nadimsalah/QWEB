<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// We need to handle two types of requests: file upload and prediction
$action = $_POST['action'] ?? '';
$prunaToken = "ru_ByeeypNcfVyId3Ip6uSsI6kzPzs2Tn3R";

if ($action === 'upload') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No file provided']);
        exit;
    }
    
    $filePath = $_FILES['file']['tmp_name'];
    $mimeType = $_FILES['file']['type'];
    $originalName = $_FILES['file']['name'];
    
    $ch = curl_init("https://api.pruna.ai/v1/files");
    $cFile = curl_file_create($filePath, $mimeType, $originalName);
    
    $postData = array('content' => $cFile);
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $prunaToken"
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        http_response_code(500);
        echo json_encode(['error' => 'cURL Error: ' . $err]);
        exit;
    }
    
    http_response_code($httpCode);
    echo $response;
    exit;
}

if ($action === 'predict') {
    $userImg = $_POST['userImg'] ?? '';
    $prodImg = $_POST['prodImg'] ?? '';
    
    if (!$userImg || !$prodImg) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing images']);
        exit;
    }
    
    $ch = curl_init("https://api.pruna.ai/v1/predictions");
    
    $payload = json_encode([
        "input" => [
            "prompt" => "Perform a seamless virtual try-on. Transform the person in the first image by applying the garment from the second image.",
            "images" => [$userImg, $prodImg],
            "aspect_ratio" => "match_input_image"
        ]
    ]);
    
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "apikey: $prunaToken",
        "Model: p-image-edit",
        "Try-Sync: true"
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        http_response_code(500);
        echo json_encode(['error' => 'cURL Error: ' . $err]);
        exit;
    }
    
    http_response_code($httpCode);
    echo $response;
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
?>
