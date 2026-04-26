<?php
define('FROM_UI', true);
require_once 'conn.php';
mysqli_report(MYSQLI_REPORT_OFF);

$posts = [];
$stats = [];
if($con) {
    try {
        $pRes = $con->query("SELECT count(*) as c FROM Posts");
         if ($pRes) $stats['total_posts'] = $pRes->fetch_assoc()['c'];

        $pRes2 = $con->query("SELECT count(*) as c FROM Posts WHERE PostStatus='ACTIVE'");
         if ($pRes2) $stats['active_posts'] = $pRes2->fetch_assoc()['c'];

        $postQuery = "SELECT Shops.ShopName, Shops.ShopPhoto, Posts.* 
                      FROM Posts 
                      LEFT JOIN Shops ON Shops.ShopID=Posts.ShopID 
                      ORDER BY Posts.CreatedAtPosts DESC 
                      LIMIT 15";
        $pRes3 = $con->query($postQuery);
        if ($pRes3 && $pRes3->num_rows > 0) {
            while ($row = $pRes3->fetch_assoc()) {
                $posts[] = $row;
            }
        } else {
             $stats['query_error'] = mysqli_error($con);
        }
    } catch (Throwable $e) {
        $stats['error'] = $e->getMessage();
    }
} else {
    $stats['error'] = "DB Conn is false";
}

echo json_encode(["stats" => $stats, "posts" => $posts]);
?>
