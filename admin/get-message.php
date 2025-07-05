<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => '', 'message' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'])) {
    $message_id = $_POST['message_id'] ?? '';
    
    if (!empty($message_id)) {
        try {
            $db = Database::getInstance();
            $message = $db->fetch("SELECT * FROM contact_messages WHERE id = ?", [$message_id]);
            
            if ($message) {
                $response['success'] = true;
                $response['message'] = $message;
            } else {
                $response['message'] = 'Message not found';
            }
        } catch (Exception $e) {
            $response['message'] = 'Error retrieving message';
        }
    } else {
        $response['message'] = 'Invalid message ID';
    }
} else {
    $response['message'] = 'Invalid request';
}

header('Content-Type: application/json');
echo json_encode($response);
?> 