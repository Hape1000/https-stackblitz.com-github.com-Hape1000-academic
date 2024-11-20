<?php
// Database connection parameters
$host = 'localhost';
$dbname = 'limkokwing_ars';
$username = 'root';
$password = '';

try {
    // Establish PDO database connection with error handling enabled
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // Silently handle database connection errors to avoid exposing sensitive information
    $pdo = null;
}

// Start session management
session_start();

/**
 * Check if user is currently logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Log user actions for audit trail
 * @param int $userId The ID of the user performing the action
 * @param string $action Description of the action performed
 */
function logAction($userId, $action) {
    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$userId, $action]);
        } catch (PDOException $e) {
            // Silently handle logging errors
        }
    }
}

// Clear existing session when accessing index page (except during login)
if (basename($_SERVER['PHP_SELF']) == 'index.php' && !isset($_POST['employee_number'])) {
    session_destroy();
    session_start();
}
?>