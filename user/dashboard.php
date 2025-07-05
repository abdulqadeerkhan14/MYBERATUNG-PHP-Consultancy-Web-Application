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

// Get user's visa applications
$applications = $db->fetchAll("SELECT * FROM visa_applications WHERE user_id = ? ORDER BY created_at DESC", [$_SESSION['user_id']]);
$active_applications = $db->fetchAll("SELECT * FROM visa_applications WHERE user_id = ? AND status IN ('pending', 'under_review')", [$_SESSION['user_id']]);
$completed_applications = $db->fetchAll("SELECT * FROM visa_applications WHERE user_id = ? AND status IN ('approved', 'completed')", [$_SESSION['user_id']]);

$total_applications = count($applications);
$active_count = count($active_applications);
$completed_count = count($completed_applications);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MYBERATUNG</title>
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
            font-size: 1.15rem;
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

        .stats-card {
            background: linear-gradient(135deg, var(--primary) 0%, #0DCAF0 100%);
            color: var(--white);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(6, 163, 218, 0.3);
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stats-label {
            font-size: 1rem;
            opacity: 0.9;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 5px;
            transition: width 0.6s ease;
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

        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge-approved {
            background: #d4edda;
            color: #155724;
        }

        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-under-review {
            background: #cce7ff;
            color: #004085;
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
            .dropdown-menu .dropdown-item.active i,
            .dropdown-menu .dropdown-item:active i,
            .dropdown-menu .dropdown-item:hover i {
                color: #fff !important;
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
                font-size: 1.25rem;
                font-weight: 600;
                padding: 18px 28px;
            }
            .sidebar .dropdown-toggle i {
                color: #fff !important;
            }
            .sidebar .dropdown-menu .dropdown-item {
                font-size: 1.15rem;
            }
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
            <a href="dashboard.php" class="nav-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="visa-application.php" class="nav-link"><i class="fas fa-file-alt"></i> Visa Application</a>
            <a href="contact.php" class="nav-link"><i class="fas fa-envelope"></i> Contact</a>
            <a href="settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            <a href="logout.php" class="nav-link mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <!-- Mobile dropdown -->
        <div class="dropdown mt-4 w-100 d-block d-md-none">
            <button class="btn btn-block btn-light dropdown-toggle d-flex justify-content-between align-items-center" type="button" id="sidebarDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="margin:0 auto 10px auto;text-align:left;">
                <span>
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </span>
            </button>
            <div class="dropdown-menu w-100" aria-labelledby="sidebarDropdown">
                <a class="dropdown-item" href="visa-application.php"><i class="fas fa-file-alt"></i> Visa Application</a>
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
            <h1>Dashboard</h1>
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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $total_applications; ?></div>
                    <div class="stats-label">Total Applications</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $active_count; ?></div>
                    <div class="stats-label">Active Applications</div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?php echo $completed_count; ?></div>
                    <div class="stats-label">Completed Applications</div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <h4 class="mb-3">Process Progress</h4>
                    <?php
                    $progress_text = 'No application found.';
                    $progress_percent = 0;
                    $progress_color = 'bg-secondary';
                    $status_label = '';
                    $status_label_class = 'text-success font-weight-bold';
                    if (!empty($applications)) {
                        $latest = $applications[0];
                        switch ($latest['status']) {
                            case 'pending':
                                $progress_text = 'Your application is pending';
                                $progress_percent = 0;
                                $progress_color = 'bg-warning';
                                $status_label = 'Pending';
                                break;
                            case 'accepted':
                                $progress_text = 'Your application is accepted';
                                $progress_percent = 20;
                                $progress_color = 'bg-info';
                                $status_label = 'Application Accepted';
                                break;
                            case 'contract_submitted':
                                $progress_text = 'Contract submitted to Employer';
                                $progress_percent = 40;
                                $progress_color = 'bg-primary';
                                $status_label = 'Contract submitted to Employer';
                                break;
                            case 'signed_by_employer':
                                $progress_text = 'Contract Signed';
                                $progress_percent = 60;
                                $progress_color = 'bg-primary';
                                $status_label = 'Contract Signed';
                                break;
                            case 'rejected_by_employer':
                                $progress_text = 'Your contract was rejected by employer';
                                $progress_percent = 0;
                                $progress_color = 'bg-danger';
                                $status_label = 'Contract Rejected';
                                $status_label_class = 'text-danger font-weight-bold';
                                break;
                            case 'ihk_submitted':
                                $progress_text = 'IHK Submitted';
                                $progress_percent = 80;
                                $progress_color = 'bg-success';
                                $status_label = 'IHK Submitted';
                                break;
                            case 'completed':
                                $progress_text = 'Process Completed';
                                $progress_percent = 100;
                                $progress_color = 'bg-success';
                                $status_label = 'Process Completed';
                                break;
                            default:
                                $progress_text = htmlspecialchars($latest['status']);
                                $progress_percent = 0;
                                $progress_color = 'bg-secondary';
                                $status_label = htmlspecialchars($latest['status']);
                        }
                    }
                    ?>
                    <p class="mb-3 text-left <?php echo $status_label_class; ?>" style="font-size: 17px;">
                        <?php echo $status_label ? $status_label : $progress_text; ?>
                    </p>
                    <div class="progress" style="height: 24px; border-radius: 12px; background: #e9ecef;">
                        <div class="progress-bar <?php echo $progress_color; ?>" role="progressbar" style="width: <?php echo $progress_percent; ?>%; font-size: 16px; border-radius: 12px;" aria-valuenow="<?php echo $progress_percent; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $progress_percent; ?>% Complete
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Offer/Notification -->
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info" role="alert" style="border-radius: 10px;">
                    <?php
                    // Example: You can replace this with dynamic content from the database
                    $offer = '';
                    // $offer = $db->fetch("SELECT message FROM offers WHERE active = 1 ORDER BY created_at DESC LIMIT 1");
                    if ($offer) {
                        echo htmlspecialchars($offer['message']);
                    } else {
                        echo 'No new offers or notifications at this time.';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Testimonial Review Section -->
        <div class="main-content mt-4">
            <div class="dashboard-card" style="max-width:600px; margin:auto;">
                <h4 class="mb-3 text-center">Leave a Testimonial</h4>
                                        <?php
                $review_success = '';
                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review'], $_POST['rating']) && isset($_SESSION['user_id'])) {
                    $review = trim($_POST['review']);
                    $rating = (int)$_POST['rating'];
                    $user_id = $_SESSION['user_id'];
                    if ($review !== '' && $rating >= 1 && $rating <= 5) {
                        try {
                            $db->execute("INSERT INTO testimonials (user_id, review, rating) VALUES (?, ?, ?)", [$user_id, $review, $rating]);
                            $review_success = true;
                        } catch (Exception $e) {
                            $review_success = false;
                        }
                    }
                }
                ?>
                <?php if (!empty($review_success)): ?>
                    <div class="alert alert-success">Thank you for your review!</div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="review">Your Review</label>
                        <textarea class="form-control" id="review" name="review" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Your Rating</label><br>
                        <div id="star-rating" class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required style="display:none;">
                                <label for="star<?php echo $i; ?>" class="star-label" style="font-size:2rem; color:#ccc; cursor:pointer;">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                    </div>
                </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
                <script>
                // Star rating color change
                const starLabels = document.querySelectorAll('#star-rating .star-label');
                const starInputs = document.querySelectorAll('#star-rating input[type=radio]');
                starLabels.forEach((label, idx) => {
                    label.addEventListener('click', function() {
                        starLabels.forEach((l, i) => {
                            l.style.color = i <= idx ? '#ffc107' : '#ccc';
                        });
                    });
                });
                // On page load, highlight if already selected
                starInputs.forEach((input, idx) => {
                    input.addEventListener('change', function() {
                        starLabels.forEach((l, i) => {
                            l.style.color = i < this.value ? '#ffc107' : '#ccc';
                        });
                    });
                });
                </script>
            </div>
        </div>

        <?php foreach ($applications as $app): ?>
            <?php if ($app['status'] === 'under_review'): ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i> Your document is under review
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
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