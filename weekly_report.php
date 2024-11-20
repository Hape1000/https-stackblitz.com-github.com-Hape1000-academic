<?php
require_once 'config.php';

if (!isLoggedIn() || $_SESSION['role'] != 'lecturer') {
    header("Location: index.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get assigned modules with all relevant information
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        m.id as module_id,
        m.module_name,
        c.id as class_id,
        c.class_name,
        s.id as semester_id,
        s.semester_name,
        ay.id as academic_year_id,
        ay.year_name
    FROM lecturer_assignments la
    JOIN modules m ON la.module_id = m.id
    JOIN classes c ON la.class_id = c.id
    JOIN semesters s ON la.semester_id = s.id
    JOIN academic_years ay ON la.academic_year_id = ay.id
    WHERE la.lecturer_id = ? 
    AND la.status = 1 
    AND m.status = 1 
    AND c.status = 1
    AND s.status = 1
    AND ay.status = 1
    ORDER BY ay.year_name DESC, s.semester_name, m.module_name
");
$stmt->execute([$lecturer_id]);
$assignments = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate required fields
        $required_fields = [
            'module_id' => 'Module',
            'report_date' => 'Report Date',
            'chapter_covered' => 'Chapter Covered',
            'learning_outcomes' => 'Learning Outcomes',
            'mode_of_delivery' => 'Mode of Delivery',
            'student_attendance' => 'Student Attendance',
            'challenges' => 'Challenges',
            'recommendations' => 'Recommendations'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                throw new Exception("$label is required!");
            }
        }

        // Find the corresponding class_id for the selected module
        $class_id = null;
        foreach ($assignments as $assignment) {
            if ($assignment['module_id'] == $_POST['module_id']) {
                $class_id = $assignment['class_id'];
                break;
            }
        }

        if (!$class_id) {
            throw new Exception("Invalid module selected!");
        }

        // Check if report exists for this date
        $stmt = $pdo->prepare("
            SELECT id FROM weekly_reports 
            WHERE lecturer_id = ? AND module_id = ? AND class_id = ? AND report_date = ?
        ");
        $stmt->execute([
            $lecturer_id,
            $_POST['module_id'],
            $class_id,
            $_POST['report_date']
        ]);
        
        if ($stmt->fetch()) {
            throw new Exception("A report for this module and date already exists!");
        }

        // Insert report
        $stmt = $pdo->prepare("
            INSERT INTO weekly_reports (
                lecturer_id, module_id, class_id, chapter_covered,
                learning_outcomes, mode_of_delivery, student_attendance,
                challenges, recommendations, malpractice_instances,
                report_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $lecturer_id,
            $_POST['module_id'],
            $class_id,
            $_POST['chapter_covered'],
            $_POST['learning_outcomes'],
            $_POST['mode_of_delivery'],
            $_POST['student_attendance'],
            $_POST['challenges'],
            $_POST['recommendations'],
            $_POST['malpractice'],
            $_POST['report_date']
        ]);

        logAction($lecturer_id, 'Submitted weekly report for module ID: ' . $_POST['module_id']);
        $success = "Report submitted successfully!";
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get current date
$current_date = date('Y-m-d');

// Store form data for repopulating on error
$form_data = $_POST;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Weekly Report - Limkokwing ARS</title>
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
            max-width: 800px;
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
        input[type="number"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        textarea {
            height: 100px;
            resize: vertical;
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

        .no-assignments {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            color: var(--text-dark);
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
        
        <h2>Submit Weekly Report</h2>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($assignments)): ?>
            <div class="no-assignments">
                <p>You currently have no module assignments. Please contact the administrator.</p>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Module & Class:</label>
                    <select name="module_id" required>
                        <option value="">Select Module & Class</option>
                        <?php foreach ($assignments as $assignment): ?>
                            <option value="<?php echo $assignment['module_id']; ?>" 
                                    <?php echo isset($form_data['module_id']) && $form_data['module_id'] == $assignment['module_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(
                                    $assignment['module_name'] . ' - ' . 
                                    $assignment['class_name'] . ' (' . 
                                    $assignment['year_name'] . ' ' . 
                                    $assignment['semester_name'] . ')'
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Report Date:</label>
                    <input type="date" name="report_date" required 
                           max="<?php echo $current_date; ?>" 
                           value="<?php echo isset($form_data['report_date']) ? htmlspecialchars($form_data['report_date']) : $current_date; ?>">
                </div>

                <div class="form-group">
                    <label>Chapter Covered:</label>
                    <input type="text" name="chapter_covered" required 
                           value="<?php echo isset($form_data['chapter_covered']) ? htmlspecialchars($form_data['chapter_covered']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Learning Outcomes:</label>
                    <textarea name="learning_outcomes" required><?php echo isset($form_data['learning_outcomes']) ? htmlspecialchars($form_data['learning_outcomes']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Mode of Delivery:</label>
                    <select name="mode_of_delivery" required>
                        <option value="">Select Mode of Delivery</option>
                        <?php 
                        $delivery_modes = ['Face-to-Face', 'Online', 'Hybrid'];
                        foreach ($delivery_modes as $mode): 
                        ?>
                            <option value="<?php echo $mode; ?>" 
                                    <?php echo isset($form_data['mode_of_delivery']) && $form_data['mode_of_delivery'] == $mode ? 'selected' : ''; ?>>
                                <?php echo $mode; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Student Attendance:</label>
                    <input type="number" name="student_attendance" required min="0" 
                           value="<?php echo isset($form_data['student_attendance']) ? htmlspecialchars($form_data['student_attendance']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>Challenges:</label>
                    <textarea name="challenges" required><?php echo isset($form_data['challenges']) ? htmlspecialchars($form_data['challenges']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Recommendations:</label>
                    <textarea name="recommendations" required><?php echo isset($form_data['recommendations']) ? htmlspecialchars($form_data['recommendations']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label>Assessment Malpractice Instances (if any):</label>
                    <textarea name="malpractice"><?php echo isset($form_data['malpractice']) ? htmlspecialchars($form_data['malpractice']) : ''; ?></textarea>
                </div>

                <button type="submit">Submit Report</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>