<?php
/**
 * Academic Year and Semester Management
 * 
 * This file handles the management of academic years and semesters including:
 * - Adding new academic years
 * - Adding new semesters
 * - Setting current academic year/semester
 * - Updating academic year/semester details
 * - Viewing all academic years and semesters
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
    try {
        if ($_POST['action'] == 'add_year') {
            // Reset current flag if this year is set as current
            if (isset($_POST['is_current']) && $_POST['is_current'] == 1) {
                $pdo->query("UPDATE academic_years SET is_current = 0");
            }

            // Insert new academic year
            $stmt = $pdo->prepare("
                INSERT INTO academic_years (year_name, is_current) 
                VALUES (?, ?)
            ");
            $stmt->execute([
                $_POST['year_name'],
                isset($_POST['is_current']) ? 1 : 0
            ]);
            $success = "Academic year added successfully!";
        }
        elseif ($_POST['action'] == 'add_semester') {
            // Reset current flag if this semester is set as current
            if (isset($_POST['is_current']) && $_POST['is_current'] == 1) {
                $pdo->query("UPDATE semesters SET is_current = 0");
            }

            // Insert new semester
            $stmt = $pdo->prepare("
                INSERT INTO semesters (
                    semester_name, academic_year_id, 
                    start_date, end_date, is_current
                ) VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['semester_name'],
                $_POST['academic_year_id'],
                $_POST['start_date'],
                $_POST['end_date'],
                isset($_POST['is_current']) ? 1 : 0
            ]);
            $success = "Semester added successfully!";
        }
        elseif ($_POST['action'] == 'update_year') {
            // Reset current flag if this year is set as current
            if (isset($_POST['is_current']) && $_POST['is_current'] == 1) {
                $pdo->query("UPDATE academic_years SET is_current = 0");
            }

            // Update academic year
            $stmt = $pdo->prepare("
                UPDATE academic_years 
                SET year_name = ?, is_current = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['year_name'],
                isset($_POST['is_current']) ? 1 : 0,
                $_POST['status'],
                $_POST['year_id']
            ]);
            $success = "Academic year updated successfully!";
        }
        elseif ($_POST['action'] == 'update_semester') {
            // Reset current flag if this semester is set as current
            if (isset($_POST['is_current']) && $_POST['is_current'] == 1) {
                $pdo->query("UPDATE semesters SET is_current = 0");
            }

            // Update semester
            $stmt = $pdo->prepare("
                UPDATE semesters 
                SET semester_name = ?, academic_year_id = ?, 
                    start_date = ?, end_date = ?, 
                    is_current = ?, status = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['semester_name'],
                $_POST['academic_year_id'],
                $_POST['start_date'],
                $_POST['end_date'],
                isset($_POST['is_current']) ? 1 : 0,
                $_POST['status'],
                $_POST['semester_id']
            ]);
            $success = "Semester updated successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all academic years
$stmt = $pdo->query("SELECT * FROM academic_years ORDER BY year_name DESC");
$academic_years = $stmt->fetchAll();

// Fetch all semesters with academic year info
$stmt = $pdo->query("
    SELECT s.*, ay.year_name 
    FROM semesters s 
    JOIN academic_years ay ON s.academic_year_id = ay.id 
    ORDER BY ay.year_name DESC, s.semester_name
");
$semesters = $stmt->fetchAll();
?>
<!-- Rest of the HTML code remains unchanged -->