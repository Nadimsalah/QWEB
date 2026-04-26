<?php
require_once 'conn.php';
$res = mysqli_query($con, "SELECT * FROM Categories LIMIT 50");
$cats = [];
if($res){
    while($row = mysqli_fetch_assoc($res)) {
        $cats[] = $row;
    }
}
echo json_encode(['error' => mysqli_error($con), 'count' => count($cats), 'data' => $cats]);
?>
