<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle password reset request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_request'])) {
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } else {
        // Check if admin user exists with this email
        $user = $db->fetch(
            "SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1",
            [$email]
        );
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $db->execute(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$user['id'], $token, $expires]
            );
            
            // Send reset email (in production, you would use a proper email service)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset-confirm.php?token=" . $token;
            
            $message = 'Password reset instructions have been sent to your email address. Please check your inbox.';
            
            // For development, show the reset link
            if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
                $message .= '<br><br><strong>Development Reset Link:</strong><br><a href="' . $reset_link . '">' . $reset_link . '</a>';
            }
        } else {
            $error = 'No admin account found with this email address';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Reset Password</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <small class="form-text text-muted">Enter the email address associated with your admin account.</small>
                            </div>
                            
                            <button type="submit" name="reset_request" class="btn btn-primary btn-block">Send Reset Link</button>
                        </form>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-muted">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 