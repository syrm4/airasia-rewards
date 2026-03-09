<?php
// Authorization: Ensure user is logged in AND is an Admin
require_once 'auth.php';
restrictToAdmin();

require_once 'db-config.php';

// GET EXISTING DATA: Pull the card details for the form
if (isset($_GET['id'])) {
    // FIX: Use prepared statement to prevent SQL injection
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

// PROCESS UPDATE: When the user clicks "Update Card"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id     = (int)$_POST['cardId'];
    $name   = trim($_POST['cardName']);
    $type   = trim($_POST['cardType']);
    $value  = trim($_POST['cardValue']);
    $points = trim($_POST['points']);

    // FIX: Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("UPDATE GIFTCARD SET cardName=?, cardType=?, cardValue=?, points=? WHERE cardId=?");
    $stmt->bind_param("ssdii", $name, $type, $value, $points, $id);

    if ($stmt->execute()) {
        header("Location: card-list.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
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

        <form action="card-update.php?id=<?php echo $card['cardId']; ?>" method="POST">
            <input type="hidden" name="cardId" value="<?php echo $card['cardId']; ?>">

            <div class="form-group">
                <label>Card Name:</label>
                <input type="text" name="cardName" value="<?php echo htmlspecialchars($card['cardName']); ?>" required>
            </div>

            <div class="form-group">
                <label>Card Type:</label>
                <input type="text" name="cardType" value="<?php echo htmlspecialchars($card['cardType']); ?>" required>
            </div>

            <div class="form-group">
                <label>Card Value ($):</label>
                <input type="number" name="cardValue" step="0.01" value="<?php echo $card['cardValue']; ?>" required>
            </div>

            <div class="form-group">
                <label>Required Points:</label>
                <input type="number" name="points" value="<?php echo $card['points']; ?>" required>
            </div>

            <button type="submit" class="button-link">Update Card</button>

            <p><a href="card-details.php?id=<?php echo $card['cardId']; ?>">Cancel and Go Back</a></p>
        </form>
    </main>

</body>
</html>
