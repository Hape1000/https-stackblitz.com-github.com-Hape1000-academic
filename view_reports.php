<?php
require_once 'config.php';

// Allow both admin and PRL to access reports
if (!isLoggedIn() || !in_array($_SESSION['role'], ['admin', 'prl'])) {
    header("Location: index.php");
    exit();
}

$is_admin = $_SESSION['role'] === 'admin';

// Get filter values
$academic_year = isset($_GET['academic_year']) ? $_GET['academic_year'] : '';
$semester = isset($_GET['semester']) ? $_GET['semester'] : '';
$module = isset($_GET['module']) ? $_GET['module'] : '';
$lecturer = isset($_GET['lecturer']) ? $_GET['lecturer'] : '';
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'weekly';

// Base query for weekly reports
$weekly_query = "
    SELECT DISTINCT
           wr.id,
           wr.chapter_covered,
           wr.learning_outcomes,
           wr.mode_of_delivery,
           wr.student_attendance,
           wr.challenges,
           wr.recommendations,
           wr.malpractice_instances,
           wr.report_date,
           wr.created_at,
           u.full_name as lecturer_name,
           u.employee_number as lecturer_employee_number,
           m.module_name,
           c.class_name,
           ay.year_name,
           s.semester_name
    FROM weekly_reports wr
    JOIN users u ON wr.lecturer_id = u.id
    JOIN modules m ON wr.module_id = m.id
    JOIN classes c ON wr.class_id = c.id
    JOIN lecturer_assignments la ON (
        wr.lecturer_id = la.lecturer_id AND 
        wr.module_id = la.module_id AND 
        wr.class_id = la.class_id
    )
    JOIN academic_years ay ON la.academic_year_id = ay.id
    JOIN semesters s ON la.semester_id = s.id
    WHERE 1=1
";

// Base query for attendance reports
$attendance_query = "
    SELECT 
        a.date,
        u.full_name as lecturer_name,
        u.employee_number as lecturer_employee_number,
        m.module_name,
        c.class_name,
        s.student_number,
        s.full_name as student_name,
        a.status,
        ay.year_name,
        sem.semester_name
    FROM attendance a
    JOIN users u ON a.lecturer_id = u.id
    JOIN modules m ON a.module_id = m.id
    JOIN classes c ON a.class_id = c.id
    JOIN students s ON a.student_id = s.id
    JOIN lecturer_assignments la ON (
        a.lecturer_id = la.lecturer_id AND 
        a.module_id = la.module_id AND 
        a.class_id = la.class_id
    )
    JOIN academic_years ay ON la.academic_year_id = ay.id
    JOIN semesters sem ON la.semester_id = sem.id
    WHERE 1=1
";

$params = [];

if ($academic_year) {
    $weekly_query .= " AND ay.id = ?";
    $attendance_query .= " AND ay.id = ?";
    $params[] = $academic_year;
}
if ($semester) {
    $weekly_query .= " AND s.id = ?";
    $attendance_query .= " AND sem.id = ?";
    $params[] = $semester;
}
if ($module) {
    $weekly_query .= " AND m.id = ?";
    $attendance_query .= " AND m.id = ?";
    $params[] = $module;
}
if ($lecturer) {
    $weekly_query .= " AND u.id = ?";
    $attendance_query .= " AND u.id = ?";
    $params[] = $lecturer;
}

$weekly_query .= " ORDER BY wr.report_date DESC, wr.created_at DESC";
$attendance_query .= " ORDER BY a.date DESC, m.module_name, s.full_name";

// Execute appropriate query based on report type
if ($report_type === 'weekly') {
    $stmt = $pdo->prepare($weekly_query);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare($attendance_query);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll();
}

// Get filter options
$stmt = $pdo->query("SELECT DISTINCT id, year_name FROM academic_years WHERE status = 1 ORDER BY year_name DESC");
$academic_years = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT id, semester_name FROM semesters WHERE status = 1 ORDER BY semester_name");
$semesters = $stmt->fetchAll();

$stmt = $pdo->query("SELECT DISTINCT id, module_name FROM modules WHERE status = 1 ORDER BY module_name");
$modules = $stmt->fetchAll();

