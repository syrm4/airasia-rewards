<?php
require_once 'auth.php';
restrictToAdmin();
require_once 'db-config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    requireCsrf();

    $un   = trim($_POST['userName']);
    $pw   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fn   = trim($_POST['firstName']);
    $ln   = trim($_POST['lastName']);
    $type = trim($_POST['accountType']);
    $pts  = (int)$_POST['points'];

    $stmt = $conn->prepare("INSERT INTO USER (userName, password, firstName, lastName, role) VALUES (?, ?, ?, ?, 'Customer')");
    $stmt->bind_param("ssss", $un, $pw, $fn, $ln);

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;

        $acc_stmt = $conn->prepare("INSERT INTO ACCOUNT (userId, accountType, points) VALUES (?, ?, ?)");
        $acc_stmt->bind_param("isi", $last_id, $type, $pts);

        if ($acc_stmt->execute()) {
            header("Location: card-list.php");
            exit();
        }
    } else {
        echo "<p style='color:red;'>Error: " . htmlspecialchars($conn->error) . "</p>";
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

        <form action="cust-add.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

            <div class="form-group"><label>First Name:</label><input type="text" name="firstName" required></div>
            <div class="form-group"><label>Last Name:</label><input type="text" name="lastName" required></div>
            <div class="form-group"><label>Username:</label><input type="text" name="userName" required></div>
            <div class="form-group"><label>Password:</label><input type="password" name="password" required></div>
            <div class="form-group"><label>Account Type:</label><input type="text" name="accountType" placeholder="Gold, Silver, etc." required></div>
            <div class="form-group"><label>Starting Points:</label><input type="number" name="points" value="0" required></div>

            <button type="submit" class="button-link">Create Account</button>
            <p><a href="card-list.php">Cancel and Go Back</a></p>
        </form>
    </main>
</body>
</html>