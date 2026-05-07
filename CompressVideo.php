<?php
/**
 * CompressVideo.php
 * 
 * This is a standalone utility script to demonstrate TRICK 2: Aggressive Compression.
 * You can include this logic in your upload endpoints or run it as a background job.
 * 
 * It takes an uploaded video, scales it down to 720p (TikTok standard),
 * applies a fast compression preset, and moves the moov atom to the front (faststart).
 */

function optimizeVideoReel($inputFilePath, $outputFilePath) {
    // Check if FFmpeg is installed
    exec('ffmpeg -version', $output, $return_var);
    if ($return_var !== 0) {
        return ["status" => "error", "msg" => "FFmpeg is not installed on this server."];
    }

    // FFmpeg command to compress to 720p, 30fps, faststart, and lower bitrate
    // -vcodec libx264: Use H.264 codec
    // -preset veryfast: Compress quickly to avoid blocking the server
    // -crf 28: Constant Rate Factor (28 is good compression with decent mobile quality)
    // -vf "scale=-2:720": Resize width automatically, force height to 720p
    // -movflags +faststart: Move metadata to the front of the file
    $cmd = "ffmpeg -i " . escapeshellarg($inputFilePath) . " -vcodec libx264 -preset veryfast -crf 28 -vf \"scale=-2:720\" -r 30 -movflags +faststart " . escapeshellarg($outputFilePath) . " 2>&1";
    
    exec($cmd, $cmdOutput, $cmdResult);

    if ($cmdResult === 0 && file_exists($outputFilePath)) {
        return ["status" => "success", "file" => $outputFilePath];
    } else {
        return ["status" => "error", "msg" => "Compression failed", "log" => $cmdOutput];
    }
}

// Example usage:
// $result = optimizeVideoReel('uploads/raw_4k_upload.mp4', 'uploads/optimized_reel.mp4');
// if ($result['status'] === 'success') {
//     unlink('uploads/raw_4k_upload.mp4'); // Delete original to save space
// }
