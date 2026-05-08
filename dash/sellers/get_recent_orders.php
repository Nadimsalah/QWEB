<?php
require_once 'init.php';

$sellerID = (int)$_SESSION['SellerID'];
$shopNameEscaped = $con->real_escape_string($_SESSION['SellerName']);

$recentOrdersSql = "SELECT Orders.OrderID, Orders.OrderPrice, Orders.OrderState, Orders.CreatedAtOrders, Orders.UserName as OrderBuyer, Users.name as DbBuyer, Users.UserPhoto as BuyerPhoto 
                    FROM Orders 
                    LEFT JOIN Users ON Orders.UserID = Users.UserID 
                    WHERE ShopID = $sellerID 
                    ORDER BY Orders.OrderID DESC LIMIT 6";

$recentOrders = [];
if ($roRes = $con->query($recentOrdersSql)) {
    while ($ro = $roRes->fetch_assoc()) {
        $st = $ro['OrderState'];
        $displaySt = ($st == 'Rated' || $st == 'Done') ? 'Delivered' : (($st == 'waiting') ? 'Pending' : $st);

        $tagClass = 'tag-blue';
        if ($st == 'waiting') $tagClass = 'tag-orange';
        if ($st == 'Done' || $st == 'Rated') $tagClass = 'tag-green';
        if ($st == 'Cancelled') $tagClass = 'tag-pink';

        // Prioritize Orders.UserName, fallback to Users.name, then "Guest"
        $bName = trim((string)($ro['OrderBuyer'] ?? ''));
        if($bName === '') $bName = trim((string)($ro['DbBuyer'] ?? ''));
        if($bName === '') $bName = 'Guest Customer';
        $bPhoto = trim((string)($ro['BuyerPhoto'] ?? ''));
        $fallbackB = "https://ui-avatars.com/api/?name=" . urlencode($bName) . "&background=EBE8FA&color=6B4EE6&bold=true";
        $imgSrc = (!empty($bPhoto) && $bPhoto !== 'None' && strlen($bPhoto) > 10) ? $bPhoto : $fallbackB;

        $recentOrders[] = [
            'id' => $ro['OrderID'],
            'customer' => $bName,
            'photo' => $imgSrc,
            'fallback' => $fallbackB,
            'date' => date('n/j/Y', strtotime($ro['CreatedAtOrders'])),
            'status' => $displaySt,
            'tag' => $tagClass,
            'price' => number_format($ro['OrderPrice'], 2) . ' MAD'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($recentOrders);
?>
