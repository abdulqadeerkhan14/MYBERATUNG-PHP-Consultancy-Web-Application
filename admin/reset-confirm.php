<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();
$message = '';
$error = '';
$token_valid = false;
$user_id = null;

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset link';
} else {
    // Check if token exists and is not expired
    $reset = $db->fetch(
        "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0 LIMIT 1",
        [$token]
    );
    
    if ($reset) {
        $token_valid = true;
        $user_id = $reset['user_id'];
    } else {
        $error = 'Invalid or expired reset link. Please request a new password reset.';
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $token_valid) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please enter both password fields';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Hash the new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user password
        $db->execute(
            "UPDATE users SET password = ? WHERE id = ?",
            [$hashed_password, $user_id]
        );
        
        // Mark reset token as used
        $db->execute(
            "UPDATE password_resets SET used = 1 WHERE token = ?",
            [$token]
        );
        
        $message = 'Password has been successfully reset. You can now login with your new password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Set New Password</h2>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($message); ?>
                                <br><br>
                                <a href="login.php" class="btn btn-primary">Go to Login</a>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                                <br><br>
                                <a href="reset-password.php" class="btn btn-primary">Request New Reset</a>
                            </div>
                        <?php elseif ($token_valid): ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                                </div>
                                
                                <button type="submit" name="reset_password" class="btn btn-primary btn-block">Reset Password</button>
                            </form>
                        <?php endif; ?>
                        
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