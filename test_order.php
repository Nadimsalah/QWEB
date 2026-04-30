<?php require " conn.php\; $q = mysqli_query($con, \SELECT OrderState FROM Orders WHERE OrderID=2222739\); print_r(mysqli_fetch_assoc($q)); ?>
