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
        logAction($conn, 'CARD_DELETE', "cardId=$cardId");
        // FIX A01: Use session flash for success message
        setFlash('Gift card deleted successfully.', 'success');
        header("Location: card-list.php");
        exit();
    } else {
        error_log("card-delete.php DB error: " . $conn->error);
        // FIX A01: Use session flash for error message
        setFlash('An unexpected error occurred. Please try again.', 'error');
        header("Location: card-list.php");
        exit();
    }
} else {
    header("Location: card-list.php");
    exit();
}
?>