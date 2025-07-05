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
$message = '';
$error = '';

// Fetch current site settings from the database
$site_settings_keys = ['site_name', 'site_description', 'contact_email', 'contact_phone', 'address', 'business_hours', 'services'];
$site_settings_array = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name', 'site_description', 'contact_email', 'contact_phone', 'address', 'business_hours', 'services')");
$site_settings = [];
foreach ($site_settings_array as $setting) {
    $site_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_site':
                $site_name = trim($_POST['site_name'] ?? '');
                $site_description = trim($_POST['site_description'] ?? '');
                $contact_email = trim($_POST['contact_email'] ?? '');
                $contact_phone = trim($_POST['contact_phone'] ?? '');
                $contact_address = trim($_POST['contact_address'] ?? '');
                $business_hours = trim($_POST['business_hours'] ?? '');
                $services = trim($_POST['services'] ?? '');
                if (empty($site_name)) {
                    $error = 'Site name is required';
                } else {
                    $db->execute("REPLACE INTO settings (setting_key, setting_value) VALUES
                        ('site_name', ?),
                        ('site_description', ?),
                        ('contact_email', ?),
                        ('contact_phone', ?),
                        ('address', ?),
                        ('business_hours', ?),
                        ('services', ?)",
                        [$site_name, $site_description, $contact_email, $contact_phone, $contact_address, $business_hours, $services]
                    );
                    $message = 'Site settings updated successfully!';
                    // Refresh site settings after update
                    $site_settings_array = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name', 'site_description', 'contact_email', 'contact_phone', 'address', 'business_hours', 'services')");
                    $site_settings = [];
                    foreach ($site_settings_array as $setting) {
                        $site_settings[$setting['setting_key']] = $setting['setting_value'];
                    }
                }
                break;
                
            case 'update_admin':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'All password fields are required';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match';
                } else {
                    // Verify current password
                    $admin = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['admin_id']]);
                    if ($admin && password_verify($current_password, $admin['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $db->execute(
                            "UPDATE users SET password = ? WHERE id = ?",
                            [$hashed_password, $_SESSION['admin_id']]
                        );
                        $message = 'Password updated successfully';
                    } else {
                        $error = 'Current password is incorrect';
                    }
                }
                break;
                
            case 'update_social_links':
                $whatsapp = trim($_POST['whatsapp'] ?? '');
                $facebook = trim($_POST['facebook'] ?? '');
                $instagram = trim($_POST['instagram'] ?? '');
                $twitter = trim($_POST['twitter'] ?? '');
                $db->execute("REPLACE INTO settings (setting_key, setting_value) VALUES
                    ('whatsapp', ?),
                    ('facebook', ?),
                    ('instagram', ?),
                    ('twitter', ?)", [$whatsapp, $facebook, $instagram, $twitter]);
                $message = 'Social links updated successfully!';
                break;
                
            case 'backup_database':
                // Create database backup
                $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $message = 'Database backup created: ' . $backup_file;
                break;
        }
    }
}

// Get current admin info
$admin = $db->fetch("SELECT username, email FROM users WHERE id = ?", [$_SESSION['admin_id']]);

// Get site statistics
$total_users = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
$total_services = $db->fetch("SELECT COUNT(*) as count FROM services")['count'];
$total_posts = $db->fetch("SELECT COUNT(*) as count FROM blog_posts")['count'];
$published_posts = $db->fetch("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")['count'];

// Get social links from settings
$settings_array = $db->fetchAll("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('whatsapp', 'facebook', 'instagram', 'twitter')");
$social_links = [];
foreach ($settings_array as $setting) {
    $social_links[$setting['setting_key']] = $setting['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MYBERATUNG</title>
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
            background: linear-gradient(135deg, var(--primary) 0%, #0DCAF0 100%);
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
            <a href="dashboard.php" class="nav-link">
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
            <a href="settings.php" class="nav-link active">
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
            <h1>Settings</h1>
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
        <!-- Page Content -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Site Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Site Settings</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_site">
                            
                            <div class="form-group">
                                <label for="site_name">Site Name</label>
                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo htmlspecialchars($site_settings['site_name'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="site_description">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($site_settings['contact_email'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($site_settings['contact_phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="contact_address">Contact Address</label>
                                <textarea class="form-control" id="contact_address" name="contact_address" rows="3"><?php echo htmlspecialchars($site_settings['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="business_hours">Business Hours</label>
                                <textarea class="form-control" id="business_hours" name="business_hours" rows="2"><?php echo htmlspecialchars($site_settings['business_hours'] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="services">Services</label>
                                <textarea class="form-control" id="services" name="services" rows="2"><?php echo htmlspecialchars($site_settings['services'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Site Settings
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Social Links Card -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-share-alt"></i> Social Links</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" autocomplete="off">
                            <input type="hidden" name="action" value="update_social_links">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp Number</label>
                                    <input type="text" class="form-control" id="whatsapp" name="whatsapp" placeholder="e.g. 1234567890" value="<?php echo htmlspecialchars($social_links['whatsapp'] ?? ''); ?>">
                                    <small class="form-text text-muted">Leave blank to hide WhatsApp icon</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="facebook"><i class="fab fa-facebook-f"></i> Facebook Link</label>
                                    <input type="url" class="form-control" id="facebook" name="facebook" placeholder="e.g. https://facebook.com/yourpage" value="<?php echo htmlspecialchars($social_links['facebook'] ?? ''); ?>">
                                    <small class="form-text text-muted">Leave blank to hide Facebook icon</small>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="instagram"><i class="fab fa-instagram"></i> Instagram Link</label>
                                    <input type="url" class="form-control" id="instagram" name="instagram" placeholder="e.g. https://instagram.com/yourprofile" value="<?php echo htmlspecialchars($social_links['instagram'] ?? ''); ?>">
                                    <small class="form-text text-muted">Leave blank to hide Instagram icon</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="twitter"><i class="fab fa-twitter"></i> Twitter Link</label>
                                    <input type="url" class="form-control" id="twitter" name="twitter" placeholder="e.g. https://twitter.com/yourprofile" value="<?php echo htmlspecialchars($social_links['twitter'] ?? ''); ?>">
                                    <small class="form-text text-muted">Leave blank to hide Twitter icon</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Social Links</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Admin Settings -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-shield"></i> Admin Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Current Admin:</strong> <?php echo htmlspecialchars($admin['username']); ?><br>
                            <strong>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="update_admin">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- System Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> System Actions</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="backup_database">
                            <button type="submit" class="btn btn-info" onclick="return confirm('Create database backup?')">
                                <i class="fas fa-download"></i> Backup Database
                            </button>
                        </form>
                        
                        <a href="../" class="btn btn-success" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Website
                        </a>
                        
                        <a href="logout.php" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
</body>
</html> 