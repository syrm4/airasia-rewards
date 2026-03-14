<?php
require_once 'auth.php'; // db-config.php included internally by auth.php
restrictToAdmin();

$allowedAccountTypes = ['Corporate', 'Gold', 'Silver'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $un   = trim($_POST['userName']);
    $pw   = $_POST['password'];
    $fn   = trim($_POST['firstName']);
    $ln   = trim($_POST['lastName']);
    $type = trim($_POST['accountType']);
    $pts  = (int)$_POST['points'];

    if (!in_array($type, $allowedAccountTypes, true)) {
        $inputError = "Invalid account type selected.";
    } elseif ($pts < 0) {
        $inputError = "Starting points cannot be negative.";
    } elseif (empty($un) || empty($fn) || empty($ln)) {
        $inputError = "First name, last name, and username are all required.";
    } else {
        $hashedPw = password_hash($pw, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO USER (userName, password, firstName, lastName, role) VALUES (?, ?, ?, ?, 'Customer')");
        $stmt->bind_param("ssss", $un, $hashedPw, $fn, $ln);

        if ($stmt->execute()) {
            $last_id = $conn->insert_id;

            $acc_stmt = $conn->prepare("INSERT INTO ACCOUNT (userId, accountType, points) VALUES (?, ?, ?)");
            $acc_stmt->bind_param("isi", $last_id, $type, $pts);

            if ($acc_stmt->execute()) {
                logAction($conn, 'CUSTOMER_ADD', "newUsername=$un, accountType=$type");
                setFlash('Customer enrolled successfully.', 'success');
                header("Location: card-list.php");
                exit();
            } else {
                error_log("cust-add.php ACCOUNT insert error: " . $conn->error);
                $dbError = "An unexpected error occurred. Please try again.";
            }
        } else {
            error_log("cust-add.php USER insert error: " . $conn->error);
            $dbError = "An unexpected error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Customer</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>
    <header><img src="images/logo.png" alt="Logo" class="logo"></header>
    <main>
        <h1>Enroll New Customer</h1>
        <p>Logged in as: <?php echo htmlspecialchars($_SESSION['userName']); ?> (Admin)</p>

        <?php renderFormErrors($inputError ?? null, $dbError ?? null); ?>

        <form action="cust-add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <div class="form-group"><label>First Name:</label>
                <input type="text" name="firstName"
                       value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>"
                       required>
            </div>
            <div class="form-group"><label>Last Name:</label>
                <input type="text" name="lastName"
                       value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>"
                       required>
            </div>
            <div class="form-group"><label>Username:</label>
                <input type="text" name="userName"
                       value="<?php echo htmlspecialchars($_POST['userName'] ?? ''); ?>"
                       required>
            </div>
            <div class="form-group"><label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Account Type:</label>
                <select name="accountType" required>
                    <option value="" disabled selected>Select a type...</option>
                    <?php foreach ($allowedAccountTypes as $at): ?>
                        <option value="<?php echo $at; ?>"
                            <?php echo (($_POST['accountType'] ?? '') === $at) ? 'selected' : ''; ?>>
                            <?php echo $at; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group"><label>Starting Points:</label>
                <input type="number" name="points" min="0"
                       value="<?php echo htmlspecialchars($_POST['points'] ?? '0'); ?>"
                       required>
            </div>

            <button type="submit" class="button-link">Create Account</button>
            <p><a href="card-list.php">Cancel and Go Back</a></p>
        </form>
    </main>
</body>
</html>