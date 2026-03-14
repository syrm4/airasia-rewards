<?php
require_once 'auth.php';
restrictToAdmin();
require_once 'db-config.php';

// FIX: Now accepts POST only, with CSRF validation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardId'])) {
    requireCsrf();

    $cardId = (int)$_POST['cardId'];
    $stmt = $conn->prepare("DELETE FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);

    if ($stmt->execute()) {
        header("Location: card-list.php");
        exit();
    } else {
        echo "Error deleting record: " . htmlspecialchars($conn->error);
    }
} else {
    header("Location: card-list.php");
    exit();
}
?>