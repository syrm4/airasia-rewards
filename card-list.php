<?php
// Ensure user is logged in
require_once 'auth.php';
require_once 'db-config.php';

// Fetch User's Points
$uId = (int)$_SESSION['userId'];
$stmt = $conn->prepare("SELECT points FROM ACCOUNT WHERE userId = ?");
$stmt->bind_param("i", $uId);
$stmt->execute();
$acc_res = $stmt->get_result();
$account = $acc_res->fetch_assoc();

// Fetch Gift Cards
$result = $conn->query("SELECT * FROM GIFTCARD");

// FIX: Sanitize the error param to prevent XSS
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : "";
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
        <?php if($error != "") echo "<p style='color:red; font-weight:bold;'>$error</p>"; ?>

        <!-- Authorization: Only Admin sees these buttons -->
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
