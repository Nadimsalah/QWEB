<?php require "conn.php"; $q=mysqli_query($con, "SELECT * FROM ShopStory LIMIT 1"); print_r(mysqli_fetch_assoc($q)); ?>
