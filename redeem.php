<?php
// Authorization: Ensure user is logged in
require_once 'auth.php';
require_once 'db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cardId'])) {
    $cardId = (int)$_POST['cardId'];
    $userId = (int)$_SESSION['userId'];

    // Fetch Card Points Requirement
    $stmt = $conn->prepare("SELECT points, cardName FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();

    // Fetch User's Account (need accountId AND points)
    $stmt2 = $conn->prepare("SELECT accountId, points FROM ACCOUNT WHERE userId = ?");
    $stmt2->bind_param("i", $userId);
    $stmt2->execute();
    $account = $stmt2->get_result()->fetch_assoc();

    // Logic: Compare points
    if ($account['points'] >= $card['points']) {
        // Step 1: Deduct points from the ACCOUNT table
        $new_balance = $account['points'] - $card['points'];
        $stmt3 = $conn->prepare("UPDATE ACCOUNT SET points = ? WHERE accountId = ?");
        $stmt3->bind_param("ii", $new_balance, $account['accountId']);

        if ($stmt3->execute()) {
            // Step 2: Insert into REDEMPTION table matching requirements schema
            $date           = date('Y-m-d H:i:s');
            $pointsRedeemed = $card['points'];
            $accountId      = $account['accountId'];

            $stmt4 = $conn->prepare("INSERT INTO REDEMPTION (date, pointsRedeemed, accountId, cardId) VALUES (?, ?, ?, ?)");
            $stmt4->bind_param("siii", $date, $pointsRedeemed, $accountId, $cardId);
            $stmt4->execute();

            // Step 3: Redirect with success message
            header("Location: card-list.php?error=Success! You redeemed " . urlencode($card['cardName']));
            exit();
        }
    } else {
        // Insufficient points
        header("Location: card-list.php?error=Insufficient points for this reward.");
        exit();
    }
} else {
    header("Location: card-list.php");
    exit();
}
?>
