<?php
/**
 * UploadVideoToBunny.php
 * 
 * This script demonstrates how to securely upload a video to BunnyCDN Stream
 * to achieve HLS Adaptive Bitrate Streaming (faster loading, auto-quality adjustment).
 */

header('Content-Type: application/json');
require_once 'conn.php';

// TODO: Replace with your actual Bunny Stream API Keys
$BUNNY_LIBRARY_ID = 'YOUR_LIBRARY_ID'; 
$BUNNY_API_KEY    = 'YOUR_API_KEY'; 

if (!isset($_FILES['video'])) {
    echo json_encode(["status" => "error", "msg" => "No video file provided."]);
    exit;
}

$fileTmpPath = $_FILES['video']['tmp_name'];
$fileName    = $_FILES['video']['name'];
$fileSize    = $_FILES['video']['size'];

try {
    // 1. Create a new Video object in BunnyCDN
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://video.bunnycdn.com/library/$BUNNY_LIBRARY_ID/videos");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['title' => $fileName]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "AccessKey: $BUNNY_API_KEY",
        "Content-Type: application/json",
        "accept: application/json"
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Failed to create video in BunnyCDN. Status: $httpCode");
    }

    $videoData = json_decode($response, true);
    $videoId = $videoData['guid'];

    // 2. Upload the actual video file to the created Video object
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, "https://video.bunnycdn.com/library/$BUNNY_LIBRARY_ID/videos/$videoId");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, "PUT");
    
    $fileStream = fopen($fileTmpPath, 'r');
    curl_setopt($ch2, CURLOPT_INFILE, $fileStream);
    curl_setopt($ch2, CURLOPT_INFILESIZE, $fileSize);
    
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        "AccessKey: $BUNNY_API_KEY",
        "Content-Type: application/octet-stream",
        "accept: application/json"
    ]);

    $uploadResponse = curl_exec($ch2);
    $uploadHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    fclose($fileStream);
    curl_close($ch2);

    if ($uploadHttpCode !== 200) {
        throw new Exception("Failed to upload video file to BunnyCDN. Status: $uploadHttpCode");
    }

    // Success! BunnyCDN will now process the video into HLS formats (360p, 480p, 720p).
    // The HLS playlist URL will be: https://iframe.mediadelivery.net/play/$BUNNY_LIBRARY_ID/$videoId/playlist.m3u8

    $hlsUrl = "https://iframe.mediadelivery.net/play/$BUNNY_LIBRARY_ID/$videoId/playlist.m3u8";

    // You can now save $videoId or $hlsUrl into your database!
    // Example: UPDATE Posts SET BunnyV = '$videoId' WHERE PostID = ...

    echo json_encode([
        "status" => "success",
        "video_id" => $videoId,
        "hls_url" => $hlsUrl,
        "msg" => "Video uploaded successfully and is now processing."
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
}
