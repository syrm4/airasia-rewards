<?php
session_start();
require_once 'db-config.php';

// Generate CSRF token for the login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        die("Invalid CSRF token.");
    }

    $user = trim($_POST['user']);
    $pass = trim($_POST['pass']);

    $stmt = $conn->prepare("SELECT * FROM USER WHERE userName = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['userId']    = $row['userId'];
            $_SESSION['userName']  = $row['userName'];
            $_SESSION['role']      = $row['role'];
            $_SESSION['firstName'] = $row['firstName'];
            header("Location: card-list.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="CSS/style.css">
</head>
<body>

    <header>
        <img src="images/logo.png" alt="Company Logo" class="logo">
    </header>

    <main>
        <h1>Air Asia Gift Card Login</h1>

        <?php if($error != "") echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($error) . "</p>"; ?>

        <form action="login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="user" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="pass" required>
            </div>

            <button type="submit" class="button-link">Login</button>
        </form>
    </main>

</body>
</html>