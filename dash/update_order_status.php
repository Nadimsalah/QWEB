<?php
require "conn.php";
header('Content-Type: application/json');

// Get POST data
$orderID = $_POST['OrderID'] ?? 0;
$newStatus = $_POST['OrderState'] ?? '';

if (!$orderID || !$newStatus) {
    echo json_encode(['status' => 'error', 'message' => 'Missing OrderID or OrderState']);
    exit;
}

// Validate status
$allowed = ['waiting', 'Accepted', 'Preparing', 'Ready', 'Doing', 'Done', 'Cancelled', 'Rated', 'Arrived', 'Returned', 'No_Answer', 'Postponed', 'Paid', 'Out_For_Delivery', 'Refunded'];
if (!in_array($newStatus, $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status state']);
    exit;
}

// Update Database
$orderID = (int)$orderID;
$newStatus = mysqli_real_escape_string($con, $newStatus);
$sql = "UPDATE Orders SET OrderState = '$newStatus' WHERE OrderID = $orderID";

if (mysqli_query($con, $sql)) {
    // 1. Update Firebase Tracker node
    $fbUrl = "https://jibler-37339-default-rtdb.firebaseio.com/OrderTrackers/$orderID.json";
    $fbData = [
        'current_status' => $newStatus,
        'updated_at' => time(),
        'updated_by' => 'AdminPanel'
    ];
    
    $chFb = curl_init();
    curl_setopt($chFb, CURLOPT_URL, $fbUrl);
    curl_setopt($chFb, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($chFb, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chFb, CURLOPT_POSTFIELDS, json_encode($fbData));
    curl_setopt($chFb, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($chFb);
    curl_close($chFb);

    // 2. Add System Message to Chat
    $friendlyStatus = $newStatus;
    if($newStatus == 'waiting') $friendlyStatus = 'Placed';
    if($newStatus == 'Accepted') $friendlyStatus = 'Confirmed by Admin';
    if($newStatus == 'Doing') $friendlyStatus = 'Out for Delivery';
    
    $msgData = [
        'height' => time() * 1000,
        'message' => "Order Status changed to: " . $friendlyStatus,
        'sender' => 'admin',
        'timestamp' => date('H:i')
    ];
    
    $chMsg = curl_init("https://jibler-37339-default-rtdb.firebaseio.com/Messages/$orderID.json");
    curl_setopt($chMsg, CURLOPT_POST, true);
    curl_setopt($chMsg, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chMsg, CURLOPT_POSTFIELDS, json_encode($msgData));
    curl_setopt($chMsg, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($chMsg);
    curl_close($chMsg);

    echo json_encode(['status' => 'success', 'new_state' => $newStatus]);
} else {
    echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
}
