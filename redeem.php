<?php
require_once 'auth.php';
require_once 'db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardId'])) {
    requireCsrf();

    $cardId = (int)$_POST['cardId'];
    $userId = (int)$_SESSION['userId'];

    // Begin transaction to prevent race conditions
    $conn->begin_transaction();

    try {
        // Lock the user's account row for the duration of this transaction
        // FOR UPDATE prevents concurrent requests from reading the same balance simultaneously
        $stmt = $conn->prepare("SELECT accountId, points FROM ACCOUNT WHERE userId = ? FOR UPDATE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account) {
            throw new Exception("Account not found.");
        }

        // Fetch card details
        $stmt2 = $conn->prepare("SELECT points, cardName FROM GIFTCARD WHERE cardId = ?");
        $stmt2->bind_param("i", $cardId);
        $stmt2->execute();
        $card = $stmt2->get_result()->fetch_assoc();

        if (!$card) {
            throw new Exception("Gift card not found.");
        }

        // Check sufficient points
        if ($account['points'] < $card['points']) {
            throw new Exception("Insufficient points for this reward.");
        }

        // Deduct points
        $new_balance = $account['points'] - $card['points'];
        $stmt3 = $conn->prepare("UPDATE ACCOUNT SET points = ? WHERE accountId = ?");
        $stmt3->bind_param("ii", $new_balance, $account['accountId']);
        $stmt3->execute();

        // Log redemption
        $date           = date('Y-m-d H:i:s');
        $pointsRedeemed = $card['points'];
        $accountId      = $account['accountId'];

        $stmt4 = $conn->prepare("INSERT INTO REDEMPTION (date, pointsRedeemed, accountId, cardId) VALUES (?, ?, ?, ?)");
        $stmt4->bind_param("siii", $date, $pointsRedeemed, $accountId, $cardId);
        $stmt4->execute();

        // All good - commit
        $conn->commit();

        header("Location: card-list.php?error=Success! You redeemed " . urlencode($card['cardName']));
        exit();

    } catch (Exception $e) {
        // Something failed - roll back everything
        $conn->rollback();
        header("Location: card-list.php?error=" . urlencode($e->getMessage()));
        exit();
    }

} else {
    header("Location: card-list.php");
    exit();
}
?>