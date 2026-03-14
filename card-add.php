<?php
require_once 'auth.php'; // db-config.php and $allowedCardTypes included internally
restrictToAdmin();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $name   = trim($_POST['cardName']);
    $type   = trim($_POST['cardType']);
    $value  = (float)$_POST['cardValue'];
    $points = (int)$_POST['points'];

    // Pass $allowedCardTypes as argument (avoids global variable usage)
    $inputError = validateCardInput($name, $type, $value, $points, $allowedCardTypes);

    if (!$inputError) {
        $stmt = $conn->prepare("INSERT INTO GIFTCARD (cardName, cardType, cardValue, points) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssdi", $name, $type, $value, $points);

        if ($stmt->execute()) {
            logAction($conn, 'CARD_ADD', "cardName=$name, points=$points");
            setFlash('Gift card added successfully.', 'success');
            header("Location: card-list.php");
            exit();
        } else {
            error_log("card-add.php DB error: " . $conn->error);
            $dbError = "An unexpected error occurred. Please try again.";
        }
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

        <?php renderFormErrors($inputError ?? null, $dbError ?? null); ?>

        <form action="card-add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="cardName"
                       value="<?php echo htmlspecialchars($_POST['cardName'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Card Type:</label>
                <select name="cardType" required>
                    <option value="" disabled selected>Select a type...</option>
                    <?php foreach ($allowedCardTypes as $ct): ?>
                        <option value="<?php echo $ct; ?>"
                            <?php echo (($_POST['cardType'] ?? '') === $ct) ? 'selected' : ''; ?>>
                            <?php echo $ct; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Card Value ($):</label>
                <input type="number" name="cardValue" step="0.01" min="0.01"
                       value="<?php echo htmlspecialchars($_POST['cardValue'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Required Points:</label>
                <input type="number" name="points" min="0"
                       value="<?php echo htmlspecialchars($_POST['points'] ?? ''); ?>"
                       required>
            </div>

            <button type="submit" class="button-link">Save Gift Card</button>
            <p><a href="card-list.php">Cancel and Go Back</a></p>
        </form>
    </main>

</body>
</html>