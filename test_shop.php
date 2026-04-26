<?php require conn.php; $r=$con->query(SELECT ShopID, ShopName FROM Shops WHERE ShopID=721953); print_r($r->fetch_assoc());
