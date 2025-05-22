<?php
/**
 * Kitchen Staff Logout
 */
require_once '../includes/config.php';

// Clear kitchen staff session
unset($_SESSION['kitchen_id']);
unset($_SESSION['kitchen_username']);
unset($_SESSION['kitchen_name']);

// Destroy session
session_destroy();

// Redirect to login page
header('Location: index.php');
exit; 