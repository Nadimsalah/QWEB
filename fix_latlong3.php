<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Replace `$UserLat = !empty($_POST["UserLat"]) ? (float)$_POST["UserLat"] : 0;`
    $content = preg_replace("/\\\$UserLat = \\\$_POST\\\[\"UserLat\"\\\];/", "\$UserLat = !empty(\$_POST[\"UserLat\"]) ? (float)\$_POST[\"UserLat\"] : 0;", $content);
    // Replace `$UserLongt = !empty($_POST["UserLongt"]) ? (float)$_POST["UserLongt"] : 0;`
    $content = preg_replace("/\\\$UserLongt = \\\$_POST\\\[\"UserLongt\"\\\];/", "\$UserLongt = !empty(\$_POST[\"UserLongt\"]) ? (float)\$_POST[\"UserLongt\"] : 0;", $content);
    
    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        echo "Fixed lat/long in: $file\n";
    }
}
?>
