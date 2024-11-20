<?php
/**
 * Class Management
 * 
 * This file handles the management of student classes including:
 * - Adding new classes
 * - Viewing existing classes
 * - Managing class status
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

// Handle form submission for adding new class
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Insert new class
        $stmt = $pdo->prepare("INSERT INTO classes (class_name) VALUES (?)");
        $stmt->execute([$_POST['class_name']]);
        $success = "Class added successfully!";
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch all active classes
$stmt = $pdo->query("SELECT * FROM classes WHERE status = 1 ORDER BY class_name");
$classes = $stmt->fetchAll();
?>
<!-- Rest of the HTML code remains unchanged -->