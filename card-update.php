<?php
require_once 'auth.php'; // db-config.php and $allowedCardTypes included internally
restrictToAdmin();

if (isset($_GET['id'])) {
    $cardId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM GIFTCARD WHERE cardId = ?");
    $stmt->bind_param("i", $cardId);
    $stmt->execute();
    $result = $stmt->get_result();
    $card = $result->fetch_assoc();

    if (!$card) {
        header("Location: card-list.php");
        exit();
    }
} else {
    header("Location: card-list.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $id     = (int)$_POST['cardId'];
    $name   = trim($_POST['cardName']);
    $type   = trim($_POST['cardType']);
    $value  = (float)$_POST['cardValue'];
    $points = (int)$_POST['points'];

    $inputError = validateCardInput($name, $type, $value, $points);

    if (!$inputError) {
        $stmt = $conn->prepare("UPDATE GIFTCARD SET cardName=?, cardType=?, cardValue=?, points=? WHERE cardId=?");
        $stmt->bind_param("ssdii", $name, $type, $value, $points, $id);

        if ($stmt->execute()) {
            logAction($conn, 'CARD_UPDATE', "cardId=$id, cardName=$name");
            setFlash('Gift card updated successfully.', 'success');
            header("Location: card-list.php");
            exit();
        } else {
            error_log("card-update.php DB error: " . $conn->error);
            $dbError = "An unexpected error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Gift Card</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header>
        <img src="images/logo.png" alt="Company Logo" class="logo">
    </header>

    <main>
        <h1>Update Gift Card Details</h1>

        <?php renderFormErrors($inputError ?? null, $dbError ?? null); ?>

        <form action="card-update.php?id=<?php echo $card['cardId']; ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <input type="hidden" name="cardId" value="<?php echo $card['cardId']; ?>">

            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="cardName"
                       value="<?php echo htmlspecialchars($_POST['cardName'] ?? $card['cardName']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Card Type:</label>
                <select name="cardType" required>
                    <?php
                    $selectedType = $_POST['cardType'] ?? $card['cardType'];
                    foreach ($allowedCardTypes as $ct): ?>
                        <option value="<?php echo $ct; ?>"
                            <?php echo ($selectedType === $ct) ? 'selected' : ''; ?>>
                            <?php echo $ct; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Card Value ($):</label>
                <input type="number" name="cardValue" step="0.01" min="0.01"
                       value="<?php echo htmlspecialchars($_POST['cardValue'] ?? $card['cardValue']); ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Required Points:</label>
                <input type="number" name="points" min="0"
                       value="<?php echo htmlspecialchars($_POST['points'] ?? $card['points']); ?>"
                       required>
            </div>

            <button type="submit" class="button-link">Update Card</button>
            <p><a href="card-details.php?id=<?php echo $card['cardId']; ?>">Cancel and Go Back</a></p>
        </form>
    </main>

</body>
</html>