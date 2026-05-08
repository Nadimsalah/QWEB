<?php
// No session needed
echo "file_uploads: " . ini_get('file_uploads') . "\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "upload_tmp_dir: " . ini_get('upload_tmp_dir') . "\n";
echo "tmp_dir exists: " . (is_dir(sys_get_temp_dir()) ? 'YES' : 'NO') . "\n";
echo "tmp_dir: " . sys_get_temp_dir() . "\n";
echo "upload tmp dir writable: " . (is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()) ? 'YES' : 'NO') . "\n";
echo "\n_FILES dump:\n";
var_dump($_FILES);
echo "\n_POST action: " . ($_POST['action'] ?? 'NONE') . "\n";
?>
