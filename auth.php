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

// Redirect Customer if they try to login to Admin pages
function restrictToAdmin() {
    if (!isAdmin()) {
        header("Location: card-list.php?error=Unauthorized Access");
        exit();
    }
}
?>
