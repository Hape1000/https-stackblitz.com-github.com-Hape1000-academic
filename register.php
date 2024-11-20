<?php
require_once 'config.php';

$success = '';
$error = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Verify admin registration code
        if ($_POST['admin_code'] !== 'A100') {
            throw new Exception("Invalid admin registration code!");
        }

        // Validate employee number format (6 digits for admin)
        if (!preg_match('/^\d{6}$/', $_POST['employee_number'])) {
            throw new Exception("Employee number must be exactly 6 digits!");
        }

        // Validate password (must match employee number format)
        if (!preg_match('/^\d{6}$/', $_POST['password'])) {
            throw new Exception("Password must be exactly 6 digits!");
        }

        // Check for duplicate employee number
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_number = ?");
            $stmt->execute([$_POST['employee_number']]);
            if ($stmt->fetch()) {
                throw new Exception("Employee number already exists!");
            }

            // Check for duplicate email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email already exists!");
            }

            // Insert new admin user with default active status
            $stmt = $pdo->prepare("
                INSERT INTO users (employee_number, full_name, email, password, role, status) 
                VALUES (?, ?, ?, ?, 'admin', 1)
            ");
            
            $stmt->execute([
                $_POST['employee_number'],
                $_POST['full_name'],
                $_POST['email'],
                $_POST['password']
            ]);

            $success = "Admin account created successfully! You can now login.";
        } else {
            throw new Exception("System error. Please try again later.");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Admin - Limkokwing ARS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --text-light: #ecf0f1;
            --text-dark: #2c3e50;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            color: var(--primary-color);
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.8rem;
        }

        .success {
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .error {
            background: rgba(231, 76, 60, 0.1);
            color: var(--accent-color);
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--secondary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background: #2980b9;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .login-link a {
            color: var(--secondary-color);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.5rem;
            }

            .register-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <a href="index.php" class="back-link">← Back to Home</a>

        <div class="register-header">
            <h2>Register Admin Account</h2>
        </div>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label>Admin Registration Code:</label>
                <input type="password" name="admin_code" required>
            </div>

            <div class="form-group">
                <label>Employee Number (6 digits):</label>
                <input type="text" name="employee_number" pattern="\d{6}" required 
                       title="Employee number must be exactly 6 digits">
            </div>

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password (6 digits):</label>
                <input type="password" name="password" pattern="\d{6}" required 
                       title="Password must be exactly 6 digits">
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" pattern="\d{6}" required>
            </div>

            <button type="submit">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script>
        function validateForm() {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (password !== confirmPassword) {
                alert('Passwords do not match!');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>