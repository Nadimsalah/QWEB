<?php
$file = 'NanoBananaApi.php';
$content = file_get_contents($file);

$search2 = "    \$ext = strtolower(pathinfo(\$_FILES['file']['name'], PATHINFO_EXTENSION));\n    if (!in_array(\$ext, ['jpg', 'jpeg', 'png', 'webp'])) {\n        \$ext = 'jpg';\n    }\n    \$filename = 'vto_' . uniqid() . '.' . \$ext;\n    \$filePath = \$storageDir . \$filename;\n    if (!move_uploaded_file(\$_FILES['file']['tmp_name'], \$filePath)) {\n        http_response_code(500);\n        echo json_encode(['error' => 'Failed to save uploaded file']);\n        exit;\n    }";

$replace2 = <<<PHP
    \$tmpPath = \$_FILES['file']['tmp_name'];
    \$imgData = file_get_contents(\$tmpPath);
    \$im = @imagecreatefromstring(\$imgData);
    
    \$filename = 'vto_' . uniqid() . '.jpg';
    \$filePath = \$storageDir . \$filename;
    
    if (\$im !== false) {
        \$bg = imagecreatetruecolor(imagesx(\$im), imagesy(\$im));
        imagefill(\$bg, 0, 0, imagecolorallocate(\$bg, 255, 255, 255));
        imagecopy(\$bg, \$im, 0, 0, 0, 0, imagesx(\$im), imagesy(\$im));
        imagejpeg(\$bg, \$filePath, 90);
        imagedestroy(\$im);
        imagedestroy(\$bg);
    } else {
        move_uploaded_file(\$tmpPath, \$filePath);
    }
PHP;

$content = str_replace($search2, $replace2, $content);
file_put_contents($file, $content);
echo "Fixed action=upload.\n";
?>
