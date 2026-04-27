<?php
// ============================================================
// QOON Pay — AddChagreToUser.php (SECURITY FIXED)
// CRITICAL FIXES:
//   1. Restored auth check (was if(true) — bypassed entirely)
//   2. All SQL queries now use prepared statements
//   3. Money amounts validated as positive numbers
//   4. Sufficient balance check before transfer
//   5. Atomic transaction to prevent race conditions
// ============================================================
require_once 'conn.php';
require_once 'security.php';
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

// ── Auth: valid token required ───────────────────────────────
$authUser = requireAuth($con);
$authenticatedUID = (int)$authUser['UserID'];

// ── Input validation ─────────────────────────────────────────
$UserID     = sanitizeInt($_POST['UserID'] ?? 0);
$Money      = sanitizeFloat($_POST['Money'] ?? 0);
$ReceiverID = sanitizeInt($_POST['ReceiverID'] ?? 0);

// Ensure authenticated user is the sender
if ($authenticatedUID !== $UserID) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden — you can only send from your own account.']);
    exit;
}

if ($Money <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transfer amount.']);
    exit;
}

if ($UserID <= 0 || $ReceiverID <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user IDs.']);
    exit;
}

if ($UserID === $ReceiverID) {
    echo json_encode(['success' => false, 'message' => 'Cannot send money to yourself.']);
    exit;
}

// ── Check sender has sufficient balance ──────────────────────
$stmt = $con->prepare("SELECT Balance FROM Users WHERE UserID=? LIMIT 1");
$stmt->bind_param("i", $UserID);
$stmt->execute();
$sender = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sender || $sender['Balance'] < $Money) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance.']);
    exit;
}

// ── Check receiver exists ────────────────────────────────────
$stmt = $con->prepare("SELECT UserID FROM Users WHERE UserID=? LIMIT 1");
$stmt->bind_param("i", $ReceiverID);
$stmt->execute();
$receiver = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receiver) {
    echo json_encode(['success' => false, 'message' => 'Receiver not found.']);
    exit;
}

// ── Get fee percentage ───────────────────────────────────────
$SendMoneyPerc    = 0.0;
$feeRes = $con->query("SELECT SendMoneyPerc FROM OrdersJiblerpercentage LIMIT 1");
if ($feeRes && $feeRow = $feeRes->fetch_assoc()) {
    $SendMoneyPerc = floatval($feeRow['SendMoneyPerc'] ?? 0);
}

$SendMoneyPercww = ($SendMoneyPerc * $Money) / 100;
$MoneyToReceive  = $Money - $SendMoneyPercww;

// ── Atomic transaction: debit sender, credit receiver ────────
$con->begin_transaction();
try {
    // Deduct from sender
    $s1 = $con->prepare("UPDATE Users SET Balance = Balance - ? WHERE UserID = ? AND Balance >= ?");
    $s1->bind_param("did", $Money, $UserID, $Money);
    $s1->execute();
    if ($s1->affected_rows !== 1) {
        throw new Exception('Balance deduction failed (race condition or insufficient funds)');
    }
    $s1->close();

    // Add to receiver
    $s2 = $con->prepare("UPDATE Users SET Balance = Balance + ? WHERE UserID = ?");
    $s2->bind_param("di", $MoneyToReceive, $ReceiverID);
    $s2->execute();
    $s2->close();

    // Update platform commission
    $s3 = $con->prepare("UPDATE Money SET TotalIncome=TotalIncome+?, BalanceTraComm=BalanceTraComm+?");
    $s3->bind_param("dd", $SendMoneyPercww, $SendMoneyPercww);
    $s3->execute();
    $s3->close();

    // Log transaction record
    $s4 = $con->prepare("INSERT INTO SendMoneyTransactions (UserID, TotalMoney, CuttenMoney) VALUES (?, ?, ?)");
    $s4->bind_param("idd", $UserID, $Money, $SendMoneyPercww);
    $s4->execute();
    $s4->close();

    $con->commit();
} catch (Exception $e) {
    $con->rollback();
    echo json_encode(['success' => false, 'message' => 'Transfer failed. Please try again.']);
    exit;
}

// ── Fetch user details for notification & receipt ────────────
$senderStmt = $con->prepare("SELECT name, UserPhoto, Email, UserFirebaseToken FROM Users WHERE UserID=? LIMIT 1");
$senderStmt->bind_param("i", $UserID);
$senderStmt->execute();
$senderRow = $senderStmt->get_result()->fetch_assoc();
$senderStmt->close();

$receiverStmt = $con->prepare("SELECT name, UserPhoto, Email, UserFirebaseToken, LANG FROM Users WHERE UserID=? LIMIT 1");
$receiverStmt->bind_param("i", $ReceiverID);
$receiverStmt->execute();
$receiverRow = $receiverStmt->get_result()->fetch_assoc();
$receiverStmt->close();

