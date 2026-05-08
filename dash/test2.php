<?php require "conn.php"; $r = mysqli_query($con, "SELECT DISTINCT StotyType FROM ShopStory LIMIT 10"); while($row=mysqli_fetch_assoc($r)) echo $row["StotyType"]."\n"; ?>
