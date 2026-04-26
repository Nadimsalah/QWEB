<?php require "conn.php"; $res = mysqli_query($con, "SELECT COUNT(*) as c FROM Categories WHERE Pro='Normal'"); $r=mysqli_fetch_assoc($res); echo "Normal: ".$r["c"]."\n"; ?>
