<?php
/**
 * Student Management
 * 
 * This file handles the management of students including:
 * - Adding new students
 * - Updating student information
 * - Managing student status
 * - Changing student classes
 * - Viewing all students
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

// Fetch all active classes for dropdown
$stmt = $pdo->query("SELECT * FROM classes WHERE status = 1 ORDER BY class_name");
$classes = $stmt->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if ($_POST['action'] == 'add') {
            // Validate student number format
            if (!preg_match('/^\d{9}$/', $_POST['student_number'])) {
                throw new Exception("Student number must be exactly 9 digits!");
            }

            // Check for duplicate student number
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_number = ?");
            $stmt->execute([$_POST['student_number']]);
            if ($stmt->fetch()) {
                throw new Exception("Student number already exists!");
            }

            // Add new student
            $stmt = $pdo->prepare("INSERT INTO students (student_number, full_name, class_id) VALUES (?, ?, ?)");
            $stmt->execute([
                $_POST['student_number'],
                $_POST['full_name'],
                $_POST['class_id']
            ]);
            $success = "Student added successfully!";
        }
        elseif ($_POST['action'] == 'toggle_status') {
            // Toggle student status (active/inactive)
            $stmt = $pdo->prepare("UPDATE students SET status = NOT status WHERE id = ?");
            $stmt->execute([$_POST['student_id']]);
            $success = "Student status updated successfully!";
        }
        elseif ($_POST['action'] == 'update_class') {
            // Update student's class
            $stmt = $pdo->prepare("UPDATE students SET class_id = ? WHERE id = ?");
            $stmt->execute([$_POST['new_class_id'], $_POST['student_id']]);
            $success = "Student class updated successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all students with their class names
$stmt = $pdo->query("
    SELECT s.*, c.class_name 
    FROM students s 
    JOIN classes c ON s.class_id = c.id 
    ORDER BY s.status DESC, s.student_number
");
$students = $stmt->fetchAll();
?>
<!-- Rest of the HTML code remains unchanged -->