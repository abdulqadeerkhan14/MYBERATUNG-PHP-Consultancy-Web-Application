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
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } else {
        try {
            $db = Database::getInstance();
            $user = $db->fetch(
                "SELECT * FROM users WHERE email = ? AND role = 'client' AND is_active = TRUE",
                [$email]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Generate a unique session token
                $session_token = bin2hex(random_bytes(32));
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_activity'] = time();
                $_SESSION['session_token'] = $session_token;
                // Store session token in DB for multi-login invalidation
                $db->execute("UPDATE users SET session_token = ? WHERE id = ?", [$session_token, $user['id']]);
                header('Location: dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid email or password.';
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
    <title>User Login - <?php echo SITE_NAME; ?></title>
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
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-left: 16px;
            padding-right: 16px;
        }
        .login-card {
            background: var(--primary);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px 24px;
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: white;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        .login-header h2 i {
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
        .btn-login {
            background: #fff;
            color: var(--primary);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 163, 218, 0.15);
            background: var(--primary);
            color: #fff;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        .signup-link p {
            color: white;
        }
        .signup-link a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 576px) {
            .login-card {
                padding-left: 16px;
                padding-right: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2><i class="fas fa-user-circle fa-2x text-primary mb-3"></i></h2>
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
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
                <div class="form-group">
                    <label for="email">Email Address</label>
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
                    <label for="password">Password</label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                        </div>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <div class="signup-link">
                <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
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