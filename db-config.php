<?php
/**
 * db-config.php
 *
 * Database connection configuration for WAMP (Windows) and MAMP (Mac).
 * Establishes the MySQLi connection available as $conn throughout the application.
 * Connection errors are logged server-side and a generic 500 response is returned
 * to the browser to avoid exposing internal details (CWE-200).
 *
 * Note: Credentials are intentionally left in plain text for local development
 * and testing purposes. Do not deploy with these defaults.
 *
 * @author syrm4
 */

// Database configuration for WAMP (Windows Default)
$hn = 'localhost';   // No port needed, WAMP uses default 3306
$db = 'rewards';
$un = 'root';
$pw = '';            // WAMP default password is empty

$conn = new mysqli($hn, $un, $pw, $db);

if ($conn->connect_error) {
    // Log real error server-side; never expose connection details to the browser
    error_log("DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("A system error occurred. Please try again later.");
}
?>