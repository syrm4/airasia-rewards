<?php
// Database configuration for WAMP (Windows Default)
$hn = 'localhost';   // No port needed, WAMP uses default 3306
$db = 'rewards';
$un = 'root';
$pw = '';            // WAMP default password is empty

$conn = new mysqli($hn, $un, $pw, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>