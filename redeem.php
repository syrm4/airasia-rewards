<?php
/**
 * redeem.php
 *
 * Handles gift card redemption for authenticated customers.
 * Runs inside a database transaction with a FOR UPDATE row lock to prevent
 * race conditions on the points balance. Validates sufficient points,
 * deducts the balance, logs the redemption, and records it in the
 * REDEMPTION table. Both success and failure outcomes are audit logged.
 *
 * @author syrm4
 */

require_once 'auth.php'; // db-config.php included internally by auth.php

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardId'])) {
    requireCsrf();

    $cardId = (int)$_POST['cardId'];
    $userId = (int)$_SESSION['userId'];

    $conn->begin_transaction();

    try {
        // Lock the account row to prevent race conditions on the points balance
        $stmt = $conn->prepare("SELECT accountId, points FROM ACCOUNT WHERE userId = ? FOR UPDATE");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account) {
            throw new Exception("Account not found.");
        }

        $stmt2 = $conn->prepare("SELECT points, cardName FROM GIFTCARD WHERE cardId = ?");
        $stmt2->bind_param("i", $cardId);
        $stmt2->execute();
        $card = $stmt2->get_result()->fetch_assoc();

        if (!$card) {
            throw new Exception("Gift card not found.");
        }

        if ($account['points'] < $card['points']) {
            logAction($conn, 'REDEEM_FAIL', "cardId=$cardId, required={$card['points']}, available={$account['points']}");
            throw new Exception("Insufficient points for this reward.");
        }

        // Deduct points from the account balance
        $new_balance = $account['points'] - $card['points'];
        $stmt3 = $conn->prepare("UPDATE ACCOUNT SET points = ? WHERE accountId = ?");
        $stmt3->bind_param("ii", $new_balance, $account['accountId']);
        $stmt3->execute();

        // Record the redemption
        $date           = date('Y-m-d H:i:s');
        $pointsRedeemed = $card['points'];
        $accountId      = $account['accountId'];

        $stmt4 = $conn->prepare("INSERT INTO REDEMPTION (date, pointsRedeemed, accountId, cardId) VALUES (?, ?, ?, ?)");
        $stmt4->bind_param("siii", $date, $pointsRedeemed, $accountId, $cardId);
        $stmt4->execute();

        logAction($conn, 'REDEEM_SUCCESS', "cardId=$cardId, cardName={$card['cardName']}, points=$pointsRedeemed");

        $conn->commit();

        setFlash('Success! You redeemed ' . $card['cardName'] . '.', 'success');
        header("Location: card-list.php");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        setFlash($e->getMessage(), 'error');
        header("Location: card-list.php");
        exit();
    }

} else {
    header("Location: card-list.php");
    exit();
}
?>