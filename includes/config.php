<?php
/**
 * Configuration file with common includes
 */

// Define the application directory
define('APP_ROOT', dirname(__FILE__, 2));
define('UPLOAD_DIR', APP_ROOT . '/uploads/');
define('DEFAULT_IMAGE', '../assets/images/default-food.jpg');

// Include required files
require_once 'db.php';
require_once 'functions.php';
require_once 'auth_functions.php';

// Error reporting - TEMPORARILY SHOWING ERRORS FOR DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1); // CHANGED from 0 to 1 to show errors
ini_set('log_errors', 1);
ini_set('error_log', APP_ROOT . '/error.log');

// Set default timezone
date_default_timezone_set('UTC'); 