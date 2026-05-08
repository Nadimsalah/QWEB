<?php
require '../api_conn.php';
$q = $con->query("INSERT INTO Shops (ShopName, ShopLogName, ShopPassword, Email, ShopPhone, ShopLat, ShopLongt, ShopRate, RatePoints, RateTime, ShopRatedTime, ShopOpen, ShopLogo, ShopCover, CategoryID, Type, priority, InHome, HasStory, StoryCount, ShopFirebaseToken, Token, Loged, lastShopsUpdated, CreatedAtShops, AdminID, LastPaid, CityID, FullName, Status, OwnerPhone, BakatID, LANG, PaySub, BankName, BankNum) VALUES ('TestShop', 'testshop123', 'pass123', 'test@example.com', '1234567890', 0, 0, 5.0, 0, 0, 0, 'Open', '', '', 0, 'Standard', 0, 0, 0, 0, '', '', 0, 0, 0, 0, 0, 0, 'Test User', 'ACTIVE', '', 0, 'EN', 0, '', '')");
if($q) echo "OK"; else echo $con->error;
?>
