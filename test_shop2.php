<?php require conn.php; $r=$con->query(SELECT ShopID FROM Shops WHERE ShopID=5610 OR ShopID=721953); while($row=$r->fetch_assoc()) { print_r($row); }
