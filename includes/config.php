<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'consultant');

// Site configuration
define('SITE_NAME', 'Visa Consultancy Services');
define('SITE_URL', 'http://localhost/consultancy');
define('ADMIN_EMAIL', 'admin@visaconsultancy.com');
define('SITE_EMAIL', 'info@visaconsultancy.com');

// Session configuration
// session_start(); // Commented out to avoid conflicts

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone
date_default_timezone_set('UTC');

// Security
define('HASH_COST', 12); // For password hashing

// File upload settings
define('UPLOAD_DIR', '../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Prevent direct access to this file
// if (!defined('SECURE_ACCESS')) {
//     die('Direct access not permitted');
// }