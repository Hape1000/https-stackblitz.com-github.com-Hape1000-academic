<?php
require_once 'config.php';

// Ensure user is admin and logged in
if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch all logs with user information, ordered by most recent first
$stmt = $pdo->query("
    SELECT l.*, u.full_name, u.employee_number 
    FROM logs l
    JOIN users u ON l.user_id = u.id
    ORDER BY l.timestamp DESC
    LIMIT 1000
");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs - Limkokwing ARS</title>
    <style>
        /* CSS styles remain unchanged */
    </style>
</head>
<body>
    <div class="container">
        <!-- Navigation back to dashboard -->
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>System Logs</h2>

        <!-- System activity log table -->
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Employee Number</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?></td>
                        <td><?php echo htmlspecialchars($log['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($log['employee_number']); ?></td>
                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>