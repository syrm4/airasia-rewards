<?php
// FIX A07: Match session cookie config from auth.php
// Must be set before session_start() which is called below
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);

session_start();
require_once 'auth.php'; // db-config.php is included internally by auth.php

// FIX A09: Capture identity before session is destroyed
logAction($conn, 'LOGOUT');

// Clear all session variables and destroy the session
session_unset();
session_destroy();

header("Location: login.php");
exit();
?>