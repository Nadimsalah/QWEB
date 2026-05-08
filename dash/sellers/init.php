<?php
session_start();

// Redirect to login if no session exists
if (!isset($_SESSION['SellerID'])) {
    header("Location: login.php");
    exit;
}

// Connect to DB using api_conn.php to avoid admin session warnings and redirects
require_once __DIR__ . '/../api_conn.php';
mysqli_set_charset($con, "utf8mb4");

$sellerID = (int)$_SESSION['SellerID'];

// Load Shop details to ensure the account still exists
$shopQuery = $con->query("SELECT * FROM Shops WHERE ShopID = $sellerID LIMIT 1");
if (!$shopQuery || $shopQuery->num_rows === 0) {
    session_destroy();
    header("Location: login.php?error=AccountNotFound");
    exit;
}

$SHOP_DATA = $shopQuery->fetch_assoc();
?>
