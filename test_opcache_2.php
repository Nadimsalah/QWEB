<?php
require_once 'conn.php';
$stats = [];
if($con) {
    try {
        $pRes = $con->query("SELECT count(*) as c FROM Posts");
        $stats['total_posts'] = $pRes ? $pRes->fetch_assoc()['c'] : mysqli_error($con);
        
        $p2 = $con->query("SELECT * FROM Posts LIMIT 2");
        $stats['posts'] = [];
        if($p2) {
            while($r = $p2->fetch_assoc()) $stats['posts'][] = $r;
        }

    } catch (Throwable $e) { }
}
echo json_encode($stats);
?>
