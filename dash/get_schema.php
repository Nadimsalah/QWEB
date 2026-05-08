<?php require "conn.php"; $res = mysqli_query($con, "SHOW COLUMNS FROM Posts"); while($r = mysqli_fetch_assoc($res)) { echo $r["Field"] . ","; } ?>
