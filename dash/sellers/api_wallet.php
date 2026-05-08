<?php
require_once 'init.php';

$action = $_GET['action'] ?? '';

if ($action == 'request_payout') {
    $shopID = (int)$_SESSION['SellerID'];
    $amount = (float)$_POST['amount'];
    
    // 1. Get current balance
    $shopQ = $con->query("SELECT Balance FROM Shops WHERE ShopID = $shopID");
    $shop = $shopQ->fetch_assoc();
    $balance = (float)$shop['Balance'];
    
    if ($amount <= 0) {
        die(json_encode(['status' => 'error', 'message' => 'Please enter a valid amount.']));
    }
    
    if ($amount > $balance) {
        die(json_encode(['status' => 'error', 'message' => 'Insufficient balance.']));
    }
    
    // 2. Insert into RequestPay
    $stmt = $con->prepare("INSERT INTO RequestPay (ShopID, Money, RequestPayStatues) VALUES (?, ?, 'PENDING')");
    $stmt->bind_param("ss", $shopID, $amount);
    
    if ($stmt->execute()) {
        // Option: Deduct immediately or wait for admin approval?
        // Usually, in these systems, it stays in balance until approved.
        echo json_encode(['status' => 'success', 'message' => 'Payout request submitted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $con->error]);
    }
    exit;
}
?>