// For admin, include employee number in lecturer selection
if ($is_admin) {
    $stmt = $pdo->query("SELECT DISTINCT id, full_name, employee_number FROM users WHERE role = 'lecturer' ORDER BY full_name");
} else {
    $stmt = $pdo->query("SELECT DISTINCT id, full_name FROM users WHERE role = 'lecturer' ORDER BY full_name");
}
$lecturers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Reports - Limkokwing ARS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Previous CSS styles remain unchanged */
        .employee-number {
            color: #666;
            font-size: 0.9em;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <h2>Reports Overview</h2>

        <div class="report-type-selector">
            <button class="report-type-btn <?php echo $report_type === 'weekly' ? 'active' : ''; ?>" 
                    onclick="window.location.href='?report_type=weekly'">
                Weekly Reports
            </button>
            <button class="report-type-btn <?php echo $report_type === 'attendance' ? 'active' : ''; ?>"
                    onclick="window.location.href='?report_type=attendance'">
                Attendance Reports
            </button>
        </div>

        <form method="GET" class="filters">
            <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
            
            <div class="filter-group">
                <label>Academic Year:</label>
                <select name="academic_year" onchange="this.form.submit()">
                    <option value="">All Years</option>
                    <?php foreach ($academic_years as $year): ?>
                        <option value="<?php echo $year['id']; ?>" 
                                <?php echo $academic_year == $year['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($year['year_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Semester:</label>
                <select name="semester" onchange="this.form.submit()">
                    <option value="">All Semesters</option>
                    <?php foreach ($semesters as $sem): ?>
                        <option value="<?php echo $sem['id']; ?>"
                                <?php echo $semester == $sem['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sem['semester_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Module:</label>
                <select name="module" onchange="this.form.submit()">
                    <option value="">All Modules</option>
                    <?php foreach ($modules as $mod): ?>
                        <option value="<?php echo $mod['id']; ?>"
                                <?php echo $module == $mod['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($mod['module_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label>Lecturer:</label>
                <select name="lecturer" onchange="this.form.submit()">
                    <option value="">All Lecturers</option>
                    <?php foreach ($lecturers as $lect): ?>
                        <option value="<?php echo $lect['id']; ?>"
                                <?php echo $lecturer == $lect['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lect['full_name']); ?>
                            <?php if ($is_admin && isset($lect['employee_number'])): ?>
                                (<?php echo htmlspecialchars($lect['employee_number']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <?php if ($report_type === 'weekly'): ?>
            <?php if (empty($reports)): ?>
                <div class="no-reports">
                    <p>No weekly reports found matching the selected criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($reports as $report): ?>
                    <div class="report-card">
                        <div class="report-header">
                            <div class="report-meta">
                                <strong>Lecturer:</strong> 
                                <?php echo htmlspecialchars($report['lecturer_name']); ?>
                                <?php if ($is_admin): ?>
                                    <span class="employee-number">
                                        (<?php echo htmlspecialchars($report['lecturer_employee_number']); ?>)
                                    </span>
                                <?php endif; ?>
                                <br>
                                <strong>Module:</strong> <?php echo htmlspecialchars($report['module_name']); ?><br>
                                <strong>Class:</strong> <?php echo htmlspecialchars($report['class_name']); ?><br>
                                <strong>Academic Year:</strong> <?php echo htmlspecialchars($report['year_name']); ?><br>
                                <strong>Semester:</strong> <?php echo htmlspecialchars($report['semester_name']); ?>
                            </div>
                            <div class="report-meta">
                                <strong>Report Date:</strong> <?php echo date('d M Y', strtotime($report['report_date'])); ?><br>
                                <strong>Submitted:</strong> <?php echo date('d M Y H:i', strtotime($report['created_at'])); ?>
                            </div>
                        </div>

                        <div class="report-content">
                            <div class="report-section">
                                <h4>Chapter Covered</h4>
                                <p><?php echo htmlspecialchars($report['chapter_covered']); ?></p>
                            </div>

                            <div class="report-section">
                                <h4>Learning Outcomes</h4>
                                <p><?php echo nl2br(htmlspecialchars($report['learning_outcomes'])); ?></p>
                            </div>

                            <div class="report-section">
                                <h4>Mode of Delivery</h4>
                                <p><?php echo htmlspecialchars($report['mode_of_delivery']); ?></p>
                            </div>

                            <div class="report-section">
                                <h4>Student Attendance</h4>
                                <p><?php echo htmlspecialchars($report['student_attendance']); ?> students</p>
                            </div>

                            <div class="report-section">
                                <h4>Challenges</h4>
                                <p><?php echo nl2br(htmlspecialchars($report['challenges'])); ?></p>
                            </div>

                            <div class="report-section">
                                <h4>Recommendations</h4>
                                <p><?php echo nl2br(htmlspecialchars($report['recommendations'])); ?></p>
                            </div>

                            <?php if ($report['malpractice_instances']): ?>
                                <div class="report-section">
                                    <h4>Assessment Malpractice Instances</h4>
                                    <p><?php echo nl2br(htmlspecialchars($report['malpractice_instances'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <?php if (empty($attendance_records)): ?>
                <div class="no-reports">
                    <p>No attendance records found matching the selected criteria.</p>
                </div>
            <?php else: ?>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Academic Year</th>
                            <th>Semester</th>
                            <th>Module</th>
                            <th>Class</th>
                            <th>Lecturer</th>
                            <?php if ($is_admin): ?>
                                <th>Employee Number</th>
                            <?php endif; ?>
                            <th>Student Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($record['date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['year_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['semester_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['module_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['lecturer_name']); ?></td>
                                <?php if ($is_admin): ?>
                                    <td><?php echo htmlspecialchars($record['lecturer_employee_number']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($record['student_number']); ?></td>
                                <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                <td>
                                    <span class="attendance-status status-<?php echo $record['status']; ?>">
                                        <?php echo ucfirst($record['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>