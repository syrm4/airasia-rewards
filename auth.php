<?php
/**
 * auth.php
 *
 * Central authentication and security helper file.
 * Included by every protected page. Handles:
 *   - Session cookie configuration and session startup
 *   - Login gate (redirects unauthenticated users)
 *   - Per-request role re-verification against the database (CWE-269)
 *   - Role-based access control helpers
 *   - CSRF token generation and validation
 *   - Audit logging
 *   - Session-based flash messages
 *   - Gift card input validation and form error rendering
 *
 * @author syrm4
 */

// Set secure session cookie flags before session_start()
session_set_cookie_params([
    'httponly' => true,                                          // Block JS access to session cookie
    'samesite' => 'Strict',                                      // Block cookie on cross-site requests
    'secure'   => isset($_SERVER['HTTPS']) &&                    // Only send over HTTPS -
                  $_SERVER['HTTPS'] === 'on',                    // disabled automatically on HTTP localhost
]);

session_start();

// Users cannot access pages unless logged in
if (!isset($_SESSION['userId'])) {
    header("Location: login.php");
    exit();
}

// Re-verify user role from the database on every request.
// Prevents stale session privileges if a user's role is changed or account is deleted.
require_once 'db-config.php';

$_verifyStmt = $conn->prepare("SELECT role FROM USER WHERE userId = ?");
$_verifyStmt->bind_param("i", $_SESSION['userId']);
$_verifyStmt->execute();
$_verifyRow = $_verifyStmt->get_result()->fetch_assoc();

if (!$_verifyRow) {
    // User no longer exists in the database - force immediate logout
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

if ($_verifyRow['role'] !== $_SESSION['role']) {
    // Role has changed in the database - update session to reflect current state
    $_SESSION['role'] = $_verifyRow['role'];
}

unset($_verifyStmt, $_verifyRow);

/**
 * Checks whether the currently logged-in user has the Admin role.
 *
 * @return bool True if the session role is 'Admin', false otherwise.
 */
function isAdmin(): bool {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin');
}

/**
 * Restricts page access to Admin users only.
 * Sets a flash error message and redirects Customers to card-list.php.
 *
 * @return void
 */
function restrictToAdmin(): void {
    if (!isAdmin()) {
        setFlash('Unauthorized Access.', 'error');
        header("Location: card-list.php");
        exit();
    }
}

/**
 * Generates a cryptographically secure CSRF token for the current session.
 * The token is created once and reused for the lifetime of the session.
 *
 * @return string A 64-character hexadecimal CSRF token.
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the one stored in the session.
 * Uses hash_equals() for timing-safe comparison to prevent timing attacks.
 *
 * @param string $token The token submitted via the form.
 * @return bool True if the token matches the session token, false otherwise.
 */
function validateCsrfToken(string $token): bool {
    return isset($_SESSION['csrf_token'], $token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforces CSRF validation on POST requests.
 * Call at the top of any POST handler. Terminates with a 403 response if
 * the token is missing or invalid.
 *
 * @return void
 */
function requireCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die("Invalid or missing CSRF token. Please go back and try again.");
    }
}

/**
 * Writes a security-relevant event to the AUDIT_LOG table.
 * Captures the current user's ID and username from the session automatically.
 *
 * @param mysqli      $conn   Active database connection.
 * @param string      $action Event identifier (e.g. 'LOGIN_SUCCESS', 'CARD_DELETE').
 * @param string|null $detail Optional context string (e.g. 'cardId=5').
 * @return void
 */
function logAction(mysqli $conn, string $action, ?string $detail = null): void {
    $userId   = $_SESSION['userId']   ?? null;
    $username = $_SESSION['userName'] ?? null;
    $logTime  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        "INSERT INTO AUDIT_LOG (logTime, userId, username, action, detail) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sisss", $logTime, $userId, $username, $action, $detail);
    $stmt->execute();
}

/**
 * Stores a one-time flash message in the session to be displayed after a redirect.
 * The message is consumed and cleared by getFlash() on the next page load.
 *
 * @param string $message The message text to display.
 * @param string $type    Message type: 'success' (green) or 'error' (red). Defaults to 'error'.
 * @return void
 */
function setFlash(string $message, string $type = 'error'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type']    = $type;
}

/**
 * Retrieves and clears the current session flash message.
 * Returns null if no flash message is set. The message is removed from
 * the session immediately so it only displays once.
 *
 * @return array{message: string, type: string}|null Flash data array, or null if none set.
 */
function getFlash(): ?array {
    if (!empty($_SESSION['flash_message'])) {
        $flash = [
            'message' => $_SESSION['flash_message'],
            'type'    => $_SESSION['flash_type'] ?? 'error',
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $flash;
    }
    return null;
}

/**
 * Centralised allowlist of valid gift card types.
 * Used by card-add.php and card-update.php for both server-side validation
 * and front-end dropdown rendering.
 *
 * @var string[] $allowedCardTypes
 */
$allowedCardTypes = ['Travel', 'Service', 'Food', 'Shopping', 'Lifestyle'];

/**
 * Validates gift card form input against business rules and the card type allowlist.
 *
 * @param string   $name         The card name (must not be empty).
 * @param string   $type         The card type (must be in $allowedTypes).
 * @param float    $value        The card monetary value (must be greater than zero).
 * @param int      $points       The required points to redeem (must be non-negative).
 * @param string[] $allowedTypes The allowlist of valid card type strings.
 * @return string|null An error message string if validation fails, or null if all inputs are valid.
 */
function validateCardInput(string $name, string $type, float $value, int $points, array $allowedTypes): ?string {
    if (!in_array($type, $allowedTypes, true)) {
        return "Invalid card type selected.";
    }
    if ($value <= 0) {
        return "Card value must be greater than zero.";
    }
    if ($points < 0) {
        return "Required points cannot be negative.";
    }
    if (empty($name)) {
        return "Card name cannot be empty.";
    }
    return null;
}

/**
 * Outputs inline validation and database error messages for admin forms.
 * Both parameters are optional; only non-empty messages are rendered.
 * Output is XSS-safe via htmlspecialchars().
 *
 * @param string|null $inputError Validation error message, or null if none.
 * @param string|null $dbError    Database error message, or null if none.
 * @return void
 */
function renderFormErrors(?string $inputError = null, ?string $dbError = null): void {
    if (!empty($inputError)) {
        echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($inputError) . "</p>";
    }
    if (!empty($dbError)) {
        echo "<p style='color:red; font-weight:bold;'>" . htmlspecialchars($dbError) . "</p>";
    }
}
// PSR-12: Closing ?> tag omitted in pure PHP files to prevent accidental whitespace output