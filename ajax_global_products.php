<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle path resolution dynamically just like index.php
if (file_exists('aliexpress_api.php')) {
    require_once 'aliexpress_api.php';
} elseif (file_exists('dash/aliexpress_api.php')) {
    require_once 'dash/aliexpress_api.php';
} elseif (file_exists('../dash/aliexpress_api.php')) {
    require_once '../dash/aliexpress_api.php';
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

if (function_exists('getAliExpressProducts')) {
    $products = getAliExpressProducts($page);
    echo json_encode(['status' => 'success', 'data' => $products]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'API not found']);
}
?>
