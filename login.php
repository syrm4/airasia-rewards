<?php
/**
 * login.php
 *
 * Public login page. Handles CSRF-protected credential validation,
 * session initialisation on successful login, session fixation prevention,
 * and audit logging of both successful and failed login attempts.
 * This file does not include auth.php as it must be accessible
 * to unauthenticated users.
 *
 * @author syrm4
 */

// Set secure session cookie flags before session_start()
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
]);

session_start();
require_once 'db-config.php';

// Generate CSRF token for the login form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Writes a login event to the AUDIT_LOG table.
 * Defined inline here because auth.php (which contains logAction()) cannot
 * be included on the login page - it would redirect unauthenticated users.
 *
 * @param mysqli   $conn     Active database connection.
 * @param string   $action   Event type: 'LOGIN_SUCCESS' or 'LOGIN_FAIL'.
 * @param string   $username The username that was submitted in the login form.
 * @param int|null $userId   The authenticated user's ID, or null on failed attempts.
 * @return void
 */
function logLoginEvent(mysqli $conn, string $action, string $username, ?int $userId = null): void {
    $logTime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        "INSERT INTO AUDIT_LOG (logTime, userId, username, action, detail) VALUES (?, ?, ?, ?, NULL)"
    );
    $stmt->bind_param("sisss", $logTime, $userId, $username, $action);
    $stmt->execute();
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

            // Regenerate session ID on login to prevent session fixation
            session_regenerate_id(true);

            $_SESSION['userId']    = $row['userId'];
            $_SESSION['userName']  = $row['userName'];
            $_SESSION['role']      = $row['role'];
            $_SESSION['firstName'] = $row['firstName'];

            // Log successful login
            logLoginEvent($conn, 'LOGIN_SUCCESS', $row['userName'], $row['userId']);

            header("Location: card-list.php");
            exit();
        } else {
            // Log failed login (wrong password - user exists)
            logLoginEvent($conn, 'LOGIN_FAIL', $user);
            $error = "Invalid username or password.";
        }
    } else {
        // Log failed login (username not found)
        logLoginEvent($conn, 'LOGIN_FAIL', $user);
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