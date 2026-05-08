<?php
require '../api_conn.php';

// Fix all shops that have localhost URLs — rewrite to qoon.app
$q = $con->query("SELECT ShopID, ShopLogo, ShopCover FROM Shops WHERE ShopLogo LIKE '%localhost%' OR ShopCover LIKE '%localhost%'");
$fixed = 0;
while ($r = $q->fetch_assoc()) {
    $newLogo  = preg_replace('#https?://localhost(:\d+)?#', 'https://qoon.app/dash', $r['ShopLogo']);
    $newCover = preg_replace('#https?://localhost(:\d+)?#', 'https://qoon.app/dash', $r['ShopCover']);
    $newLogo  = $con->real_escape_string($newLogo);
    $newCover = $con->real_escape_string($newCover);
    $con->query("UPDATE Shops SET ShopLogo='$newLogo', ShopCover='$newCover' WHERE ShopID=".(int)$r['ShopID']);
    echo "Fixed ShopID " . $r['ShopID'] . "\n  Logo:  " . $newLogo . "\n  Cover: " . $newCover . "\n\n";
    $fixed++;
}

echo $fixed > 0 ? "Done. Fixed $fixed shops.\n" : "No localhost URLs found in DB.\n";
?>
