<?php
// FIX A07: Match session cookie config from auth.php
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);

session_start();
require_once 'db-config.php';
require_once 'auth.php';

// FIX A09: Capture identity before session is destroyed
logAction($conn, 'LOGOUT');

// Clear all session variables and destroy the session
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>