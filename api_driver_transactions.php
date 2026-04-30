<?php
header('Content-Type: application/json');
require 'conn.php';

$dId = isset($_POST['DriverID']) ? (int) $_POST['DriverID'] : 0;

if ($dId == 0) {
    echo json_encode(['success' => false, 'message' => 'Driver ID required']);
    exit;
}

$transactions = [];
$q = mysqli_query($con, "
    SELECT 
        OrderID, 
        OrderPrice, 
        (OrderPriceFromShop + OrderPriceForOur) as CashCollectedBase, 
        PlatformFee,
        ShopRecive, 
        CreatedAtOrders, 
        DestinationName,
        OrderState,
        Method
    FROM Orders 
    WHERE DelvryId = '$dId' AND OrderState IN ('Done', 'Rated', 'Returned') 
    ORDER BY CreatedAtOrders DESC LIMIT 100
");

// Fetch Driver Commission
$commRes = $con->query("SELECT DriverCommesion FROM MoneyStop LIMIT 1");
$dashboardCommission = 0;
if ($commRes && $commRes->num_rows > 0) {
    $commRow = $commRes->fetch_assoc();
    $dashboardCommission = floatval($commRow['DriverCommesion']);
}

if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        // The Earnings is the OrderPrice minus the Commission (this is what the driver typed)
        $r['Earnings'] = max(0, floatval($r['OrderPrice']) - $dashboardCommission);

        // Cash Collected logic:
        if ($r['OrderState'] === 'Returned') {
            // For returned orders, the driver didn't collect cash for products. But they owe commission? Let's just say debt impact is 0.
            $r['CashCollected'] = 0;
        } else if ($r['Method'] !== 'CASH') {
            // Non-cash orders: The driver collected 0 cash from the customer. 
            // The platform owes the driver the Delivery Fee (OrderPrice). The driver owes the platform Commission.
            // So the net debt added is (Commission - OrderPrice). Usually negative, meaning it REDUCES debt!
            $r['CashCollected'] = $dashboardCommission - floatval($r['OrderPrice']);
        } else {
            // Cash orders: Driver collected everything.
            $base = floatval($r['CashCollectedBase'] ?? 0);
            $platformFee = floatval($r['PlatformFee'] ?? 0);
            $r['CashCollected'] = $base + $platformFee + $dashboardCommission;
        }

        $transactions[] = $r;
    }
}

echo json_encode([
    'success' => true,
    'data' => $transactions
]);
?>