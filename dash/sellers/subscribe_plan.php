<?php
require 'init.php';
header('Content-Type: application/json');

$sellerID = (int)$_SESSION['SellerID'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'msg' => 'Invalid request.']); exit;
}

$plan = trim($_POST['plan'] ?? '');
$price = (float)($_POST['price'] ?? 0);

$validPlans = ['Free Tier', 'Premium Pro', 'Premium Plus'];
if (!in_array($plan, $validPlans)) {
    echo json_encode(['success' => false, 'msg' => 'Invalid plan selected.']); exit;
}

// Free Tier costs nothing
if ($plan === 'Free Tier') {
    $con->query("UPDATE Shops SET PaySub = 'FREE' WHERE ShopID = $sellerID");
    echo json_encode(['success' => true, 'msg' => 'Free Tier activated.', 'new_balance' => $SHOP_DATA['Balance']]);
    exit;
}

if ($price <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Invalid price.']); exit;
}

// Check wallet balance
$shopRes = $con->query("SELECT Balance, PaySub FROM Shops WHERE ShopID = $sellerID");
$shop = $shopRes->fetch_assoc();
$balance = (float)$shop['Balance'];

if ($balance < $price) {
    echo json_encode([
        'success' => false,
        'msg' => 'Insufficient wallet balance. Your balance: ' . number_format($balance, 2) . ' MAD. Required: ' . number_format($price, 2) . ' MAD.',
        'balance' => $balance
    ]);
    exit;
}

// Deduct from wallet
$newBalance = $balance - $price;
$planKey = ($plan === 'Premium Pro') ? 'PRO' : 'PLUS';

$con->begin_transaction();
try {
    // Deduct balance
    $con->query("UPDATE Shops SET Balance = $newBalance, PaySub = '$planKey' WHERE ShopID = $sellerID");

    // Log the transaction
    $shopName = $con->real_escape_string($SHOP_DATA['ShopName']);
    $planEsc = $con->real_escape_string($plan);
    $con->query("INSERT INTO ShopLastTransaction 
        (ShopID, Money, Method, TransactionName, TransactionStatus, CreatedAtShopLastTransaction) 
        VALUES 
        ($sellerID, '-$price', 'Wallet', '$planEsc Subscription', 'Done', NOW())");

    $con->commit();

    echo json_encode([
        'success' => true,
        'msg' => $plan . ' activated successfully!',
        'new_balance' => $newBalance,
        'plan' => $planKey
    ]);
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['success' => false, 'msg' => 'Transaction failed. Please try again.']);
}
?>
