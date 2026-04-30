<?php
header('Content-Type: application/json');
require 'conn.php';

$dId = isset($_POST['DriverID']) ? (int)$_POST['DriverID'] : 0;

$todayTrips = 0;
$totalEarnings = 0.0;
$driverRating = 5.0;
$cashCollected = 0.0;
$cashLimit = 350;
$qLimit = mysqli_query($con, "SELECT MoneyStopNumber FROM MoneyStop LIMIT 1");
if($qLimit && $r = mysqli_fetch_assoc($qLimit)) {
    if (!empty($r['MoneyStopNumber'])) {
        $cashLimit = (float)$r['MoneyStopNumber'];
    }
}

if ($dId > 0) {
    $todayDate = date('Y-m-d');
    $qToday = mysqli_query($con, "SELECT COUNT(*) as c FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated', 'Returned') AND DATE(CreatedAtOrders) = '$todayDate'");
    if($qToday && $r = mysqli_fetch_assoc($qToday)) { $todayTrips = (int)$r['c']; }

    $qEarn = mysqli_query($con, "SELECT SUM(OrderPrice) as total FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated', 'Returned')");
    if($qEarn && $r = mysqli_fetch_assoc($qEarn)) { $totalEarnings = (float)$r['total']; }

    $qRate = mysqli_query($con, "SELECT AVG(OrderRate) as avg_rate FROM Orders WHERE DelvryId = '$dId' AND OrderState = 'Rated' AND OrderRate > 0");
    if($qRate && $r = mysqli_fetch_assoc($qRate)) { 
        if (!empty($r['avg_rate'])) $driverRating = round((float)$r['avg_rate'], 1);
    }

    $qCash = mysqli_query($con, "SELECT SUM(OrderPriceFromShop + OrderPriceForOur + PlatformFee) as debt FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated') AND ShopRecive = 'NO' AND Method = 'CASH'");
    $debtBase = 0;
    if($qCash && $r = mysqli_fetch_assoc($qCash)) { 
        $debtBase = (float)$r['debt']; 
    }
    
    // Wallet: Earnings from non-cash orders
    $qWallet = mysqli_query($con, "SELECT SUM(OrderPrice) as wallet FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated', 'Returned') AND Method != 'CASH'");
    $walletBalance = 0.0;
    if ($qWallet && $r = mysqli_fetch_assoc($qWallet)) {
        $walletBalance = (float)$r['wallet'];
    }

    $qCommCount = mysqli_query($con, "SELECT COUNT(*) as c FROM Orders WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated', 'Returned')");
    $commCount = 0;
    if ($qCommCount && $r = mysqli_fetch_assoc($qCommCount)) {
        $commCount = (int)$r['c'];
    }

    $commRes = mysqli_query($con, "SELECT DriverCommesion FROM MoneyStop LIMIT 1");
    $dashboardCommission = 0;
    if($commRes && $commRes->num_rows > 0) {
        $commRow = mysqli_fetch_assoc($commRes);
        $dashboardCommission = floatval($commRow['DriverCommesion']);
    }
    
    // Net Debt = (Debt from CASH orders) + (Commission for ALL orders) - (Wallet from CARD orders)
    $cashCollected = $debtBase + ($commCount * $dashboardCommission) - $walletBalance;
}

echo json_encode([
    'success' => true,
    'data' => [
        'todayTrips' => $todayTrips,
        'totalEarnings' => $totalEarnings,
        'driverRating' => $driverRating,
        'cashCollected' => $cashCollected,
        'cashLimit' => $cashLimit,
        'walletBalance' => isset($walletBalance) ? $walletBalance : 0.0
    ]
]);
?>
