<?php
require 'conn.php';
$res = mysqli_query($con, 'SELECT PostId, PostPhoto, BunnyV FROM Posts ORDER BY PostId DESC LIMIT 5');
echo "<h2>RECENT POSTS:</h2>";
while($r = mysqli_fetch_assoc($res)) {
    echo "<b>Post {$r['PostId']}:</b> {$r['PostPhoto']} <br>";
}

$res2 = mysqli_query($con, 'SELECT StotyID, StoryPhoto, StotyType FROM ShopStory ORDER BY StotyID DESC LIMIT 5');
echo "<h2>RECENT STORIES:</h2>";
while($r = mysqli_fetch_assoc($res2)) {
    echo "<b>Story {$r['StotyID']}:</b> {$r['StoryPhoto']} (Type: {$r['StotyType']})<br>";
}
?>
