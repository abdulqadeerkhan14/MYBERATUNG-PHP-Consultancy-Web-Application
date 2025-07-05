<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

$email = $_GET['email'] ?? '';
$error = '';
$success = '';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_otp = trim($_POST['otp'] ?? '');
    $user = $db->fetch("SELECT * FROM users WHERE email = ?", [$email]);
    if ($user) {
        $now = date('Y-m-d H:i:s');
        if ($user['otp'] == $input_otp && $user['otp_expiry'] >= $now) {
            $db->execute("UPDATE users SET is_active = 1, otp = NULL, otp_expiry = NULL WHERE email = ?", [$email]);
            mail($email, "Welcome to MYBERATUNG", "You have successfully created your account.");
            $success = "Your account has been verified. You can now log in.";
            header('refresh:2;url=login.php');
            exit();
        } elseif ($user['otp_expiry'] < $now) {
            $error = "OTP has expired. Please request a new one.";
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    } else {
        $error = "User not found.";
    }
}

// Resend OTP
if (isset($_GET['resend']) && $email) {
    $otp = rand(100000, 999999);
    $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute'));
    $db->execute("UPDATE users SET otp = ?, otp_expiry = ? WHERE email = ?", [$otp, $otp_expiry, $email]);
    mail($email, "Your OTP for MYBERATUNG Signup", "Your OTP is: $otp. It will expire in 1 minute.");
    $success = "A new OTP has been sent to your email.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - MYBERATUNG</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8f9fa; }
        .otp-card { background: #fff; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.08); padding: 40px 30px; max-width: 400px; width: 100%; }
        .otp-card h2 { color: #06A3DA; font-weight: 700; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="otp-card">
        <h2>Email Verification</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Enter the OTP sent to your email:</label>
                <input type="text" class="form-control" name="otp" required maxlength="6" pattern="\d{6}">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Verify</button>
        </form>
        <div class="mt-3 text-center">
            <a href="?email=<?php echo urlencode($email); ?>&resend=1">Resend OTP</a>
        </div>
    </div>
</body>
</html> 