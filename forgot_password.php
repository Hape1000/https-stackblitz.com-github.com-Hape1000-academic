<?php
require_once 'config.php';

$success = '';
$error = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_number = $_POST['employee_number'];
    $email = $_POST['email'];

    try {
        // Verify user exists and account is active
        $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_number = ? AND email = ? AND status = 1");
        $stmt->execute([$employee_number, $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Reset password to employee number (temporary password)
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$employee_number, $user['id']]);

            // Log the password reset action
            logAction($user['id'], 'Password reset to default');
            $success = "Password has been reset to your employee number. Please login and change your password.";
        } else {
            $error = "No matching active account found!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Limkokwing ARS</title>
    <style>
        /* CSS styles remain unchanged */
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation back to login -->
        <a href="login.php" class="back-link">← Back to Login</a>

        <div class="header">
            <h2>Reset Password</h2>
        </div>

        <!-- Success/Error messages -->
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Password reset form -->
        <form method="POST">
            <div class="form-group">
                <label>Employee Number:</label>
                <input type="text" name="employee_number" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <button type="submit">Reset Password</button>
        </form>
    </div>
</body>
</html>