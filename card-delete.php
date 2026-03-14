<?php
require_once 'auth.php';
restrictToAdmin();
require_once 'db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardId'])) {
    requireCsrf();

    $cardId = (int)$_POST['cardId'];
    $stmt = $conn->prepare("DELETE FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);

    if ($stmt->execute()) {
        header("Location: card-list.php");
        exit();
    } else {
        // FIX A05: Log real error server-side; redirect with generic message
        error_log("card-delete.php DB error: " . $conn->error);
        header("Location: card-list.php?error=An unexpected error occurred. Please try again.");
        exit();
    }
} else {
    header("Location: card-list.php");
    exit();
}
?>