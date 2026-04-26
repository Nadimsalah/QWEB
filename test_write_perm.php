<?php
$file = __DIR__ . '/photo/test_write.txt';
if (file_put_contents($file, 'test content') !== false) {
    echo "SUCCESS: File written to photo/ folder.\n";
    unlink($file);
} else {
    echo "FAILURE: Could not write to photo/ folder. Check permissions.\n";
    $error = error_get_last();
    print_r($error);
}
