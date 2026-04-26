<?php
$files = glob("GetAll*.php");
$files = array_merge($files, glob("GetReels*.php"));
$count = 0;
foreach($files as $file) {
    $content = file_get_contents($file);
    
    // Check if we already added the header
    if (strpos($content, 'header(\'Content-Type: application/json; charset=utf-8\');') !== false) {
        continue;
    }

    // Add header right after require "conn.php";
    $newContent = preg_replace('/(require\s*[\'"]conn\.php[\'"];?)/i', "$1\nheader('Content-Type: application/json; charset=utf-8');\n", $content);
    
    if ($newContent !== null && $newContent !== $content) {
        file_put_contents($file, $newContent);
        echo "Added header to $file\n";
        $count++;
    }
}
echo "Total files updated with header: $count\n";
