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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $ausbildung_category = $_POST['ausbildung_category'] ?? '';
    $address = $_POST['address'] ?? '';
    $cnic = $_POST['cnic'] ?? '';
    $apply_session = $_POST['apply_session'] ?? '';

    // File uploads
    $passport_file = $_FILES['passport_file'] ?? null;
    $matric_file = $_FILES['matric_file'] ?? null;
    $inter_file = $_FILES['inter_file'] ?? null;
    $exp_file = $_FILES['exp_file'] ?? null;
    $language_file = $_FILES['language_file'] ?? null;

    // Validation
    if (empty($full_name) || empty($email) || empty($phone) || empty($whatsapp) || empty($ausbildung_category) || empty($address) || empty($cnic) || empty($apply_session) || !$passport_file || !$matric_file || !$inter_file || !$language_file) {
        $error_message = 'Please fill in all required fields and upload all required documents.';
    } else {
        // Handle file uploads
        $upload_dir = '../uploads/applications/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $passport_path = $education_path = $language_path = '';
        $timestamp = time();
        if ($passport_file && $passport_file['tmp_name']) {
            $passport_path = $upload_dir . 'passport_' . $timestamp . '_' . basename($passport_file['name']);
            move_uploaded_file($passport_file['tmp_name'], $passport_path);
        }
        $matric_path = $inter_path = $exp_path = '';
        if ($matric_file && $matric_file['tmp_name']) {
            $matric_path = $upload_dir . 'matric_' . $timestamp . '_' . basename($matric_file['name']);
            move_uploaded_file($matric_file['tmp_name'], $matric_path);
        }
        if ($inter_file && $inter_file['tmp_name']) {
            $inter_path = $upload_dir . 'inter_' . $timestamp . '_' . basename($inter_file['name']);
            move_uploaded_file($inter_file['tmp_name'], $inter_path);
        }
        if ($exp_file && $exp_file['tmp_name']) {
            $exp_path = $upload_dir . 'exp_' . $timestamp . '_' . basename($exp_file['name']);
            move_uploaded_file($exp_file['tmp_name'], $exp_path);
        }
        if ($language_file && $language_file['tmp_name']) {
            $language_path = $upload_dir . 'language_' . $timestamp . '_' . basename($language_file['name']);
            move_uploaded_file($language_file['tmp_name'], $language_path);
        }
        // Insert into visa_applications
        try {
            $db->insert('visa_applications', [
                'user_id' => $_SESSION['user_id'],
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'whatsapp' => $whatsapp,
                'ausbildung_category' => $ausbildung_category,
                'address' => $address,
                'cnic' => $cnic,
                'passport_file' => $passport_path,
                'matric_file' => $matric_path,
                'inter_file' => $inter_path,
                'exp_file' => $exp_path,
                'language_file' => $language_path,
                'apply_session' => $apply_session,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            // Send confirmation email (HTML)
            $subject = "Application Submitted - MYBERATUNG";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: MYBERATUNG <no-reply@myberatung.com>\r\n";
            $message = '<div style="font-family:Poppins,Arial,sans-serif;background:#f8f9fa;padding:30px;">
                <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.07);padding:32px 28px;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <img src="https://myberatung.com/assets/logo.png" alt="MYBERATUNG Logo" style="max-height:60px;margin-bottom:10px;">
                        <h2 style="color:#06A3DA;margin:0;font-weight:700;">MYBERATUNG</h2>
                    </div>
                    <h3 style="color:#333;font-weight:600;margin-bottom:12px;">Application Received!</h3>
                    <p style="color:#444;font-size:16px;">Dear <b>' . htmlspecialchars($full_name) . '</b>,</p>
                    <p style="color:#444;font-size:15px;">Thank you for submitting your application. We have received your documents and our team will review them shortly. You will be contacted soon regarding the next steps.</p>
                    <div style="margin:28px 0 18px 0;text-align:center;">
                        <span style="display:inline-block;background:#06A3DA;color:#fff;padding:10px 28px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:1px;">Status: Pending Review</span>
                    </div>
                    <p style="color:#888;font-size:13px;margin-bottom:0;">If you have any questions, feel free to reply to this email or contact our support team.</p>
                    <hr style="margin:24px 0 16px 0;border:none;border-top:1px solid #eee;">
                    <div style="text-align:center;color:#aaa;font-size:12px;">&copy; ' . date('Y') . ' MYBERATUNG. All rights reserved.</div>
                </div>
            </div>';
            mail($email, $subject, $message, $headers);
            
            // Send notification email to admin
            // Fetch the current admin email from the users table
            $admin_row = $db->fetch("SELECT email FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
            $admin_email = $admin_row && !empty($admin_row['email']) ? $admin_row['email'] : ADMIN_EMAIL;
            $admin_subject = "New Visa Application Submitted by $full_name";
            $admin_headers = "MIME-Version: 1.0" . "\r\n";
            $admin_headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $admin_headers .= "From: MYBERATUNG <no-reply@myberatung.com>\r\n";
            $admin_message = '<div style="font-family:Poppins,Arial,sans-serif;background:#f8f9fa;padding:30px;">
                <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.07);padding:32px 28px;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <img src="https://myberatung.com/assets/logo.png" alt="MYBERATUNG Logo" style="max-height:60px;margin-bottom:10px;">
                        <h2 style="color:#06A3DA;margin:0;font-weight:700;">MYBERATUNG</h2>
                    </div>
                    <h3 style="color:#333;font-weight:600;margin-bottom:12px;">New Visa Application Submitted</h3>
                    <p style="color:#444;font-size:16px;">A new visa application has been submitted by <b>' . htmlspecialchars($full_name) . '</b>.</p>';
            $admin_message .= '<ul style="color:#444;font-size:15px;line-height:1.7;">'
                .'<li><b>Name:</b> ' . htmlspecialchars($full_name) . '</li>'
                .'<li><b>Email:</b> ' . htmlspecialchars($email) . '</li>'
                .'<li><b>Phone:</b> ' . htmlspecialchars($phone) . '</li>'
                .'<li><b>WhatsApp:</b> ' . htmlspecialchars($whatsapp) . '</li>'
                .'<li><b>Category:</b> ' . htmlspecialchars($ausbildung_category) . '</li>'
                .'<li><b>Address:</b> ' . htmlspecialchars($address) . '</li>'
                .'<li><b>CNIC:</b> ' . htmlspecialchars($cnic) . '</li>'
                .'<li><b>Session:</b> ' . htmlspecialchars($apply_session) . '</li>'
                .'</ul>';
            $admin_message .= '<p style="color:#888;font-size:13px;margin-bottom:0;">You can view this application in the admin dashboard.</p>';
            $admin_message .= '<hr style="margin:24px 0 16px 0;border:none;border-top:1px solid #eee;">';
            $admin_message .= '<div style="text-align:center;color:#aaa;font-size:12px;">&copy; ' . date('Y') . ' MYBERATUNG. All rights reserved.</div>';
            $admin_message .= '</div></div>';
            mail($admin_email, $admin_subject, $admin_message, $admin_headers);

            $success_message = 'Your application has been submitted successfully!';
            // Clear form data
            $full_name = $email = $phone = $whatsapp = $ausbildung_category = $address = $cnic = $apply_session = '';
        } catch (Exception $e) {
            $error_message = 'Sorry, there was an error submitting your application. Please try again later.';
        }
    }
}