$SenderName        = $senderRow['name']             ?? 'User';
$SenderPhoto       = $senderRow['UserPhoto']        ?? '';
$SenderEmail       = $senderRow['Email']            ?? '';
$RecieverName      = $receiverRow['name']           ?? 'User';
$ReceiverPhoto     = $receiverRow['UserPhoto']      ?? '';
$ReceiverEmail     = $receiverRow['Email']          ?? '';
$ReceiverFirebase  = $receiverRow['UserFirebaseToken'] ?? '';
$ReceiverLang      = $receiverRow['LANG']           ?? 'FR';

// ── Insert transaction records ────────────────────────────────
$t1 = $con->prepare("INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees) VALUES (?,?,'QOON Pay',?,?,?,0,?,?,'less',?)");
$t1->bind_param("idssissd", $UserID, $Money, $SenderName, $SenderPhoto, $ReceiverID, $RecieverName, $ReceiverPhoto, $SendMoneyPercww);
$t1->execute();
$TransID = $con->insert_id;
$t1->close();

$t2 = $con->prepare("INSERT INTO UserTransaction (UserID,Money,Method,DistnationName,DistnationPhoto,DriverID,OrderID,DriverName,Driverphoto,MoneyPlusOrLess,UserFees,Type) VALUES (?,?,'QOON Pay',?,?,?,0,?,?,'Add funds',?,'SENDMONEY')");
$t2->bind_param("iddssissd", $ReceiverID, $Money, $SenderName, $SenderPhoto, $UserID, $RecieverName, $ReceiverPhoto, $SendMoneyPercww);
$t2->execute();
$t2->close();

// ── Send push notification ────────────────────────────────────
$notifMessages = [
    'EN' => "$SenderName sent you $Money MAD",
    'FR' => "$SenderName vous a envoyé $Money MAD",
    'AR' => "$SenderName أرسل لك $Money درهم",
];
$notifMsg = $notifMessages[$ReceiverLang] ?? $notifMessages['FR'];

$fcmKey = getenv('FCM_SERVER_KEY') ?: '';
if ($fcmKey && $ReceiverFirebase) {
    $fcmPayload = json_encode([
        'to'           => $ReceiverFirebase,
        'notification' => ['title' => 'QOON Pay', 'body' => $notifMsg],
    ]);
    safePost(
        'https://fcm.googleapis.com/fcm/send',
        ["Authorization: key=$fcmKey", 'Content-Type: application/json'],
        $fcmPayload
    );
}

// ── Send email receipt via PHPMailer ─────────────────────────
$smtpPass = getenv('SMTP_PASS') ?: '';
$smtpHost = getenv('SMTP_HOST') ?: 'mail.qoon.app';
$smtpUser = getenv('SMTP_USER') ?: 'info@qoon.app';
$smtpPort = (int)(getenv('SMTP_PORT') ?: 465);

$receiptHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QOON Pay Receipt</title>
<style>body{background:#f7f8fa;font-family:Arial,sans-serif;padding:40px;text-align:center}
.card{max-width:400px;background:#fff;padding:28px;border-radius:18px;box-shadow:0 8px 26px rgba(0,0,0,.08);margin:0 auto}
.amount{font-size:34px;font-weight:700}.sub{color:#6b7280;font-size:14px}
.status{color:#059669;font-weight:600}
</style></head><body><div class="card">
<img src="https://qoon.app/userDriver/UserDriverApi/logos/QOONLOGO.png" style="width:100px;margin-bottom:16px"><br>
<div class="amount">' . htmlspecialchars((string)$Money) . ' MAD</div>
<div class="sub">Transfer from ' . htmlspecialchars($SenderName) . ' → ' . htmlspecialchars($RecieverName) . '</div>
<p>Fee: ' . htmlspecialchars((string)$SendMoneyPercww) . ' MAD | Transaction ID: ' . htmlspecialchars((string)$TransID) . '</p>
<p class="status">✅ Completed</p>
</div></body></html>';

foreach ([$ReceiverEmail, $SenderEmail] as $toEmail) {
    if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) continue;
    if (!$smtpPass) continue; // Skip if no SMTP password configured in .env
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $smtpPort;
        $mail->setFrom($smtpUser, 'QOON');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = 'QOON Pay Receipt 💸';
        $mail->Body    = $receiptHtml;
        $mail->send();
    } catch (\Exception $e) { /* silent — email is non-critical */ }
}

echo json_encode([
    'status_code' => 200,
    'success'     => true,
    'data'        => $TransID,
    'message'     => 'Transfer completed successfully'
]);