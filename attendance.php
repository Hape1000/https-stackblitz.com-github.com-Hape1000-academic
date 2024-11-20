<?php
require_once 'config.php';

if (!isLoggedIn() || ($_SESSION['role'] != 'lecturer' && $_SESSION['role'] != 'prl')) {
    header("Location: index.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get assigned modules and classes
$stmt = $pdo->prepare("
    SELECT DISTINCT m.id as module_id, m.module_name, c.id as class_id, c.class_name 
    FROM lecturer_assignments la
    JOIN modules m ON la.module_id = m.id
    JOIN classes c ON la.class_id = c.id
    WHERE la.lecturer_id = ? AND la.status = 1
");
$stmt->execute([$lecturer_id]);
$assignments = $stmt->fetchAll();

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_attendance'])) {
    try {
        $pdo->beginTransaction();

        $module_id = $_POST['module_id'];
        $class_id = $_POST['class_id'];
        $date = $_POST['date'];
        $students = $_POST['attendance'] ?? [];

        // Delete existing attendance for this date/module/class
        $stmt = $pdo->prepare("
            DELETE FROM attendance 
            WHERE module_id = ? AND class_id = ? AND date = ? AND lecturer_id = ?
        ");
        $stmt->execute([$module_id, $class_id, $date, $lecturer_id]);

        // Insert new attendance records
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, module_id, class_id, lecturer_id, status, date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($students as $student_id => $status) {
            $stmt->execute([
                $student_id,
                $module_id,
                $class_id,
                $lecturer_id,
                $status,
                $date
            ]);
        }

        $pdo->commit();
        $success = "Attendance recorded successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error recording attendance: " . $e->getMessage();
    }
}

// Get students for selected class
$students = [];
if (isset($_GET['class_id'])) {
    $stmt = $pdo->prepare("
        SELECT id, student_number, full_name 
        FROM students 
        WHERE class_id = ? AND status = 1 
        ORDER BY full_name
    ");
    $stmt->execute([$_GET['class_id']]);
    $students = $stmt->fetchAll();
}

// Get existing attendance for selected date and class
$existing_attendance = [];
if (isset($_GET['class_id']) && isset($_GET['date'])) {
    $stmt = $pdo->prepare("
        SELECT student_id, status 
        FROM attendance 
        WHERE class_id = ? AND date = ? AND lecturer_id = ?
    ");
    $stmt->execute([$_GET['class_id'], $_GET['date'], $lecturer_id]);
    while ($row = $stmt->fetch()) {
        $existing_attendance[$row['student_id']] = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Mark Attendance - Limkokwing ARS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }
        .container {
            max-width: 1000px;
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
        }
        select, input[type="date"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
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
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #45a049;
        }
        .success { 
            color: #2ecc71;
            background: rgba(46, 204, 113, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .error { 
            color: #e74c3c;
            background: rgba(231, 76, 60, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .nav { 
            margin-bottom: 20px;
        }
        .nav a { 
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .attendance-controls {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .radio-group {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .radio-group label {
            display: inline;
            margin: 0;
            cursor: pointer;
        }
        .mark-all-btn {
            background: #3498db;
            margin-left: 10px;
        }
        .mark-all-btn:hover {
            background: #2980b9;
        }
        .status-present {
            color: #2ecc71;
        }
        .status-absent {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>Mark Attendance</h2>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="GET">
            <div class="form-group">
                <label>Select Module & Class:</label>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">Select Module & Class</option>
                    <?php foreach ($assignments as $assignment): ?>
                        <option value="<?php echo $assignment['class_id']; ?>" 
                                <?php echo (isset($_GET['class_id']) && $_GET['class_id'] == $assignment['class_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($assignment['module_name'] . ' - ' . $assignment['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if (!empty($students)): ?>
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="module_id" value="<?php echo $assignments[0]['module_id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo $_GET['class_id']; ?>">
                
                <div class="form-group">
                    <label>Date:</label>
                    <input type="text" name="date" class="datepicker" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="attendance-controls">
                    <button type="button" class="mark-all-btn" onclick="markAllPresent()">Mark All Present</button>
                    <button type="button" class="mark-all-btn" onclick="markAllAbsent()">Mark All Absent</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Name</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td>
                                    <div class="radio-group">
                                        <label>
                                            <input type="radio" 
                                                   name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="present" 
                                                   <?php echo (!isset($existing_attendance[$student['id']]) || 
                                                             $existing_attendance[$student['id']] == 'present') ? 'checked' : ''; ?>>
                                            <span class="status-present">Present</span>
                                        </label>
                                        <label>
                                            <input type="radio" 
                                                   name="attendance[<?php echo $student['id']; ?>]" 
                                                   value="absent"
                                                   <?php echo (isset($existing_attendance[$student['id']]) && 
                                                             $existing_attendance[$student['id']] == 'absent') ? 'checked' : ''; ?>>
                                            <span class="status-absent">Absent</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" name="submit_attendance" style="margin-top: 20px;">Save Attendance</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Initialize date picker
        flatpickr(".datepicker", {
            dateFormat: "Y-m-d",
            maxDate: "today"
        });

        // Function to mark all students as present
        function markAllPresent() {
            const radios = document.querySelectorAll('input[type="radio"][value="present"]');
            radios.forEach(radio => radio.checked = true);
        }

        // Function to mark all students as absent
        function markAllAbsent() {
            const radios = document.querySelectorAll('input[type="radio"][value="absent"]');
            radios.forEach(radio => radio.checked = true);
        }
    </script>
</body>
</html>