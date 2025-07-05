<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check if user exists
        $user = $db->fetch(
            "SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1",
            [$username]
        );
        
        if ($user) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user password
            $db->execute(
                "UPDATE users SET password = ? WHERE id = ?",
                [$hashed_password, $user['id']]
            );
            
            $message = 'Password has been successfully changed. You can now login with your new password.';
        } else {
            $error = 'Admin user not found with this username';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Admin Password - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Change Admin Password</h2>
                        
                        <div class="alert alert-info">
                            <strong>Default Admin Credentials:</strong><br>
                            Username: <code>admin</code><br>
                            Email: <code>admin@visaconsultancy.com</code>
                        </div>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Admin Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="admin" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-block">Change Password</button>
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