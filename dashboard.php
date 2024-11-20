<?php
require_once 'config.php';

// Ensure user is logged in, redirect to login if not
if (!isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Get user's role and ID from session
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// Fetch detailed user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Limkokwing ARS</title>
    <style>
        /* CSS styles remain unchanged */
    </style>
</head>
<body>
    <!-- Header with user navigation -->
    <header class="header">
        <div class="header-title">Limkokwing ARS</div>
        <div class="header-actions">
            <a href="profile.php" class="header-link">My Profile</a>
            <a href="logout.php" class="header-link logout-btn">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <!-- Welcome message with user's name -->
        <div class="welcome-message">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
            <p>Access your academic reporting tools below</p>
        </div>

        <!-- Display user information -->
        <div class="user-info">
            <h3>User Information</h3>
            <p><strong>Employee Number:</strong> <?php echo htmlspecialchars($user['employee_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
        </div>

        <!-- Role-based menu options -->
        <div class="menu">
            <?php if ($role == 'admin'): ?>
                <!-- Administrative functions -->
                <div class="menu-item admin">
                    <a href="manage_lecturers.php">Manage Lecturers</a>
                </div>
                <div class="menu-item admin">
                    <a href="manage_academic.php">Manage Academic Years</a>
                </div>
                <div class="menu-item admin">
                    <a href="manage_modules.php">Manage Modules</a>
                </div>
                <div class="menu-item admin">
                    <a href="manage_classes.php">Manage Classes</a>
                </div>
                <div class="menu-item admin">
                    <a href="manage_students.php">Manage Students</a>
                </div>
                <div class="menu-item admin">
                    <a href="assign_modules.php">Assign Modules</a>
                </div>
                <div class="menu-item admin">
                    <a href="view_logs.php">View System Logs</a>
                </div>
            <?php endif; ?>

            <?php if ($role == 'lecturer'): ?>
                <!-- Lecturer functions -->
                <div class="menu-item reports">
                    <a href="weekly_report.php">Submit Weekly Report</a>
                </div>
                <div class="menu-item attendance">
                    <a href="attendance.php">Mark Attendance</a>
                </div>
            <?php endif; ?>

            <?php if ($role == 'prl'): ?>
                <!-- Principal Lecturer functions -->
                <div class="menu-item reports">
                    <a href="view_reports.php">View Reports</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>