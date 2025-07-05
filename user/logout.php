<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';

session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit();
?> 