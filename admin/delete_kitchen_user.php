<?php
/**
 * Delete Kitchen Staff User
 * Admin tool to remove kitchen staff accounts
 */
require_once '../includes/config.php';

// Require admin login
requireLogin();

// Check for staff ID
$staffId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($staffId <= 0) {
    header('Location: add_kitchen_user.php');
    exit;
}

try {
    $pdo = getDbConnection();
    
    // Get staff information first (for confirmation message)
    $stmt = $pdo->prepare("SELECT username, name FROM kitchen_staff WHERE id = ?");
    $stmt->execute([$staffId]);
    $staffInfo = $stmt->fetch();
    
    if (!$staffInfo) {
        // Staff doesn't exist, redirect back
        header('Location: add_kitchen_user.php');
        exit;
    }
    
    // Delete the staff
    $stmt = $pdo->prepare("DELETE FROM kitchen_staff WHERE id = ?");
    $stmt->execute([$staffId]);
    
    // Set success message in session
    $_SESSION['kitchen_user_deleted'] = true;
    $_SESSION['deleted_kitchen_name'] = $staffInfo['name'];
    
} catch (PDOException $e) {
    error_log('Delete kitchen user error: ' . $e->getMessage());
    // Set error message in session
    $_SESSION['kitchen_user_error'] = 'Failed to delete kitchen staff account. Please try again.';
}

// Redirect back to kitchen staff page
header('Location: add_kitchen_user.php');
exit; 