<?php
session_start();
$_SESSION['SellerID'] = 1; // Assuming ShopID 1 for test
$ch = curl_init('http://localhost:8001/sellers/api_orders.php?action=update_status');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['order_id' => '2222714', 'status' => 'Preparing']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "Response: " . $res;
