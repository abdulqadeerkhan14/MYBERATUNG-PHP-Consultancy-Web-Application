<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
$db = Database::getInstance();
// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $db->execute('DELETE FROM support_messages WHERE id = ?', [$id]);
    header('Location: support-messages.php?deleted=1');
    exit();
}

// Handle AJAX support message submission (no admin session required)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && isset($_POST['email'])) {
    $db = Database::getInstance();
    $message = trim($_POST['message']);
    $email = trim($_POST['email']);
    $created_at = date('Y-m-d H:i:s');
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $db->insert('support_messages', [
        'user_id' => $user_id,
        'email' => $email,
        'message' => $message,
        'created_at' => $created_at
    ]);
    // Send email to admin
    $to = ADMIN_EMAIL;
    $subject = 'New Support Message from Website';
    $headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/html; charset=UTF-8\r\n";
    $body = '<h3>New Support Message</h3>' .
            '<p><b>Email:</b> ' . htmlspecialchars($email) . '</p>' .
            '<p><b>Message:</b><br>' . nl2br(htmlspecialchars($message)) . '</p>' .
            '<p><small>Sent at: ' . $created_at . '</small></p>';
    @mail($to, $subject, $body, $headers);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Support message sent successfully!']);
    exit();
}

$messages = $db->fetchAll('SELECT * FROM support_messages ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-headset"></i> Support Messages</h4>
            <a href="dashboard.php" class="btn btn-light btn-sm">Back to Dashboard</a>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Message deleted.</div>
            <?php endif; ?>
            <?php if (empty($messages)): ?>
                <div class="alert alert-info">No support messages found.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Email</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><?php echo $msg['id']; ?></td>
                            <td>
                              <?php
                                if ($msg['user_id']) {
                                  $user = $db->fetch('SELECT first_name, last_name FROM users WHERE id = ?', [$msg['user_id']]);
                                  echo $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'User #' . $msg['user_id'];
                                } else {
                                  echo '-';
                                }
                              ?>
                            </td>
                            <td><?php echo $msg['email'] ? '<a href="mailto:' . htmlspecialchars($msg['email']) . '">' . htmlspecialchars($msg['email']) . '</a>' : '-'; ?></td>
                            <td><?php echo nl2br(htmlspecialchars($msg['message'])); ?></td>
                            <td><?php echo $msg['created_at']; ?></td>
                            <td>
                                <a href="?delete=<?php echo $msg['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html> 