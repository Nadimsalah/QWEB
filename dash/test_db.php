<?php require 'conn.php'; \ = mysqli_query(\, 'SELECT * FROM Posts ORDER BY PostId DESC LIMIT 1'); print_r(mysqli_fetch_assoc(\)); ?>
