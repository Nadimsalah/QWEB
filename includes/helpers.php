<?php
function get_img_url($path, $domain = null) {
    if (empty($path) || $path === 'NONE' || $path === '0') {
        return '';
    }
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    $baseDomain = $domain ?? 'https://qoon.app/dash/';
    return $baseDomain . 'photo/' . $path;
}
?>
