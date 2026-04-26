<?php
$file = 'NanoBananaApi.php';
$content = file_get_contents($file);

// Replace proxy_image to convert to true JPEG
$search1 = "    \$filename = 'prod_' . uniqid() . '.jpg';\n    \$filePath = \$storageDir . \$filename;\n    file_put_contents(\$filePath, \$imgData);";

$replace1 = <<<PHP
    \$filename = 'prod_' . uniqid() . '.jpg';
    \$filePath = \$storageDir . \$filename;
    
    // Force true JPEG conversion
    \$im = @imagecreatefromstring(\$imgData);
    if (\$im !== false) {
        \$bg = imagecreatetruecolor(imagesx(\$im), imagesy(\$im));
        imagefill(\$bg, 0, 0, imagecolorallocate(\$bg, 255, 255, 255));
        imagecopy(\$bg, \$im, 0, 0, 0, 0, imagesx(\$im), imagesy(\$im));
        imagejpeg(\$bg, \$filePath, 90);
        imagedestroy(\$im);
        imagedestroy(\$bg);
    } else {
        file_put_contents(\$filePath, \$imgData);
    }
PHP;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Fixed proxy_image.\n";
?>
