<?php require "conn.php"; $r = mysqli_query($con, "SHOW COLUMNS FROM Shops"); while($row=mysqli_fetch_row($r)) echo $row[0].", "; ?>
