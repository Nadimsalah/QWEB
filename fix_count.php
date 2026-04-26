<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    $new_content = str_replace("\$count = "0";", "\$count = \"0\";", $content);
    if ($content !== $new_content) {
        file_put_contents($file, $new_content);
        echo "Fixed count in: $file\n";
    }
}
?>
