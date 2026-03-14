<?php
require_once 'auth.php'; // db-config.php included internally by auth.php

// Fetch User's Points
$uId = (int)$_SESSION['userId'];
$stmt = $conn->prepare("SELECT points FROM ACCOUNT WHERE userId = ?");
$stmt->bind_param("i", $uId);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();

// Explicit column names instead of SELECT *
$result = $conn->query("SELECT cardId, cardName, cardType, cardValue, points FROM GIFTCARD");

$flash = getFlash();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Gift Card Inventory</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header>
        <img src="images/logo.png" alt="Logo" class="logo">
        <div style="text-align:right; padding: 10px;">
            <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['firstName']); ?></strong> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</p>
            <p>Available Points: <strong><?php echo number_format($account['points']); ?></strong></p>
            <p><a href="logout.php">Logout</a></p>
        </div>
    </header>

    <main>
        <h1>Gift Card Inventory</h1>

        <?php if ($flash): ?>
            <?php $colour = ($flash['type'] === 'success') ? 'green' : 'red'; ?>
            <p style="color:<?php echo $colour; ?>; font-weight:bold;">
                <?php echo htmlspecialchars($flash['message']); ?>
            </p>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <div class="top-nav">
                <a href="card-add.php" class="button-link">+ Add New Gift Card</a>
                <a href="cust-add.php" class="button-link">+ Add New Customer</a>
            </div>
        <?php endif; ?>

        <div class="grid-container">
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <img src="images/giftcard.png" alt="Gift Card">
                    <h3><?php echo htmlspecialchars($row['cardName']); ?></h3>
                    <p>Type: <?php echo htmlspecialchars($row['cardType']); ?></p>
                    <p>Value: $<?php echo number_format($row['cardValue'], 2); ?></p>
                    <p>Points: <?php echo number_format($row['points']); ?></p>
                    <a href="card-details.php?id=<?php echo $row['cardId']; ?>">View Details</a>
                </div>
            <?php endwhile; ?>
        </div>
    </main>

</body>
</html>