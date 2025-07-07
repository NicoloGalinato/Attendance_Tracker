<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session
session_start();

// Constants
define('BASE_URL', 'http://localhost/auth-system/');
define('ADMIN_URL', BASE_URL . 'admin/');