<?php
/**
 * logout.php
 *
 * Handles user logout. Logs the LOGOUT event to the audit log before
 * clearing all session variables and destroying the session, then
 * redirects to the login page.
 * Session cookie configuration and session_start() are handled internally
 * by auth.php.
 *
 * @author syrm4
 */

// session_set_cookie_params() and session_start() are both handled by auth.php
// Redundant calls removed - auth.php is the single source of truth for session setup
require_once 'auth.php';

// Capture identity before session is destroyed
logAction($conn, 'LOGOUT');

// Clear all session variables and destroy the session
session_unset();
session_destroy();

header("Location: login.php");
exit();