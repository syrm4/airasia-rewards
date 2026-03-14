<?php
// session_set_cookie_params() and session_start() are both handled by auth.php
// Redundant calls removed - auth.php is the single source of truth for session setup
require_once 'auth.php';

// FIX A09: Capture identity before session is destroyed
logAction($conn, 'LOGOUT');

// Clear all session variables and destroy the session
session_unset();
session_destroy();

header("Location: login.php");
exit();