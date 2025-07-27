<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session
session_start();
// Set default timezone for display purposes
date_default_timezone_set('Asia/Manila');

// Define a constant for database timezone (UTC recommended)
define('DB_TIMEZONE', 'UTC');

// Constants
define('BASE_URL', 'http://localhost/test-project/');
define('ADMIN_URL', BASE_URL . 'admin/dashboard.php');