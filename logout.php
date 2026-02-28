<?php
include_once __DIR__ . '/includes/init.php';

// Clear session fully
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

// Determine where to return the user.

$base     = '/melody-masters-online-store';
$referer  = $_SERVER['HTTP_REFERER'] ?? '';
$protected = preg_match('#/(customer|admin)/#i', $referer);

if ($referer && !$protected) {
    
    $return_url = preg_replace('/([&?])signedout=1/', '', $referer);
    $return_url = rtrim($return_url, '?&');
    $sep        = strpos($return_url, '?') !== false ? '&' : '?';
    $return_url .= $sep . 'signedout=1';
} else {
    $return_url = $base . '/index.php?signedout=1';
}

header("Location: $return_url");
exit;

