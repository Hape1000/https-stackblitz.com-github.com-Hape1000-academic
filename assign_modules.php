<?php
require_once 'config.php';

if (!isLoggedIn() || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$success = '';
$error = '';

// Fetch all active lecturers
$stmt = $pdo->query("
    SELECT * FROM users 
    WHERE role = 'lecturer' AND status = 1 
    ORDER BY full_name
");
$lecturers = $stmt->fetchAll();

// Fetch all active modules
$stmt = $pdo->query("
    SELECT * FROM modules 
    WHERE status = 1 
    ORDER BY module_name
");
$modules = $stmt->fetchAll();

// Fetch all active classes
$stmt = $pdo->query("
    SELECT * FROM classes 
    WHERE status = 1 
    ORDER BY class_name
");
$classes = $stmt->fetchAll();

// Fetch all active academic years
$stmt = $pdo->query("
    SELECT * FROM academic_years 
    WHERE status = 1 
    ORDER BY year_name DESC
");
$academic_years = $stmt->fetchAll();

// Fetch all active semesters
$stmt = $pdo->query("
    SELECT * FROM semesters 
    WHERE status = 1 
    ORDER BY semester_name
");
$semesters = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate lecturer role
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_POST['lecturer_id']]);
        $user = $stmt->fetch();
        
        if ($user['role'] != 'lecturer') {
            throw new Exception("Only lecturers can be assigned modules!");
        }

        // Check for existing assignment
        $stmt = $pdo->prepare("
            SELECT id FROM lecturer_assignments 
            WHERE lecturer_id = ? AND module_id = ? AND class_id = ? 
            AND semester_id = ? AND academic_year_id = ? AND status = 1
        ");
        $stmt->execute([
            $_POST['lecturer_id'],
            $_POST['module_id'],
            $_POST['class_id'],
            $_POST['semester_id'],
            $_POST['academic_year_id']
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("This module assignment already exists!");
        }

        // Create new assignment
        $stmt = $pdo->prepare("
            INSERT INTO lecturer_assignments 
            (lecturer_id, module_id, class_id, semester_id, academic_year_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['lecturer_id'],
            $_POST['module_id'],
            $_POST['class_id'],
            $_POST['semester_id'],
            $_POST['academic_year_id']
        ]);

        $success = "Module assigned successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch existing assignments
$stmt = $pdo->query("
    SELECT 
        la.id,
        u.full_name as lecturer_name,
        m.module_name,
        c.class_name,
        ay.year_name,
        s.semester_name
    FROM lecturer_assignments la
    JOIN users u ON la.lecturer_id = u.id
    JOIN modules m ON la.module_id = m.id
    JOIN classes c ON la.class_id = c.id
    JOIN academic_years ay ON la.academic_year_id = ay.id
    JOIN semesters s ON la.semester_id = s.id
    WHERE la.status = 1
    ORDER BY ay.year_name DESC, s.semester_name, u.full_name
");
$assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Assign Modules - Limkokwing ARS</title>
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

        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        button {
            background: var(--secondary-color);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        button:hover {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>Assign Modules to Lecturers</h2>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <form method="POST">
                <div class="form-group">
                    <label>Lecturer:</label>
                    <select name="lecturer_id" required>
                        <option value="">Select Lecturer</option>
                        <?php foreach ($lecturers as $lecturer): ?>
                            <option value="<?php echo $lecturer['id']; ?>">
                                <?php echo htmlspecialchars($lecturer['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Module:</label>
                    <select name="module_id" required>
                        <option value="">Select Module</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['id']; ?>">
                                <?php echo htmlspecialchars($module['module_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Class:</label>
                    <select name="class_id" required>
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Academic Year:</label>
                    <select name="academic_year_id" required>
                        <option value="">Select Academic Year</option>
                        <?php foreach ($academic_years as $year): ?>
                            <option value="<?php echo $year['id']; ?>">
                                <?php echo htmlspecialchars($year['year_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Semester:</label>
                    <select name="semester_id" required>
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester['id']; ?>">
                                <?php echo htmlspecialchars($semester['semester_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit">Assign Module</button>
            </form>
        </div>

        <h3>Current Assignments</h3>
        <table>
            <thead>
                <tr>
                    <th>Academic Year</th>
                    <th>Semester</th>
                    <th>Lecturer</th>
                    <th>Module</th>
                    <th>Class</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['year_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['semester_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['lecturer_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['module_name']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>