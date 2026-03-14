<?php
require_once 'auth.php';
restrictToAdmin();
require_once 'db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $name   = trim($_POST['cardName']);
    $type   = trim($_POST['cardType']);
    $value  = trim($_POST['cardValue']);
    $points = trim($_POST['points']);

    $stmt = $conn->prepare("INSERT INTO GIFTCARD (cardName, cardType, cardValue, points) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdi", $name, $type, $value, $points);

    if ($stmt->execute()) {
        // FIX A09: Log successful card addition
        logAction($conn, 'CARD_ADD', "cardName=$name, points=$points");
        header("Location: card-list.php");
        exit();
    } else {
        error_log("card-add.php DB error: " . $conn->error);
        $dbError = "An unexpected error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Gift Card</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header>
        <img src="images/logo.png" alt="Company Logo" class="logo">
    </header>

    <main>
        <h1>Add New Gift Card</h1>

        <?php if (!empty($dbError)): ?>
            <p style="color:red; font-weight:bold;"><?php echo htmlspecialchars($dbError); ?></p>
        <?php endif; ?>

        <form action="card-add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="cardName" required>
            </div>

            <div class="form-group">
                <label>Card Type (e.g. Food, Retail):</label>
                <input type="text" name="cardType" required>
            </div>

            <div class="form-group">
                <label>Card Value ($):</label>
                <input type="number" name="cardValue" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Required Points:</label>
                <input type="number" name="points" required>
            </div>

            <button type="submit" class="button-link">Save Gift Card</button>
            <p><a href="card-list.php">Cancel and Go Back</a></p>
        </form>
    </main>

</body>
</html>