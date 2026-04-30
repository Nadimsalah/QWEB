<?php
require_once "conn.php";
header('Content-Type: text/plain');

$u1 = $con->query("SELECT UserID, Balance, UserToken FROM Users WHERE Balance > 10 LIMIT 1")->fetch_assoc();
if (!$u1) die("No user with balance found.");
$u2 = $con->query("SELECT UserID FROM Users WHERE UserID != " . $u1['UserID'] . " LIMIT 1")->fetch_assoc();

echo "Sender: {$u1['UserID']} (Bal: {$u1['Balance']}), Receiver: {$u2['UserID']}\n";

$ch = curl_init('http://localhost:8000/AddChargeToUser.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['token: ' . $u1['UserToken']]);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'UserID' => $u1['UserID'],
    'ReceiverID' => $u2['UserID'],
    'Money' => 5.0
]);

$res = curl_exec($ch);
echo "Response:\n$res";
?>
