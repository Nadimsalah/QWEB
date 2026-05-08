<?php
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
$dirUrl = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
echo $baseUrl . $dirUrl . "/photo/";
?>
