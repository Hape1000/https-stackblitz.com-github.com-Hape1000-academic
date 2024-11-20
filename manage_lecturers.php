<?php
/**
 * Lecturer Management
 * 
 * This file handles the management of lecturers and staff including:
 * - Adding new lecturers/PRLs
 * - Managing user status
 * - Reassigning roles
 * - Viewing all users
 * 
 * Requires admin authentication
 */

require_once 'config.php';

// Ensure user is logged in and is an admin
if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] == 'add') {
                $emp_num = $_POST['employee_number'];
                $role = $_POST['role'];
                
                // Validate employee number format based on role
                if ($role == 'admin' && !preg_match('/^\d{6}$/', $emp_num)) {
                    throw new Exception("Admin employee number must be exactly 6 digits!");
                } elseif ($role != 'admin' && !preg_match('/^\d{5}$/', $emp_num)) {
                    throw new Exception("Lecturer/PRL employee number must be exactly 5 digits!");
                }

                // Check for duplicate employee number
                $stmt = $pdo->prepare("SELECT id FROM users WHERE employee_number = ?");
                $stmt->execute([$emp_num]);
                if ($stmt->fetch()) {
                    throw new Exception("Employee number already exists!");
                }

                // Check for duplicate email
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$_POST['email']]);
                if ($stmt->fetch()) {
                    throw new Exception("Email already exists!");
                }

                // Add new user with default password
                $stmt = $pdo->prepare("
                    INSERT INTO users (employee_number, full_name, email, password, role) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $emp_num,
                    $_POST['full_name'],
                    $_POST['email'],
                    $emp_num, // Initial password same as employee number
                    $role
                ]);
                $success = "User added successfully! Initial password is the employee number.";
            }
            elseif ($_POST['action'] == 'toggle_status') {
                $user_id = $_POST['user_id'];
                
                // Prevent modifying admin status
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user['role'] == 'admin') {
                    throw new Exception("Cannot modify admin status!");
                }
                
                // Toggle user status
                $stmt = $pdo->prepare("UPDATE users SET status = NOT status WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = "User status updated successfully!";
            }
            elseif ($_POST['action'] == 'reassign_role') {
                $user_id = $_POST['user_id'];
                $new_role = $_POST['new_role'];
                
                // Validate role
                if (!in_array($new_role, ['lecturer', 'prl'])) {
                    throw new Exception("Invalid role selected!");
                }
                
                // Prevent modifying admin roles
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user['role'] == 'admin') {
                    throw new Exception("Cannot modify admin roles!");
                }
                
                // Update user role
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $success = "User role updated successfully!";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Fetch all users except current admin
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE id != ? 
    ORDER BY role = 'admin' DESC, created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Limkokwing ARS</title>
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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        button, .btn {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }

        button:hover, .btn:hover {
            opacity: 0.9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #f5f5f5;
            font-weight: 600;
            color: var(--text-dark);
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .success {
            color: var(--success-color);
            background: rgba(46, 204, 113, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .error {
            color: var(--accent-color);
            background: rgba(231, 76, 60, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .nav {
            margin-bottom: 20px;
        }

        .nav a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .action-btn {
            padding: 5px 10px;
            margin: 2px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
        }

        .enable-btn {
            background: var(--success-color);
        }

        .disable-btn {
            background: var(--accent-color);
        }

        .reassign-btn {
            background: var(--warning-color);
            color: var(--text-dark);
        }

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-title {
            margin-top: 0;
            color: var(--primary-color);
            font-size: 1.2em;
            margin-bottom: 15px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }

            table {
                display: block;
                overflow-x: auto;
            }

            .action-btn {
                margin: 2px 0;
                display: block;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>Manage Users</h2>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h3 class="form-title">Add New User</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Employee Number:</label>
                    <input type="text" name="employee_number" required 
                           pattern="<?php echo isset($_POST['role']) && $_POST['role'] == 'admin' ? '\d{6}' : '\d{5}'; ?>"
                           title="<?php echo isset($_POST['role']) && $_POST['role'] == 'admin' ? 
                                 'Must be exactly 6 digits' : 'Must be exactly 5 digits'; ?>">
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
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="lecturer">Lecturer</option>
                        <option value="prl">Principal Lecturer</option>
                    </select>
                </div>

                <button type="submit">Add User</button>
            </form>
        </div>

        <h3>Existing Users</h3>
        <table>
            <thead>
                <tr>
                    <th>Employee Number</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['employee_number']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo ucfirst($user['role']); ?></td>
                        <td><?php echo $user['status'] ? 'Active' : 'Inactive'; ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['role'] != 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="action-btn <?php echo $user['status'] ? 'disable-btn' : 'enable-btn'; ?>">
                                        <?php echo $user['status'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                </form>

                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reassign_role">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <select name="new_role" onchange="this.form.submit()" class="action-btn reassign-btn">
                                        <option value="">Change Role</option>
                                        <?php if ($user['role'] != 'lecturer'): ?>
                                            <option value="lecturer">Lecturer</option>
                                        <?php endif; ?>
                                        <?php if ($user['role'] != 'prl'): ?>
                                            <option value="prl">PRL</option>
                                        <?php endif; ?>
                                    </select>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>