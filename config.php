<?php
// config.php - Admin Configuration
session_start();

// Include your existing database connection
require_once 'db.php';

// Check if admin is logged in for protected pages
function checkAdminLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin-login.php');
        exit();
    }
}

// Get database connection
$conn = getDB();

// Handle any initialization or common functions
?>