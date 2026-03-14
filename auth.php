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

// FIX CWE-269: Re-verify user role from the database on every request
// Prevents stale session privileges if a user's role is changed or account is deleted
require_once 'db-config.php';

$_verifyStmt = $conn->prepare("SELECT role FROM USER WHERE userId = ?");
$_verifyStmt->bind_param("i", $_SESSION['userId']);
$_verifyStmt->execute();
$_verifyRow = $_verifyStmt->get_result()->fetch_assoc();

if (!$_verifyRow) {
    // User no longer exists in the database - force immediate logout
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_verifyRow['role'] !== $_SESSION['role']) {
    // Role has changed in the database - update session to reflect current state
    $_SESSION['role'] = $_verifyRow['role'];
}

unset($_verifyStmt, $_verifyRow);

// Check for Admin role
function isAdmin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');
}

// Redirect Customer if they try to access Admin pages
function restrictToAdmin() {
    if (!isAdmin()) {
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

// FIX A09: Audit logging helper
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
function setFlash($message, $type = 'error') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

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

// Centralised allowlist for gift card types (Redundancy 3)
// Used by card-add.php and card-update.php for validation and dropdown rendering
$allowedCardTypes = ['Travel', 'Service', 'Food', 'Shopping', 'Lifestyle'];

// Centralised gift card input validation (Redundancy 4)
// Returns an error string if validation fails, or null if all inputs are valid
function validateCardInput($name, $type, $value, $points) {
    global $allowedCardTypes;
    if (!in_array($type, $allowedCardTypes, true)) {
        return "Invalid card type selected.";
    }
    if ($value <= 0) {
        return "Card value must be greater than zero.";
    }
    if ($points < 0) {
        return "Required points cannot be negative.";
    }
    if (empty($name)) {
        return "Card name cannot be empty.";
    }
    return null;
}

// Centralised inline error display helper (Redundancy 5)
// Outputs validation and DB error messages for admin forms
function renderFormErrors($inputError = null, $dbError = null) {
    if (!empty($inputError)) {
        echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($inputError) . "</p>";
    }
    if (!empty($dbError)) {
        echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($dbError) . "</p>";
    }
}
?>