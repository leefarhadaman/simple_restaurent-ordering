<?php
/**
 * Logout handler
 */
require_once '../includes/config.php';

// Log out and redirect to login page
logout();
header('Location: login.php');
exit; 