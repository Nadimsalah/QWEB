<?php
$cookies = [
    'qoon_user_id', 
    'qoon_user_name', 
    'qoon_user_photo', 
    'qoon_user_phone', 
    'qoon_user_email',
    'qoon_seen_posts' // Let's clear this too just in case
];

$host = $_SERVER['HTTP_HOST'] ?? '';
$domain = 'qoon.app';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $domain = '';
}

// Need to match exactly the way they were set
$opts = [
    'expires' => time() - 3600, 
    'path' => '/', 
    'domain' => $domain,
    'secure' => $domain !== '',
    'httponly' => false,
    'samesite' => $domain !== '' ? 'Strict' : 'Lax'
];

foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        unset($_COOKIE[$cookie]);
        setcookie($cookie, '', $opts);
        
        // Also try without domain just to be absolutely sure we nuke it
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// If coming from iframe, we should tell the parent window to reload/redirect
if (isset($_GET['iframe'])) {
    echo "<script>window.parent.location.href = 'index.php';</script>";
    exit;
}

header("Location: index.php");
exit;
