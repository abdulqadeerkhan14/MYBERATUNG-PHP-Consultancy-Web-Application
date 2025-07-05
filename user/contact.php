<?php
define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db->insert('contact_messages', [
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'email' => $user['email'],
                'subject' => $subject,
                'message' => $message,
                'status' => 'new'
            ]);
            
            $success_message = 'Your message has been sent successfully. We will get back to you soon.';
            $subject = $message = '';
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
    <title>Contact Support - MYBERATUNG</title>
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
        
        .contact-card {
            background: var(--white);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
        }

        .contact-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
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

        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(6, 163, 218, 0.25);
        }

        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
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

        .text-primary {
            color: var(--primary) !important;
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
                display: none;
            }
            .dropdown-menu {
                width: 100% !important;
                min-width: 0 !important;
                background: var(--primary) !important;
                color: #fff !important;
                border: none;
                box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            }
            .dropdown-menu .dropdown-item {
                color: #fff !important;
                background: transparent !important;
                font-weight: 500;
                padding: 15px 25px;
                border-left: 3px solid transparent;
                border-radius: 0;
                transition: background 0.2s, color 0.2s;
            }
            .dropdown-menu .dropdown-item.active,
            .dropdown-menu .dropdown-item:active,
            .dropdown-menu .dropdown-item:hover {
                background: var(--primary) !important;
                color: #fff !important;
                border-left-color: #fff;
                text-decoration: none;
                transform: translateX(5px);
            }
            .dropdown-divider {
                border-color: rgba(255,255,255,0.15);
            }
            .dropdown-item.text-danger {
                color: #ffdddd !important;
            }
            .sidebar .dropdown-toggle {
                background: var(--primary) !important;
                color: #fff !important;
                border: none;
                width: 100% !important;
            }
            .sidebar .dropdown-toggle i {
                color: #fff !important;
            }
        }
        .sidebar .dropdown-toggle {
            background: #fff;
            color: var(--primary);
            border-radius: 10px;
            font-weight: 600;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            padding: 12px 18px;
        }
        .sidebar .dropdown-toggle:after {
            display: none;
        }
        .sidebar .dropdown-menu {
            margin-top: 0.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .sidebar .dropdown-item {
            font-weight: 500;
            color: var(--dark);
            padding: 12px 18px;
            border-radius: 8px;
            transition: background 0.2s;
        }
        .sidebar .dropdown-item:hover {
            background: var(--primary);
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 style="margin-bottom:0;"><i class="fas fa-passport"></i> MYBERATUNG</h4>
        </div>
        <!-- Desktop sidebar -->
        <nav class="mt-4 d-none d-md-block">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="visa-application.php" class="nav-link"><i class="fas fa-file-alt"></i> Visa Application</a>
            <a href="contact.php" class="nav-link active"><i class="fas fa-envelope"></i> Contact</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="nav-link mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Mobile dropdown -->
        <div class="dropdown mt-4 w-100 d-block d-md-none">
            <button class="btn btn-block btn-light dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="sidebarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width:90%;margin:0 auto 10px auto;text-align:left;">
                <span>
                    <i class="fas fa-envelope"></i> Contact
                </span>
            </button>
            <div class="dropdown-menu w-100" aria-labelledby="sidebarDropdown">
                <a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="dropdown-item" href="visa-application.php"><i class="fas fa-file-alt"></i> Visa Application</a>
                <a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1>Contact Support</h1>
            <div class="d-flex align-items-center">
                <div class="mr-3 text-right">
                    <p class="mb-0 font-weight-bold"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <small class="text-muted">Client</small>
                </div>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="contact-card">
                    <div class="text-center mb-4">
                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                        <h3>Get in Touch</h3>
                        <p class="text-muted">We're here to help with your visa application</p>
                    </div>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="subject">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="6" 
                                      placeholder="Describe your issue or question..." required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                            <h6>Phone Support</h6>
                            <p class="text-muted">+1-555-123-4567</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-envelope fa-2x text-primary mb-2"></i>
                            <h6>Email Support</h6>
                            <p class="text-muted">support@visaconsultancy.com</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                            <h6>Response Time</h6>
                            <p class="text-muted">Within 24 hours</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
$(function(){
    // Highlight active link in dropdown
    var path = window.location.pathname.split("/").pop();
    $(".dropdown-menu a").each(function(){
        if($(this).attr("href") === path){
            $(this).addClass("active");
            $("#sidebarDropdown span:first").html($(this).html());
        }
    });
});
</script>
</body>
</html> 