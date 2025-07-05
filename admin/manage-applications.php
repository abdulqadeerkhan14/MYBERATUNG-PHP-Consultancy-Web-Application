<?php
// admin/manage-applications.php

define('SECURE_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/db.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$message = '';
$error = '';

// Handle progress/status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $application_id = $_POST['application_id'] ?? '';
    $status = $_POST['status'] ?? '';
    if ($application_id && $status) {
        $db->execute("UPDATE visa_applications SET status = ? WHERE id = ?", [$status, $application_id]);
        // Fetch user email and name
        $app = $db->fetch("SELECT va.full_name, u.email FROM visa_applications va LEFT JOIN users u ON va.user_id = u.id WHERE va.id = ?", [$application_id]);
        if ($app && $app['email']) {
            $status_labels = [
                'pending' => 'Pending',
                'accepted' => 'Application Accepted',
                'contract_submitted' => 'Contract submitted to Employer',
                'signed_by_employer' => 'Signed by Employer',
                'rejected_by_employer' => 'Rejected by Employer',
                'ihk_submitted' => 'IHK Submitted',
                'completed' => 'Process Complete',
                'custom' => 'Custom Update'
            ];
            $status_label = $status_labels[$status] ?? ucfirst($status);
            $subject = "Application Status Update - MYBERATUNG";
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: MYBERATUNG <no-reply@myberatung.com>\r\n";
            $message = '<div style="font-family:Poppins,Arial,sans-serif;background:#f8f9fa;padding:30px;">
                <div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 24px rgba(0,0,0,0.07);padding:32px 28px;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <img src="https://myberatung.com/assets/logo.png" alt="MYBERATUNG Logo" style="max-height:60px;margin-bottom:10px;">
                        <h2 style="color:#06A3DA;margin:0;font-weight:700;">MYBERATUNG</h2>
                    </div>
                    <h3 style="color:#333;font-weight:600;margin-bottom:12px;">Application Status Updated</h3>
                    <p style="color:#444;font-size:16px;">Dear <b>' . htmlspecialchars($app['full_name']) . '</b>,</p>
                    <p style="color:#444;font-size:15px;">Your application status has been updated by our team. The new status is shown below:</p>
                    <div style="margin:28px 0 18px 0;text-align:center;">
                        <span style="display:inline-block;background:#06A3DA;color:#fff;padding:10px 28px;border-radius:8px;font-size:16px;font-weight:600;letter-spacing:1px;">Status: ' . htmlspecialchars($status_label) . '</span>
                    </div>
                    <p style="color:#888;font-size:13px;margin-bottom:0;">If you have any questions, feel free to reply to this email or contact our support team.</p>
                    <hr style="margin:24px 0 16px 0;border:none;border-top:1px solid #eee;">
                    <div style="text-align:center;color:#aaa;font-size:12px;">&copy; ' . date('Y') . ' MYBERATUNG. All rights reserved.</div>
                </div>
            </div>';
            mail($app['email'], $subject, $message, $headers);
        }
        $message = 'Application status updated.';
    }
}

// Handle user profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $user_id = $_POST['user_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    if ($user_id && $username && $email) {
        $db->execute("UPDATE users SET username = ?, email = ? WHERE id = ?", [$username, $email, $user_id]);
        $message = 'User profile updated.';
    }
}

// Handle edit and delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['edit_applicant'])) {
        $edit_id = $_POST['edit_id'];
        $edit_name = $_POST['edit_name'];
        $edit_cnic = $_POST['edit_cnic'];
        $edit_ausbildung = $_POST['edit_ausbildung'];
        $edit_phone = $_POST['edit_phone'];
        $edit_whatsapp = $_POST['edit_whatsapp'];
        $edit_email = $_POST['edit_email'];
        $edit_session = $_POST['edit_session'];
        $db->execute("UPDATE visa_applications SET full_name=?, cnic=?, ausbildung_category=?, phone=?, whatsapp=?, apply_session=? WHERE id=?", [$edit_name, $edit_cnic, $edit_ausbildung, $edit_phone, $edit_whatsapp, $edit_session, $edit_id]);
        $db->execute("UPDATE users SET email=? WHERE id=(SELECT user_id FROM visa_applications WHERE id=?)", [$edit_email, $edit_id]);
        $message = 'Applicant updated successfully.';
    }
    if (isset($_POST['delete_applicant'])) {
        $delete_id = $_POST['delete_id'];
        $db->execute("DELETE FROM visa_applications WHERE id=?", [$delete_id]);
        $message = 'Applicant deleted successfully.';
    }
}

// Fetch all visa applications with user info
$applications = $db->fetchAll(
    "SELECT va.*, u.username, u.email FROM visa_applications va LEFT JOIN users u ON va.user_id = u.id ORDER BY va.created_at DESC"
);

