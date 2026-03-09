<?php
// Authorization: Ensure user is logged in
require_once 'auth.php';
require_once 'db-config.php';

// FIX: Cast to int and use prepared statement to prevent SQL injection
if (isset($_GET['id'])) {
    $cardId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);
    $stmt->execute();
    $result = $stmt->get_result();
    $card = $result->fetch_assoc();
} else {
    header("Location: card-list.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Card Details</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header>
        <img src="images/logo.png" alt="Company Logo" class="logo">
    </header>

    <main>
        <h1>Gift Card Details</h1>

        <p><a href="card-list.php">&larr; Back to List</a></p>

        <?php if ($card): ?>
            <div class="details-container">
                <img src="images/giftcard.png" alt="Gift Card" class="details-image">
                <h2>Card Name: <?php echo htmlspecialchars($card['cardName']); ?></h2>
                <p><strong>Card Type:</strong> <?php echo htmlspecialchars($card['cardType']); ?></p>
                <p><strong>Value:</strong> $<?php echo number_format($card['cardValue'], 2); ?></p>
                <p><strong>Required Points:</strong> <?php echo number_format($card['points']); ?></p>
            </div>

            <div class="workflow-actions">
                <?php if (isAdmin()): ?>
                    <!-- ADMIN VIEW: Show Management Buttons -->
                    <a href="card-update.php?id=<?php echo $card['cardId']; ?>" class="button-link">Update Card Details</a>
                    <a href="card-delete.php?id=<?php echo $card['cardId']; ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this card?')">Delete This Card</a>
                <?php else: ?>
                    <!-- CUSTOMER VIEW: Show Redemption Button -->
                    <form action="redeem.php" method="POST" style="display:inline;">
                        <input type="hidden" name="cardId" value="<?php echo $card['cardId']; ?>">
                        <button type="submit" class="button-link" style="background-color: #28a745; border:none;" onclick="return confirm('Redeem <?php echo number_format($card['points']); ?> points for this card?')">
                            Redeem Now
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Error: Card not found.</p>
        <?php endif; ?>
    </main>

</body>
</html>
