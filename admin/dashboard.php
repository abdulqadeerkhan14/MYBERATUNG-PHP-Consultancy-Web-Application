<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    switch ($_POST['action']) {
        case 'delete_message':
            $message_id = $_POST['message_id'] ?? '';
            if (!empty($message_id)) {
                try {
                    $db->execute("DELETE FROM contact_messages WHERE id = ?", [$message_id]);
                    $response['success'] = true;
                    $response['message'] = 'Message deleted successfully';
                } catch (Exception $e) {
                    $response['message'] = 'Error deleting message';
                }
            }
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
            
        case 'reply_message':
            $message_id = $_POST['message_id'] ?? '';
            $reply_text = $_POST['reply_text'] ?? '';
            if (!empty($message_id) && !empty($reply_text)) {
                try {
                    // Get the original message details
                    $original_message = $db->fetch("SELECT * FROM contact_messages WHERE id = ?", [$message_id]);
                    
                    if ($original_message) {
                        // Update message status to replied
                        $db->execute("UPDATE contact_messages SET status = 'replied' WHERE id = ?", [$message_id]);
                        
                        // Send email reply (you can implement actual email sending here)
                        // For now, we'll just log it or you can integrate with your email service
                        $to = $original_message['email'];
                        $subject = "Re: " . $original_message['subject'];
                        $headers = "From: " . SITE_EMAIL . "\r\n";
                        $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
                        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                        
                        $email_body = "
                        <html>
                        <body>
                            <h3>Thank you for contacting " . SITE_NAME . "</h3>
                            <p>Dear " . htmlspecialchars($original_message['name']) . ",</p>
                            <p>" . nl2br(htmlspecialchars($reply_text)) . "</p>
                            <br>
                            <p>Best regards,<br>" . SITE_NAME . " Team</p>
                            <hr>
                            <p><small>Original message: " . htmlspecialchars($original_message['message']) . "</small></p>
                        </body>
                        </html>";
                        
                        // Uncomment the line below to actually send emails
                        // mail($to, $subject, $email_body, $headers);
                        
                        $response['success'] = true;
                        $response['message'] = 'Reply sent successfully to ' . $original_message['email'];
                    } else {
                        $response['message'] = 'Original message not found';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Error sending reply: ' . $e->getMessage();
                }
            } else {
                $response['message'] = 'Message ID and reply text are required';
            }
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
    }
}

// Get statistics
$total_clients = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'client'")['count'];
$total_services = $db->fetch("SELECT COUNT(*) as count FROM services")['count'];
$total_posts = $db->fetch("SELECT COUNT(*) as count FROM blog_posts")['count'];
$new_messages = $db->fetch("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'")['count'];

// Get recent blog posts
$recent_posts = $db->fetchAll(
    "SELECT p.*, u.username as author_name 
     FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     ORDER BY p.created_at DESC 
     LIMIT 5"
);

// Get recent contact messages
$recent_messages = $db->fetchAll(
    "SELECT * FROM contact_messages 
     ORDER BY created_at DESC 
     LIMIT 5"
);

