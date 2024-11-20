<?php
require_once 'config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle password change request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verify current password
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND password = ?");
        $stmt->execute([$user_id, $_POST['current_password']]);
        
        if ($stmt->fetch()) {
            $new_password = $_POST['new_password'];
            $role = $_SESSION['role'];
            
            // Validate password format based on role
            // Admin: 6 digits, Others: 5 digits
            if ($role == 'admin') {
                if (!preg_match('/^\d{6}$/', $new_password)) {
                    throw new Exception("Admin password must be exactly 6 digits!");
                }
            } else {
                if (!preg_match('/^\d{5}$/', $new_password)) {
                    throw new Exception("Password must be exactly 5 digits!");
                }
            }

            // Update password in database
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $user_id]);
            
            // Handle admin code update if provided
            if ($role == 'admin' && !empty($_POST['admin_code'])) {
                // Future enhancement: Update admin registration code in settings table
                $success = "Password and admin registration code updated successfully!";
            } else {
                $success = "Password updated successfully!";
            }
            
            // Log the password change
            logAction($user_id, 'Password changed');
        } else {
            throw new Exception("Current password is incorrect!");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Profile - Limkokwing ARS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* CSS styles remain unchanged */
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>My Profile</h2>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- User information display -->
        <div class="profile-section">
            <h3>User Information</h3>
            <div class="profile-info">
                <p><strong>Employee Number:</strong> <?php echo htmlspecialchars($user['employee_number']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
            </div>
        </div>

        <!-- Password change form -->
        <div class="profile-section">
            <h3>Change Password</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required 
                           pattern="<?php echo $user['role'] == 'admin' ? '\d{6}' : '\d{5}'; ?>">
                    <p class="password-requirements">
                        Password must be exactly <?php echo $user['role'] == 'admin' ? '6' : '5'; ?> digits
                    </p>
                </div>

                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required
                           pattern="<?php echo $user['role'] == 'admin' ? '\d{6}' : '\d{5}'; ?>">
                </div>

                <?php if ($user['role'] == 'admin'): ?>
                    <!-- Admin-specific settings -->
                    <div class="form-group">
                        <label>New Admin Registration Code (Optional):</label>
                        <input type="password" name="admin_code">
                        <p class="password-requirements">Leave blank to keep current code</p>
                    </div>
                <?php endif; ?>

                <button type="submit">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Client-side password validation -->
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.querySelector('input[name="new_password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>