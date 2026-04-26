<?php
// Clear all QOON related cookies
$cookies = ['qoon_user_id', 'qoon_user_name', 'qoon_user_photo', 'qoon_user_phone', 'qoon_user_email'];

foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Redirect to home
header("Location: index.php");
exit;
