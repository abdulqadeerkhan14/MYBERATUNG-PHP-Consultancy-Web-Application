<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } else {
        try {
            $db = Database::getInstance();
            // Check if email already exists
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
            if ($existing_user) {
                $error_message = 'An account with this email already exists.';
            } else {
                // Create username from email
                $username = explode('@', $email)[0];
                $base_username = $username;
                $counter = 1;
                // Ensure unique username
                while ($db->fetch("SELECT id FROM users WHERE username = ?", [$username])) {
                    $username = $base_username . $counter;
                    $counter++;
                }
                // Generate OTP and expiry
                $otp = rand(100000, 999999);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute'));
                // Insert new user with OTP and inactive status
                $user_id = $db->insert('users', [
                    'username' => $username,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'phone' => $phone,
                    'role' => 'client',
                    'is_active' => 0,
                    'otp' => $otp,
                    'otp_expiry' => $otp_expiry
                ]);
                if ($user_id) {
                    // Send OTP email
                    mail($email, "Your OTP for MYBERATUNG Signup", "Your OTP is: $otp. It will expire in 1 minute.");
                    header('Location: verify-otp.php?email=' . urlencode($email));
                    exit();
                } else {
                    $error_message = 'Error creating account. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Signup - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .signup-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 16px;
            padding-right: 16px;
        }
        .signup-card {
            background: var(--primary);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 32px 24px;
            width: 100%;
            max-width: 400px;
        }
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .signup-header h2 {
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .signup-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        .signup-header h2 i {
            color: white !important;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            color: white;
            font-weight: 500;
        }
        .input-group-text {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-signup {
            background: #fff;
            color: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-signup:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 163, 218, 0.15);
            background: var(--primary);
            color: #fff;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        .login-link p {
            color: white;
        }
        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .signup-card {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <div class="signup-card">
            <div class="signup-header">
                <h2><i class="fas fa-user-plus fa-2x text-primary mb-3"></i></h2>
                <h2>Create Account</h2>
                <p>Join us to start your visa journey</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                        </div>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-phone"></i>
                            </span>
                        </div>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" id="password" name="password" 
                               minlength="6" required>
                    </div>
                    <small class="form-text text-muted">Password must be at least 6 characters long.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password *</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               minlength="6" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-signup">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <!-- Floating WhatsApp and Support Buttons -->
    <a href="https://wa.me/1234567890" target="_blank" class="fab-btn whatsapp-btn" title="Chat on WhatsApp">
        <i class="fab fa-whatsapp"></i>
    </a>
    <button type="button" class="fab-btn support-btn" title="Support" data-toggle="modal" data-target="#supportModal">
        <i class="fas fa-headset"></i>
    </button>

    <!-- Support Modal -->
    <div class="modal fade" id="supportModal" tabindex="-1" role="dialog" aria-labelledby="supportModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="supportModalLabel">Contact Support</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form>
              <div class="form-group">
                <label for="support-message">Your Message</label>
                <textarea class="form-control" id="support-message" rows="4" placeholder="Type your message..."></textarea>
              </div>
              <button type="submit" class="btn btn-primary btn-block">Send</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .fab-btn {
        position: fixed;
        right: 24px;
        z-index: 1050;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.18);
        border: none;
        outline: none;
        cursor: pointer;
        transition: box-shadow 0.2s, background 0.2s;
    }
    .whatsapp-btn {
        background: #25D366;
        bottom: 100px;
    }
    .support-btn {
        background: var(--primary);
        bottom: 30px;
    }
    .fab-btn:hover {
        box-shadow: 0 8px 24px rgba(6,163,218,0.25);
        filter: brightness(1.1);
    }
    @media (max-width: 600px) {
        .fab-btn {
            width: 48px;
            height: 48px;
            font-size: 1.5rem;
            right: 16px;
        }
        .whatsapp-btn { bottom: 85px; }
        .support-btn { bottom: 20px; }
    }
    </style>
</body>
</html> 