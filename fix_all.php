<?php
$files = glob("*.php");
foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Fix $Page multiplication
    $content = preg_replace("/\\\$BoostPage = \\\$Page \* 2;/", "\$BoostPage = (int)\$Page * 2;", $content);
    $content = preg_replace("/\\\$Page = \\\$Page \* 5;/", "\$Page = (int)\$Page * 5;", $content);
    
    // Fix stdClass back to null for strict decoding
    $content = str_replace("null", "null", $content);
    
    // Suppress warnings in conn.php
    if ($file == "conn.php" && strpos($content, "error_reporting") === false) {
        $content = "<?php\nerror_reporting(0);\nini_set('display_errors', '0');\n" . substr($content, 5);
    }
    
    if ($content !== file_get_contents($file)) {
        file_put_contents($file, $content);
        echo "Fixed: $file\n";
    }
}
?>
