<?php
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
        header("Location: card-list.php?error=Unauthorized Access");
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
?>