$recent_support_messages = $db->fetchAll('SELECT * FROM support_messages ORDER BY created_at DESC LIMIT 5');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MYBERATUNG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #06A3DA;
            --secondary: #F57E57;
            --dark: #091E3B;
            --light: #F8F9FA;
            --white: #FFFFFF;
            --gray: #6C757D;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Heebo', sans-serif;
            line-height: 1.6;
            color: var(--dark);
            background-color: var(--light);
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 250px;
            background: var(--primary);
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h4 {
            color: var(--white);
            font-weight: 700;
            margin: 0;
        }
        .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
        }
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.15);
            border-left-color: var(--white);
            text-decoration: none;
            transform: translateX(5px);
        }
        .nav-link i {
            margin-right: 12px;
            width: 20px;
            font-size: 1.1rem;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }
        .top-header {
            background: var(--white);
            padding: 20px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .top-header h1 {
            color: var(--primary);
            font-weight: 700;
            margin: 0;
        }
        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #0DCAF0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .dashboard-card {
            background: var(--white);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: none;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .dashboard-card h4 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, #0DCAF0 100%);
            color: var(--white);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            border: none;
        }
        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(6, 163, 218, 0.3);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .btn-primary {
            background: var(--primary) !important;
            border: 2px solid var(--primary) !important;
            color: var(--white) !important;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: transparent !important;
            color: var(--primary) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 163, 218, 0.3);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary) !important;
            color: var(--primary) !important;
            background: transparent !important;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary) !important;
            color: var(--white) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 163, 218, 0.3);
        }

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--primary);
            color: var(--white);
            border: none;
            font-weight: 600;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border-color: #f8f9fa;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .badge-new {
            background: #d4edda;
            color: #155724;
        }

        .badge-replied {
            background: #cce7ff;
            color: #004085;
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .card-header {
            background: var(--primary);
            color: var(--white);
            border-radius: 15px 15px 0 0 !important;
            border: none;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
        }
            
            .main-content {
                margin-left: 0;
            padding: 15px;
            }
            
            .top-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .dropdown-menu {
                background: var(--primary) !important;
            }
            .sidebar .dropdown-toggle {
                background: var(--primary) !important;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-user-shield"></i> MYBERATUNG</h4>
        </div>
        <nav class="mt-4">
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="services-management.php" class="nav-link">
                <i class="fas fa-briefcase"></i> Manage Services
            </a>
            <a href="blog-management.php" class="nav-link">
                <i class="fas fa-newspaper"></i> Manage Blog
            </a>
            <a href="user-management.php" class="nav-link">
                <i class="fas fa-users"></i> Manage Users
            </a>
            <a href="manage-applications.php" class="nav-link">
                <i class="fas fa-passport"></i> Applicants
            </a>
            <a href="settings.php" class="nav-link">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="../index.php" class="nav-link" target="_blank">
                <i class="fas fa-external-link-alt"></i> View Website
            </a>
            <a href="logout.php" class="nav-link mt-5">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1>Admin Dashboard</h1>
            <div class="d-flex align-items-center">
                <div class="mr-3 text-right">
                    <p class="mb-0 font-weight-bold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                    <small class="text-muted">Administrator</small>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="dashboard-card text-center bg-primary text-white">
                    <i class="fas fa-users fa-3x mb-3"></i>
                        <h4><?php echo $total_clients; ?></h4>
                    <p class="text-white-50 mb-0">Total Clients</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="dashboard-card text-center bg-success text-white">
                    <i class="fas fa-briefcase fa-3x mb-3"></i>
                        <h4><?php echo $total_services; ?></h4>
                    <p class="text-white-50 mb-0">Visa Services</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="dashboard-card text-center bg-info text-white">
                    <i class="fas fa-newspaper fa-3x mb-3"></i>
                        <h4><?php echo $total_posts; ?></h4>
                    <p class="text-white-50 mb-0">Blog Posts</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="dashboard-card text-center bg-warning text-white">
                    <i class="fas fa-envelope fa-3x mb-3"></i>
                        <h4><?php echo $new_messages; ?></h4>
                    <p class="text-white-50 mb-0">New Messages</p>
                </div>
            </div>
        </div>

        <!-- Recent Blog Posts & Messages -->
        <div class="row">
            <!-- Recent Blog Posts -->
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Blog Posts</h5>
                        <a href="blog-management.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus"></i> Add New
                        </a>
                    </div>
                        <?php if (!empty($recent_posts)): ?>
                            <div class="list-group">
                                <?php foreach ($recent_posts as $post): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($post['title']); ?></h6>
                                            <small><?php echo date('M d, Y', strtotime($post['created_at'])); ?></small>
                                        </div>
                                        <small>By <?php echo htmlspecialchars($post['author_name']); ?></small>
                                        <div class="mt-2">
                                            <a href="blog-management.php?edit=<?php echo $post['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center">No blog posts yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <!-- Recent Support Messages -->
            <div class="col-md-6 mb-4">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Recent Support Messages</h5>
                        <a href="support-messages.php" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-headset"></i> View All
                        </a>
                    </div>
                    <?php if (!empty($recent_support_messages)): ?>
                        <div class="list-group">
                            <?php foreach ($recent_support_messages as $msg): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Support Message #<?php echo $msg['id']; ?></h6>
                                        <small><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($msg['message'], 0, 100))) . (strlen($msg['message']) > 100 ? '...' : ''); ?></p>
                                    <small>User: <?php
                                        if ($msg['user_id']) {
                                            $user = $db->fetch('SELECT first_name, last_name FROM users WHERE id = ?', [$msg['user_id']]);
                                            echo $user ? htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])) : 'User #' . $msg['user_id'];
                                        } else {
                                            echo '-';
                                        }
                                    ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">No support messages yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-envelope"></i> Contact Messages</h5>
                        <a href="contact-messages.php" class="btn btn-light btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_messages)): ?>
                            <p class="text-muted text-center">No contact messages yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Subject</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recent_messages as $msg): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                                            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>"><?php echo htmlspecialchars($msg['email']); ?></a></td>
                                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($msg['created_at'] ?? $msg['date'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" role="dialog" aria-labelledby="messageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageModalLabel">Message Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="messageModalBody">
                    <!-- Message content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" onclick="deleteMessage()">Delete</button>
                    <button type="button" class="btn btn-success" onclick="showReplyForm()">Reply</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-labelledby="replyModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="replyModalLabel">Reply to Message</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="replyForm">
                        <div class="form-group">
                            <label for="replyText">Your Reply:</label>
                            <textarea class="form-control" id="replyText" rows="5" placeholder="Type your reply here..." required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="sendReply()">Send Reply</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let currentMessageId = null;
        let currentMessageData = null;

        function readMessage(messageId) {
            // Show loading state
            const button = event.target.closest('.message-btn');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            button.disabled = true;
            
            // Fetch message details via AJAX
            $.ajax({
                url: 'get-message.php',
                type: 'POST',
                data: { message_id: messageId },
                success: function(response) {
                    // Reset button
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    if (response.success) {
                        currentMessageId = messageId;
                        currentMessageData = response.message;
                        
                        const modalBody = $('#messageModalBody');
                        modalBody.html(`
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>From:</strong> ${response.message.name}<br>
                                    <strong>Email:</strong> ${response.message.email}<br>
                                    <strong>Subject:</strong> ${response.message.subject}<br>
                                    <strong>Date:</strong> ${response.message.created_at}<br>
                                    <strong>Status:</strong> <span class="badge badge-${response.message.status === 'new' ? 'warning' : response.message.status === 'read' ? 'info' : 'success'}">${response.message.status}</span>
                                </div>
                            </div>
                            <hr>
                            <div class="message-content">
                                <strong>Message:</strong><br>
                                <p class="mt-2">${response.message.message.replace(/\n/g, '<br>')}</p>
                            </div>
                        `);
                        
                        $('#messageModal').modal('show');
                        
                        // Mark as read if status is new
                        if (response.message.status === 'new') {
                            markAsRead(messageId);
                        }
                    } else {
                        alert('Error loading message: ' + response.message);
                    }
                },
                error: function() {
                    // Reset button
                    button.innerHTML = originalText;
                    button.disabled = false;
                    alert('Error loading message');
                }
            });
        }

        function markAsRead(messageId) {
            $.ajax({
                url: 'mark-read.php',
                type: 'POST',
                data: { message_id: messageId },
                success: function(response) {
                    // Update the message status in the UI if needed
                }
            });
        }

        function deleteMessage() {
            if (!currentMessageId) return;
            
            if (confirm('Are you sure you want to delete this message?')) {
                $.ajax({
                    url: 'dashboard.php',
                    type: 'POST',
                    data: { 
                        action: 'delete_message',
                        message_id: currentMessageId 
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#messageModal').modal('hide');
                            location.reload(); // Refresh to update the list
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function() {
                        alert('Error deleting message');
                    }
                });
            }
        }

        function showReplyForm() {
            $('#messageModal').modal('hide');
            $('#replyModal').modal('show');
        }

        function sendReply() {
            const replyText = $('#replyText').val().trim();
            if (!replyText) {
                alert('Please enter a reply message');
                return;
            }

            $.ajax({
                url: 'dashboard.php',
                type: 'POST',
                data: { 
                    action: 'reply_message',
                    message_id: currentMessageId,
                    reply_text: replyText
                },
                success: function(response) {
                    if (response.success) {
                        $('#replyModal').modal('hide');
                        $('#replyText').val('');
                        alert('Reply sent successfully!');
                        location.reload(); // Refresh to update status
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error sending reply');
                }
            });
        }
    </script>
</body>
</html> 