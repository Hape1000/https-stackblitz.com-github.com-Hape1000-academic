<?php
/**
 * Module Management
 * 
 * This file handles the management of academic modules including:
 * - Adding new modules
 * - Updating existing modules
 * - Viewing all modules
 * - Managing module status
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
        if ($_POST['action'] == 'add') {
            // Add new module
            $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name) VALUES (?, ?)");
            $stmt->execute([
                $_POST['module_code'],
                $_POST['module_name']
            ]);
            $success = "Module added successfully!";
        } elseif ($_POST['action'] == 'update') {
            // Update existing module
            $stmt = $pdo->prepare("UPDATE modules SET module_code = ?, module_name = ? WHERE id = ?");
            $stmt->execute([
                $_POST['module_code'],
                $_POST['module_name'],
                $_POST['module_id']
            ]);
            $success = "Module updated successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Fetch module for editing if ID is provided
$editing = false;
$edit_module = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ? AND status = 1");
    $stmt->execute([$_GET['edit']]);
    $edit_module = $stmt->fetch();
    if ($edit_module) {
        $editing = true;
    }
}

// Fetch all active modules
$stmt = $pdo->query("SELECT * FROM modules WHERE status = 1 ORDER BY module_code");
$modules = $stmt->fetchAll();
?>
<!-- Rest of the HTML code remains unchanged -->