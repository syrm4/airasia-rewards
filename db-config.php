<?php
// Database configuration for WAMP (Windows Default)
$hn = 'localhost';   // No port needed, WAMP uses default 3306
$db = 'rewards';
$un = 'root';
$pw = '';            // WAMP default password is empty

$conn = new mysqli($hn, $un, $pw, $db);

if ($conn->connect_error) {
    // FIX CWE-200: Log real error server-side; never expose connection details to the browser
    error_log("DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("A system error occurred. Please try again later.");
}
?>
