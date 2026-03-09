<?php
// Authorization: Only Admins can delete records
require_once 'auth.php';
restrictToAdmin();

require_once 'db-config.php';

if (isset($_GET['id'])) {
    // FIX: Cast to int and use prepared statement to prevent SQL injection
    $cardId = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);

    if ($stmt->execute()) {
        header("Location: card-list.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    header("Location: card-list.php");
    exit();
}
?>
