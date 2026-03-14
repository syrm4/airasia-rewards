<?php
// FIX A07: Set secure session cookie flags before session_start()
session_set_cookie_params([
    'httponly' => true,                                          // Block JS access to session cookie
    'samesite' => 'Strict',                                      // Block cookie on cross-site requests
    'secure'   => isset($_SERVER['HTTPS']) &&                    // Only send over HTTPS -
                  $_SERVER['HTTPS'] === 'on',                    // disabled automatically on HTTP localhost
]);

session_start();

// Users cannot access pages unless logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

// Check for Admin role
function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');
}

// Redirect Customer if they try to access Admin pages
function restrictToAdmin() {
    if (!isAdmin()) {
        // FIX A01: Use session flash instead of GET parameter
        setFlash('Unauthorized Access.', 'error');
        header("Location: card-list.php");
        exit();
    }
}

// Generate a CSRF token (once per session)
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate submitted CSRF token against session token
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token'], $token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Call this at the top of any POST handler
function requireCsrf() {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die("Invalid or missing CSRF token. Please go back and try again.");
    }
}

// FIX A09: Audit logging helper - call after db-config.php has been included
// $conn   : active MySQLi connection
// $action : event name e.g. LOGIN_SUCCESS, CARD_DELETE
// $detail : optional context e.g. "cardId=5"
function logAction($conn, $action, $detail = null) {
    $userId   = $_SESSION['userId']   ?? null;
    $username = $_SESSION['userName'] ?? null;
    $logTime  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        "INSERT INTO AUDIT_LOG (logTime, userId, username, action, detail) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sisss", $logTime, $userId, $username, $action, $detail);
    $stmt->execute();
}

// FIX A01: Session-based flash message helpers
// Stores a one-time message in the session to be shown after a redirect
function setFlash($message, $type = 'error') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

// Reads and immediately clears the flash message so it only shows once
function getFlash() {
    if (!empty($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type'    => $_SESSION['flash_type'] ?? 'error',
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $flash;
    }
    return null;
}
?>