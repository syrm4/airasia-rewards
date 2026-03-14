<?php
require_once 'auth.php';
restrictToAdmin();
require_once 'db-config.php';

// FIX CWE-20: Define allowlists for validated fields
$allowedCardTypes = ['Travel', 'Service', 'Food', 'Shopping', 'Lifestyle'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $name   = trim($_POST['cardName']);
    $type   = trim($_POST['cardType']);
    $value  = (float)$_POST['cardValue'];
    $points = (int)$_POST['points'];

    // FIX CWE-20: Validate cardType against allowlist
    if (!in_array($type, $allowedCardTypes, true)) {
        $inputError = "Invalid card type selected.";
    }
    // FIX CWE-20: Validate cardValue is a positive number
    elseif ($value <= 0) {
        $inputError = "Card value must be greater than zero.";
    }
    // FIX CWE-20: Validate points is a non-negative integer
    elseif ($points < 0) {
        $inputError = "Required points cannot be negative.";
    }
    // FIX CWE-20: Validate cardName is not empty
    elseif (empty($name)) {
        $inputError = "Card name cannot be empty.";
    }
    else {
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

        <?php if (!empty($inputError)): ?>
            <p style="color:red; font-weight:bold;"><?php echo htmlspecialchars($inputError); ?></p>
        <?php endif; ?>

        <?php if (!empty($dbError)): ?>
            <p style="color:red; font-weight:bold;"><?php echo htmlspecialchars($dbError); ?></p>
        <?php endif; ?>

        <form action="card-add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="cardName"
                       value="<?php echo htmlspecialchars($_POST['cardName'] ?? ''); ?>"
                       required>
            </div>

            <!-- FIX CWE-20: Replaced free-text input with allowlisted dropdown -->
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
                <!-- FIX CWE-20: min="0.01" enforces positive value on front end -->
                <input type="number" name="cardValue" step="0.01" min="0.01"
                       value="<?php echo htmlspecialchars($_POST['cardValue'] ?? ''); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Required Points:</label>
                <!-- FIX CWE-20: min="0" enforces non-negative on front end -->
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