<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['employee_number']) || empty($_POST['password']) || empty($_POST['role'])) {
        $error = "All fields are required!";
    } else {
        $employee_number = $_POST['employee_number'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        try {
            // Verify credentials and check if account is active
            $stmt = $pdo->prepare("SELECT * FROM users WHERE employee_number = ? AND password = ? AND role = ? AND status = 1");
            $stmt->execute([$employee_number, $password, $role]);
            $user = $stmt->fetch();

            if ($user) {
                // Set session variables on successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['full_name'];
                
                // Log the login action
                logAction($user['id'], 'User logged in');
                
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid credentials or account is disabled!";
            }
        } catch (PDOException $e) {
            // Database connection error
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Limkokwing ARS</title>
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

        .login-container {
            width: 100%;
            max-width: 400px;
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

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header h2 {
            color: var(--primary-color);
            margin: 0;
            font-size: 1.8rem;
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

        select,
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        select:focus,
        input[type="text"]:focus,
        input[type="password"]:focus {
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

        .links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .links a {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .links span {
            color: #bbb;
            margin: 0 0.5rem;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <a href="index.php" class="back-link">← Back to Home</a>

        <div class="login-header">
            <h2>Login to ARS</h2>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Account Type:</label>
                <select name="role" required>
                    <option value="">Select Account Type</option>
                    <option value="admin">Administrator</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="prl">Principal Lecturer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Employee Number:</label>
                <input type="text" name="employee_number" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a>
            <span>|</span>
            <a href="register.php">Register as Admin</a>
        </div>
    </div>
</body>
</html>