<?php
$files = glob("GetAll*.php");
$files = array_merge($files, glob("GetReels*.php"));
$count = 0;
foreach($files as $file) {
    $content = file_get_contents($file);
    
    // Check if it already has JSON_UNESCAPED_UNICODE to avoid double replacing
    if (strpos($content, 'JSON_UNESCAPED_UNICODE') !== false) {
        continue;
    }

    $newContent = preg_replace('/echo json_encode\((array\([^)]+\))\);/is', 'echo json_encode($1, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);', $content);
    
    if ($newContent !== null && $newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Updated $file\n";
        $count++;
    }
}
echo "Total files updated: $count\n";
