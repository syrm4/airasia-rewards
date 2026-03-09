<?php
// Start the session to gain access to it
session_start();

// Objective: Log the user out of the system by clearing all session variables
session_unset();

// Destroy the actual session file on the server
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");
exit();
?>
