<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    $new_content = preg_replace("/\\\$Page(\s*)\/(\s*)(\d+)/", "(int)\$Page$1/$2$3", $content);
    if ($content !== $new_content) {
        file_put_contents($file, $new_content);
        echo "Fixed division: $file\n";
    }
}
?>