// Get all visa services for dropdown
$services = $db->fetchAll("SELECT * FROM services ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visa Application - MYBERATUNG</title>
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
            background: linear-gradient(135deg, var(--light) 0%, #e9ecef 100%);
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
        
        .application-form {
            background: var(--white);
            border-radius: 18px;
            padding: 40px 32px 32px 32px;
            box-shadow: 0 8px 32px rgba(6, 163, 218, 0.10), 0 1.5px 6px rgba(6, 163, 218, 0.07);
            border: 1.5px solid #e3e6f0;
            max-width: 900px;
            margin: 0 auto 40px auto;
            transition: all 0.3s ease;
        }

        .application-form:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(6, 163, 218, 0.15);
        }
        
        .form-section {
            background: var(--light);
            border: 1.5px solid #e3e6f0;
            border-radius: 12px;
            padding: 28px 24px 18px 24px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(6, 163, 218, 0.04);
            transition: all 0.3s ease;
        }
        
        .form-section:hover {
            box-shadow: 0 4px 12px rgba(6, 163, 218, 0.08);
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
        
        .custom-file-label {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .custom-file-label:hover {
            border-color: var(--primary);
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
                background: rgba(255,255,255,0.15) !important;
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
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-passport"></i> MYBERATUNG</h4>
        </div>
        <!-- Desktop sidebar -->
        <nav class="mt-4 d-none d-md-block">
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="visa-application.php" class="nav-link active"><i class="fas fa-file-alt"></i> Visa Application</a>
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="nav-link mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Mobile dropdown -->
        <div class="dropdown mt-4 w-100 d-block d-md-none">
            <button class="btn btn-block btn-light dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="sidebarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="width:90%;margin:0 auto 10px auto;text-align:left;">
                <span>
                    <i class="fas fa-file-alt"></i> Visa Application
                </span>
            </button>
            <div class="dropdown-menu w-100" aria-labelledby="sidebarDropdown">
                <a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="dropdown-item" href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
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
            <h1><i class="fas fa-file-alt text-primary"></i> Visa Application</h1>
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

        <!-- Application Form -->
        <div class="application-form">
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
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-section mb-4">
                    <h4><i class="fas fa-user-edit"></i> Personal Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($full_name ?? ''); ?>" required placeholder="Enter your full name">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Email</label>
                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="Enter your email address">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" required placeholder="03XXXXXXXXX">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Whatsapp Number</label>
                            <input type="text" class="form-control" name="whatsapp" value="<?php echo htmlspecialchars($whatsapp ?? ''); ?>" required placeholder="03XXXXXXXXX">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Ausbildung Category</label>
                            <select class="form-control p-0 pl-2" name="ausbildung_category" required style="min-width: 320px;">
                                <option value="" disabled selected>Select your Ausbildung Category</option>
                                <option value="Nursing" <?php if(($ausbildung_category ?? '')=='Nursing') echo 'selected'; ?>>Nursing</option>
                                <option value="Gastronomy" <?php if(($ausbildung_category ?? '')=='Gastronomy') echo 'selected'; ?>>Gastronomy</option>
                                <option value="IT" <?php if(($ausbildung_category ?? '')=='IT') echo 'selected'; ?>>IT</option>
                                <option value="Other" <?php if(($ausbildung_category ?? '')=='Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($address ?? ''); ?>" required placeholder="Enter your address">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">CNIC</label>
                            <input type="text" class="form-control" name="cnic" value="<?php echo htmlspecialchars($cnic ?? ''); ?>" required placeholder="Enter your CNIC number">
                        </div>
                    </div>
                </div>
                <div class="form-section mb-4">
                    <h4><i class="fas fa-file-upload"></i> Document Uploads</h4>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Passport (PDF/JPG/PNG)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="passport_file" name="passport_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="passport_file">Choose Passport file...</label>
                            </div>
                            <small class="form-text text-muted">Upload a clear scan of your passport</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Matriculation Certificate (PDF/JPG/PNG)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="matric_file" name="matric_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="matric_file">Choose Matriculation Certificate...</label>
                            </div>
                            <small class="form-text text-muted">Upload your Matriculation Certificate</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Intermediate Certificate (PDF/JPG/PNG)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="inter_file" name="inter_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="inter_file">Choose Intermediate Certificate...</label>
                            </div>
                            <small class="form-text text-muted">Upload your Intermediate Certificate</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Experience Letter (PDF/JPG/PNG, if have)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="exp_file" name="exp_file" accept=".pdf,.jpg,.jpeg,.png">
                                <label class="custom-file-label" for="exp_file">Choose Experience Letter (if available)...</label>
                    </div>
                            <small class="form-text text-muted">Upload your Experience Letter (if available)</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Language Certificate (PDF/JPG/PNG)</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="language_file" name="language_file" accept=".pdf,.jpg,.jpeg,.png" required>
                                <label class="custom-file-label" for="language_file">Choose Language Certificate...</label>
                        </div>
                            <small class="form-text text-muted">Upload your Language Certificate</small>
                        </div>
                    </div>
                </div>
                <div class="form-section mb-4">
                    <h4><i class="fas fa-calendar-alt"></i> Session Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label required-field">Apply For the Session</label>
                            <input type="text" class="form-control" name="apply_session" value="<?php echo htmlspecialchars($apply_session ?? ''); ?>" required placeholder="e.g. March 2024, Fall 2024, etc.">
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Application
                    </button>
                </div>
            </form>
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
    // Show selected file name for custom file inputs
    $(document).on('change', '.custom-file-input', function (e) {
        var fileName = e.target.files[0] ? e.target.files[0].name : '';
        $(this).next('.custom-file-label').html(fileName);
    });
    </script>
</body>
</html> 