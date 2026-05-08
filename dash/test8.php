<?php require "conn.php"; $r = mysqli_query($con, "SHOW TABLES LIKE '%User%'"); while($row=mysqli_fetch_row($r)) echo $row[0]."\n"; ?>
