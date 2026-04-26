<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    // Fix empty strings in UserLat / UserLongt
    $content = preg_replace("/\\\$UserLat = isset\\\(\\\$_POST\\\[\"UserLat\"\\\]\\\) \? \\\$_POST\\\[\"UserLat\"\\\] : \"0\";/", "\$UserLat = !empty(\$_POST[\"UserLat\"]) ? (float)\$_POST[\"UserLat\"] : 0;", $content);
    $content = preg_replace("/\\\$UserLongt = isset\\\(\\\$_POST\\\[\"UserLongt\"\\\]\\\) \? \\\$_POST\\\[\"UserLongt\"\\\] : \"0\";/", "\$UserLongt = !empty(\$_POST[\"UserLongt\"]) ? (float)\$_POST[\"UserLongt\"] : 0;", $content);
    
    if (strpos($content, "\$test==4") !== false) {
        $content = str_replace("if(\$test==4){", "if(\$test==4 || empty(\$result)){", $content);
    }

    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        echo "Fixed latlong in: $file\n";
    }
}
?>
