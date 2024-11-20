<?php
require_once 'config.php';

// Log the logout action if user is currently logged in
if (isLoggedIn()) {
    logAction($_SESSION['user_id'], 'User logged out');
    session_destroy();
}

// Redirect to login page
header("Location: index.php");
exit();
?>