<?php
session_start();
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/db.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $response['message'] = 'Please enter a valid email address.';
    } else {
        $db = Database::getInstance();
        
        // Check if email already exists
        $existing = $db->fetch(
            "SELECT id FROM newsletter_subscribers WHERE email = ?",
            [$email]
        );
        
        if ($existing) {
            $response['message'] = 'This email is already subscribed to our newsletter.';
        } else {
            // Add new subscriber
            $result = $db->insert(
                'newsletter_subscribers',
                [
                    'email' => $email,
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            );
            
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Thank you for subscribing to our newsletter!';
            } else {
                $response['message'] = 'An error occurred. Please try again later.';
            }
        }
    }
}

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $user = $db->fetch("SELECT session_token FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if (!$user || !isset($_SESSION['session_token']) || $user['session_token'] !== $_SESSION['session_token'] || (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600))) {
        // Session expired or token mismatch
        session_unset();
        session_destroy();
        header('Location: user/login.php?expired=1');
        exit();
    } else {
        $_SESSION['last_activity'] = time(); // Refresh activity
    }
}

// Redirect back with message
$redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
$redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . 'message=' . urlencode($response['message']);
header('Location: ' . $redirect_url);
exit(); 