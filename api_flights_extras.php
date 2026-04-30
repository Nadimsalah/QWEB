<?php
header('Content-Type: application/json');

require_once 'includes/TrawexAPI.php';

$action = $_GET['action'] ?? '';
$sessionId = $_GET['session_id'] ?? '';
$fareSourceCode = $_GET['fare_source_code'] ?? '';

if (empty($action) || empty($sessionId) || empty($fareSourceCode)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

$trawex = new TrawexAPI();

if ($action === 'fare_rules') {
    $rules = $trawex->getFareRules($sessionId, $fareSourceCode);
    $isSuccess = isset($rules['Success']) ? ((string)$rules['Success'] === 'true') : (isset($rules['success']) ? ((string)$rules['success'] === 'true') : false);
    
    if ($rules && $isSuccess) {
        echo json_encode(['success' => true, 'data' => $rules['FareRules'] ?? 'No specific fare rules provided.']);
    } else {
        $msg = $rules['Errors'][0]['ErrorMessage'] ?? 'Failed to fetch fare rules';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
} elseif ($action === 'extra_services') {
    $services = $trawex->getExtraServices($sessionId, $fareSourceCode);
    $isSuccess = isset($services['Success']) ? ((string)$services['Success'] === 'true') : (isset($services['success']) ? ((string)$services['success'] === 'true') : false);
    
    if ($services && $isSuccess) {
        // Trawex returns ExtraServices or ExtraServicesData
        $data = $services['ExtraServices'] ?? $services['ExtraServicesData'] ?? $services;
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        $msg = $services['Errors'][0]['ErrorMessage'] ?? 'Failed to fetch extra services';
        echo json_encode(['success' => false, 'message' => $msg, 'debug_raw' => $services]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
