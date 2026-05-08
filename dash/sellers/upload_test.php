<?php
require 'init.php'; // gives us $con and $SHOP_DATA
$sellerID = (int)$_SESSION['SellerID'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = "SellerID: $sellerID\n";
    $result .= "_FILES: " . print_r($_FILES, true);
    
    if (!empty($_FILES['testlogo']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../photo/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $name = 'test-logo-' . $sellerID . '-' . time() . '.png';
        $dest = $uploadDir . $name;
        if (move_uploaded_file($_FILES['testlogo']['tmp_name'], $dest)) {
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            $dirUrl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
            $webPath = $baseUrl . $dirUrl . '/photo/' . $name;
            // Save to DB
            $con->query("UPDATE Shops SET ShopLogo = '$webPath' WHERE ShopID = $sellerID");
            $result .= "\n✅ SUCCESS! Saved to DB: $webPath\nFile on disk: $dest\nFile exists: " . (file_exists($dest) ? 'YES' : 'NO');
        } else {
            $result .= "\n❌ move_uploaded_file FAILED";
        }
    } else {
        $result .= "\n⚠️ No file received - _FILES is empty";
    }
    echo "<pre>$result</pre>";
    echo '<a href="upload_test.php">Try again</a>';
    exit;
}
?>
<!DOCTYPE html>
<html>
<head><title>Upload Test</title></head>
<body>
<h2>Direct Upload Test — ShopID: <?= $sellerID ?></h2>
<p>Current Logo in DB: <code><?= htmlspecialchars($SHOP_DATA['ShopLogo']) ?></code></p>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="testlogo" required><br><br>
    <button type="submit">Upload Logo Directly to DB</button>
</form>
</body>
</html>