// Status options
$status_options = [
    'pending' => 'Pending',
    'accepted' => 'Application Accepted',
    'contract_submitted' => 'Contract submitted to Employer',
    'signed_by_employer' => 'Signed by Employer',
    'rejected_by_employer' => 'Rejected by Employer',
    'ihk_submitted' => 'IHK Submitted',
    'completed' => 'Process Complete',
    'custom' => 'Custom...'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - MYBERATUNG</title>
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
        .table-responsive { 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-inline .form-control { 
            width: auto; 
            display: inline-block; 
        }
        .status-select { 
            min-width: 220px; 
        }
        .btn-detail { 
            min-width: 90px; 
        }
        .modal-lg { 
            max-width: 700px; 
        }
        .file-link { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
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

        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: var(--primary);
            color: var(--white);
            border: none;
            font-weight: 600;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border-color: #f8f9fa;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
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
            <a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="services-management.php" class="nav-link"><i class="fas fa-briefcase"></i> Manage Services</a>
            <a href="blog-management.php" class="nav-link"><i class="fas fa-newspaper"></i> Manage Blog</a>
            <a href="user-management.php" class="nav-link"><i class="fas fa-users"></i> Manage Users</a>
            <a href="manage-applications.php" class="nav-link active"><i class="fas fa-passport"></i> Applicants</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="../index.php" class="nav-link" target="_blank"><i class="fas fa-external-link-alt"></i> View Website</a>
            <a href="logout.php" class="nav-link mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <h1>Manage Applications</h1>
            <div class="d-flex align-items-center">
                <div class="mr-3 text-right">
                    <p class="mb-0 font-weight-bold"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
                    <small class="text-muted">Administrator</small>
                </div>
                <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?></div>
            </div>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        <?php endif; ?>
        <div class="dashboard-card">
            <h4 class="mb-4">Applicants</h4>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>CNIC</th>
                            <th>Ausbildung Category</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Detail</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($app['full_name'] ?? $app['username']); ?></td>
                            <td><?php echo htmlspecialchars($app['cnic'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($app['ausbildung_category'] ?? '-'); ?></td>
                            <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
                            <td>
                                <form method="POST" class="form-inline update-status-form">
                                    <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                    <select name="status" class="form-control status-select mr-2" onchange="toggleCustomStatus(this)">
                                        <?php foreach ($status_options as $key => $label): ?>
                                            <?php if ($key === 'custom' && $app['status'] !== null && !array_key_exists($app['status'], $status_options)): ?>
                                                <option value="custom" selected><?php echo htmlspecialchars($app['status']); ?></option>
                                            <?php else: ?>
                                                <option value="<?php echo $key; ?>" <?php if($app['status'] === $key) echo 'selected'; ?>><?php echo $label; ?></option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="custom_status" class="form-control mr-2 custom-status-input" style="display:none;max-width:160px;" placeholder="Custom status..." value="<?php echo ($app['status'] !== null && !array_key_exists($app['status'], $status_options)) ? htmlspecialchars($app['status']) : ''; ?>">
                                    <button type="submit" name="update_progress" class="btn btn-sm btn-primary">Update</button>
                                </form>
                            </td>
                            <td>
                                <button type="button" class="btn btn-info btn-sm btn-detail" 
                                    data-toggle="modal" 
                                    data-target="#detailModal"
                                    data-applicant='<?php echo json_encode([
                                        "full_name" => $app["full_name"] ?? $app["username"],
                                        "email" => $app["email"],
                                        "phone" => $app["phone"] ?? '-',
                                        "whatsapp" => $app["whatsapp"] ?? '-',
                                        "cnic" => $app["cnic"] ?? '-',
                                        "ausbildung_category" => $app["ausbildung_category"] ?? '-',
                                        "created_at" => date('M d, Y', strtotime($app['created_at'])),
                                        "status" => $status_options[$app['status']] ?? $app['status'],
                                        "passport_file" => $app['passport_file'] ?? '',
                                        "matric_file" => $app['matric_file'] ?? '',
                                        "inter_file" => $app['inter_file'] ?? '',
                                        "exp_file" => $app['exp_file'] ?? '',
                                        "language_file" => $app['language_file'] ?? ''
                                    ]); ?>'>Detail</button>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <button type="button" class="btn btn-warning btn-sm mr-2 btn-edit" 
                                        data-toggle="modal" 
                                        data-target="#editModal"
                                        data-applicant='<?php echo json_encode([
                                            "id" => $app["id"],
                                            "full_name" => $app["full_name"] ?? $app["username"],
                                            "cnic" => $app["cnic"] ?? '',
                                            "ausbildung_category" => $app["ausbildung_category"] ?? '',
                                            "phone" => $app["phone"] ?? '',
                                            "whatsapp" => $app["whatsapp"] ?? '',
                                            "email" => $app["email"] ?? '',
                                            "apply_session" => $app["apply_session"] ?? ''
                                        ]); ?>'>Edit</button>
                                    <form method="POST" style="display:inline-block; margin-right: 8px;" onsubmit="return confirm('Are you sure you want to delete this applicant?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" name="delete_applicant" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Single Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-labelledby="detailModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                                <div class="modal-header">
            <h5 class="modal-title" id="detailModalLabel">Applicant Details</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
          <div class="modal-body" id="detailModalBody">
            <!-- Content will be filled by JS -->
                                                    </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                    </div>
                                                    </div>
                                                    </div>
    <!-- Single Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" id="editApplicantForm">
            <div class="modal-header">
              <h5 class="modal-title" id="editModalLabel">Edit Applicant</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
                                                    </div>
            <div class="modal-body" id="editModalBody">
              <!-- Content will be filled by JS -->
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit_applicant" class="btn btn-primary">Save Changes</button>
                                                </div>
                                            </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show/hide custom status input
    function toggleCustomStatus(select) {
        var customInput = select.parentElement.querySelector('.custom-status-input');
        if (select.value === 'custom') {
            customInput.style.display = 'inline-block';
            customInput.required = true;
        } else {
            customInput.style.display = 'none';
            customInput.required = false;
        }
    }
    // On page load, show custom input if needed
    document.querySelectorAll('.status-select').forEach(function(select) {
        toggleCustomStatus(select);
    });
    </script>
    <script>
    // Detail Modal population
    $(document).on('click', '.btn-detail', function() {
        var data = $(this).data('applicant');
        var docs = '';
        if (data.passport_file) docs += `<li class="file-link"><i class="fas fa-file-download"></i> <a href="${data.passport_file}" target="_blank" download>Passport</a></li>`;
        if (data.matric_file) docs += `<li class="file-link"><i class="fas fa-file-download"></i> <a href="${data.matric_file}" target="_blank" download>Matriculation Certificate</a></li>`;
        if (data.inter_file) docs += `<li class="file-link"><i class="fas fa-file-download"></i> <a href="${data.inter_file}" target="_blank" download>Intermediate Certificate</a></li>`;
        if (data.exp_file) docs += `<li class="file-link"><i class="fas fa-file-download"></i> <a href="${data.exp_file}" target="_blank" download>Experience Letter</a></li>`;
        if (data.language_file) docs += `<li class="file-link"><i class="fas fa-file-download"></i> <a href="${data.language_file}" target="_blank" download>Language Certificate</a></li>`;
        $('#detailModalBody').html(`
            <div class="row">
                <div class="col-md-6 mb-2"><strong>Name:</strong> ${data.full_name}</div>
                <div class="col-md-6 mb-2"><strong>Email:</strong> ${data.email}</div>
                <div class="col-md-6 mb-2"><strong>Phone:</strong> ${data.phone}</div>
                <div class="col-md-6 mb-2"><strong>Whatsapp:</strong> ${data.whatsapp}</div>
                <div class="col-md-6 mb-2"><strong>CNIC:</strong> ${data.cnic}</div>
                <div class="col-md-6 mb-2"><strong>Ausbildung Category:</strong> ${data.ausbildung_category}</div>
                <div class="col-md-6 mb-2"><strong>Applied Date:</strong> ${data.created_at}</div>
                <div class="col-md-6 mb-2"><strong>Status:</strong> ${data.status}</div>
                <div class="col-md-12 mt-3">
                    <h6>Documents</h6>
                    <ul class="list-unstyled">${docs}</ul>
                </div>
            </div>
        `);
    });
    // Edit Modal population
    $(document).on('click', '.btn-edit', function() {
        var data = $(this).data('applicant');
        $('#editModalBody').html(`
            <input type="hidden" name="edit_id" value="${data.id}">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="edit_name" class="form-control" value="${data.full_name}" required>
            </div>
            <div class="form-group">
                <label>CNIC</label>
                <input type="text" name="edit_cnic" class="form-control" value="${data.cnic}" required>
            </div>
            <div class="form-group">
                <label>Ausbildung Category</label>
                <input type="text" name="edit_ausbildung" class="form-control" value="${data.ausbildung_category}" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="edit_phone" class="form-control" value="${data.phone}">
            </div>
            <div class="form-group">
                <label>Whatsapp</label>
                <input type="text" name="edit_whatsapp" class="form-control" value="${data.whatsapp}">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="edit_email" class="form-control" value="${data.email}" required>
            </div>
            <div class="form-group">
                <label>Session</label>
                <input type="text" name="edit_session" class="form-control" value="${data.apply_session}">
            </div>
        `);
    });
    </script>
</body>
</html> 