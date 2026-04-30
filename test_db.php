<?php
require_once "conn.php";
$UserID = 1; // Assuming User 1 exists
$ReceiverID = 2; // Assuming User 2 exists

// Find two valid users
$u1 = $con->query("SELECT UserID, Balance FROM Users WHERE Balance > 10 LIMIT 1")->fetch_assoc();
$u2 = $con->query("SELECT UserID FROM Users WHERE UserID != " . $u1['UserID'] . " LIMIT 1")->fetch_assoc();

if(!$u1 || !$u2) { die("Not enough users or balance."); }
echo "Sender: {$u1['UserID']} (Bal: {$u1['Balance']}), Receiver: {$u2['UserID']}\n";

$Money = 1.0;
$_POST['UserID'] = $u1['UserID'];
$_POST['ReceiverID'] = $u2['UserID'];
$_POST['Money'] = $Money;

// Bypass requireAuth temporarily for the test by fetching token
$tokenRow = $con->query("SELECT UserToken FROM Users WHERE UserID = {$u1['UserID']}")->fetch_assoc();
$_POST['token'] = $tokenRow['UserToken'];

// Simulate HTTP request
$ch = curl_init('http://localhost:8000/AddChargeToUser.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
$res = curl_exec($ch);
echo "Response: $res\n";
?>
