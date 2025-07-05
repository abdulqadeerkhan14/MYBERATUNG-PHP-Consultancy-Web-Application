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
    $db->execute('DELETE FROM contact_messages WHERE id = ?', [$id]);
    header('Location: contact-messages.php?deleted=1');
    exit();
}
// Handle reply
$reply_success = '';
$reply_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_id'])) {
    $reply_id = intval($_POST['reply_id']);
    $reply_text = trim($_POST['reply_text'] ?? '');
    $msg = $db->fetch('SELECT * FROM contact_messages WHERE id = ?', [$reply_id]);
    if ($msg && !empty($reply_text)) {
        $to = $msg['email'];
        $subject = 'Re: ' . $msg['subject'];
        $headers = "From: " . SITE_EMAIL . "\r\n";
        $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body = "<html><body>"
            . "<h3 style='color:#06A3DA;'>Thank you for contacting " . SITE_NAME . "</h3>"
            . "<p>Dear <strong>" . htmlspecialchars($msg['name']) . "</strong>,</p>"
            . "<div style='margin:18px 0;padding:16px;background:#f8f9fa;border-radius:8px;border:1px solid #e3e6f0;'>"
            . nl2br(htmlspecialchars($reply_text)) . "</div>"
            . "<p style='margin-top:24px;'>Best regards,<br><strong>" . SITE_NAME . " Team</strong></p>"
            . "<hr><p style='color:#888;font-size:13px;'><b>Original message:</b><br>" . nl2br(htmlspecialchars($msg['message'])) . "</p>"
            . "</body></html>";
        if (@mail($to, $subject, $body, $headers)) {
            $db->execute("UPDATE contact_messages SET status = 'replied' WHERE id = ?", [$reply_id]);
            $reply_success = 'Reply sent successfully to ' . htmlspecialchars($to);
        } else {
            $reply_error = 'Failed to send email. Please check your server mail configuration.';
        }
    } else {
        $reply_error = 'Message not found or reply text is empty.';
    }
}
$messages = $db->fetchAll('SELECT * FROM contact_messages ORDER BY created_at DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; }
        .admin-card {
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(6,163,218,0.08);
            border: none;
            margin-top: 40px;
        }
        .admin-card .card-header {
            border-radius: 18px 18px 0 0;
            background: linear-gradient(90deg, #06A3DA 0%, #0DCAF0 100%);
            color: #fff;
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }
        .admin-card .btn-light { color: #06A3DA; border: 1px solid #06A3DA; }
        .admin-card .btn-light:hover { background: #06A3DA; color: #fff; }
        .table thead th {
            background: #f8f9fa;
            color: #091E3B;
            font-weight: 600;
            border-bottom: 2px solid #e3e6f0;
        }
        .table td, .table th { vertical-align: middle; }
        .badge-status {
            font-size: 0.95em;
            padding: 0.45em 1em;
            border-radius: 12px;
            font-weight: 500;
        }
        .badge-status.new { background: #ffeeba; color: #856404; }
        .badge-status.replied { background: #d4edda; color: #155724; }
        .badge-status.read { background: #cce5ff; color: #004085; }
        .action-btns .btn { margin-right: 0.25rem; }
        .modal-header { background: #06A3DA; color: #fff; border-radius: 12px 12px 0 0; }
        .modal-title { font-weight: 600; }
        .modal-footer { background: #f8f9fa; border-radius: 0 0 12px 12px; }
        .form-control:focus { border-color: #06A3DA; box-shadow: 0 0 0 0.2rem rgba(6,163,218,0.15); }
        @media (max-width: 600px) {
            .admin-card { margin-top: 15px; }
            .table-responsive { font-size: 0.97em; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card admin-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-envelope-open-text mr-2"></i>Contact Messages</span>
            <a href="dashboard.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        <div class="card-body">
            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success mb-3">Message deleted.</div>
            <?php endif; ?>
            <?php if ($reply_success): ?>
                <div class="alert alert-success mb-3"> <?php echo $reply_success; ?> </div>
            <?php endif; ?>
            <?php if ($reply_error): ?>
                <div class="alert alert-danger mb-3"> <?php echo $reply_error; ?> </div>
            <?php endif; ?>
            <?php if (empty($messages)): ?>
                <div class="alert alert-info">No contact messages found.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td class="text-center"><i class="fas fa-envelope"></i> <?php echo $msg['id']; ?></td>
                            <td><?php echo htmlspecialchars($msg['name']); ?></td>
                            <td><a href="mailto:<?php echo htmlspecialchars($msg['email']); ?>" class="text-primary"><i class="fas fa-at"></i> <?php echo htmlspecialchars($msg['email']); ?></a></td>
                            <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                            <td style="max-width:260px;white-space:pre-line;overflow:auto;"><span class="text-muted">"<?php echo nl2br(htmlspecialchars($msg['message'])); ?>"</span></td>
                            <td><span class="text-secondary"><?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?></span></td>
                            <td>
                                <span class="badge badge-status <?php echo strtolower($msg['status']); ?>">
                                    <?php echo ucfirst($msg['status']); ?>
                                </span>
                            </td>
                            <td class="action-btns">
                                <a href="?delete=<?php echo $msg['id']; ?>" class="btn btn-outline-danger btn-sm" title="Delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i></a>
                                <button class="btn btn-outline-success btn-sm" data-toggle="modal" data-target="#replyModal" data-id="<?php echo $msg['id']; ?>" data-email="<?php echo htmlspecialchars($msg['email']); ?>" data-name="<?php echo htmlspecialchars($msg['name']); ?>" data-subject="<?php echo htmlspecialchars($msg['subject']); ?>" title="Reply"><i class="fas fa-reply"></i> Reply</button>
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
<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" role="dialog" aria-labelledby="replyModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="">
        <div class="modal-header">
          <h5 class="modal-title" id="replyModalLabel"><i class="fas fa-reply"></i> Reply to Message</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="reply_id" id="reply_id">
          <div class="form-group">
            <label for="reply_to">To</label>
            <input type="email" class="form-control" id="reply_to" readonly>
          </div>
          <div class="form-group">
            <label for="reply_subject">Subject</label>
            <input type="text" class="form-control" id="reply_subject" readonly>
          </div>
          <div class="form-group">
            <label for="reply_text">Message</label>
            <textarea class="form-control" name="reply_text" id="reply_text" rows="5" required placeholder="Type your reply here..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Reply</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$('#replyModal').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var id = button.data('id');
  var email = button.data('email');
  var name = button.data('name');
  var subject = button.data('subject');
  var modal = $(this);
  modal.find('#reply_id').val(id);
  modal.find('#reply_to').val(email);
  modal.find('#reply_subject').val('Re: ' + subject);
  modal.find('#reply_text').val('');
});
</script>
</body>
</html> 