<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    $content = str_replace(
        '$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;', 
        '$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;', 
        $content
    );
    $content = str_replace(
        '$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;', 
        '$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;', 
        $content
    );
    
    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        echo "Fixed lat/long in: $file\n";
    }
}
?>
