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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $icon = $_POST['icon'] ?? '';
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $features = $_POST['features'] ?? '';
                
                if (empty($title) || empty($description)) {
                    $error = 'Title and description are required';
                } else {
                    $db->execute(
                        "INSERT INTO services (title, description, icon, is_featured, features) VALUES (?, ?, ?, ?, ?)",
                        [$title, $description, $icon, $is_featured, $features]
                    );
                    $message = 'Service added successfully';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? '';
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $icon = $_POST['icon'] ?? '';
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $features = $_POST['features'] ?? '';
                
                if (empty($id) || empty($title) || empty($description)) {
                    $error = 'ID, title and description are required';
                } else {
                    $db->execute(
                        "UPDATE services SET title = ?, description = ?, icon = ?, is_featured = ?, features = ? WHERE id = ?",
                        [$title, $description, $icon, $is_featured, $features, $id]
                    );
                    $message = 'Service updated successfully';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!empty($id)) {
                    $db->execute("DELETE FROM services WHERE id = ?", [$id]);
                    $message = 'Service deleted successfully';
                }
                break;
                
            case 'toggle_featured':
                $id = $_POST['id'] ?? '';
                $is_featured = $_POST['is_featured'] ?? 0;
                if (!empty($id)) {
                    $db->execute(
                        "UPDATE services SET is_featured = ? WHERE id = ?",
                        [$is_featured ? 0 : 1, $id]
                    );
                    $message = 'Service featured status updated';
                }
                break;
        }
    }
}

// Get all services
$services = $db->fetchAll("SELECT * FROM services ORDER BY is_featured DESC, title ASC");

// Get service for editing
$edit_service = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_service = $db->fetch("SELECT * FROM services WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - MYBERATUNG</title>
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
        .service-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .service-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #fff3cd;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .icon-preview {
            font-size: 2rem;
            margin: 10px 0;
            color: var(--primary);
        }
        .quick-link-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .quick-link-card:hover {
            transform: translateY(-3px);
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
            <a href="services-management.php" class="nav-link active">
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
            <a href="settings.php" class="nav-link">
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
            <h1>Services Management</h1>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>All Services</h3>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
                <i class="fas fa-plus"></i> Add New Service
            </button>
        </div>

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

        <!-- Services Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Services</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Icon</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $service): ?>
                            <tr>
                                <td><?php echo $service['id']; ?></td>
                                <td>
                                    <i class="<?php echo htmlspecialchars($service['icon']); ?> fa-2x text-primary"></i>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($service['title']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($service['description'], 0, 100)) . '...'; ?>
                                </td>
                                <td>
                                    <?php if ($service['is_featured']): ?>
                                        <span class="badge badge-warning">Featured</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Regular</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?edit=<?php echo $service['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="toggleFeatured(<?php echo $service['id']; ?>, <?php echo $service['is_featured']; ?>)">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['title']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (empty($services)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                <h4>No Services Found</h4>
                <p class="text-muted">Add your first visa service to get started.</p>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Add First Service
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add Service Modal -->
    <div class="modal fade" id="addServiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Service</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="title">Service Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="features">Features <small>(comma separated, e.g. Expert Consultation, Document Preparation)</small></label>
                            <input type="text" class="form-control" id="features" name="features" placeholder="Feature 1, Feature 2, Feature 3">
                        </div>
                        
                        <div class="form-group">
                            <label for="icon">Icon Class</label>
                            <input type="text" class="form-control" id="icon" name="icon" placeholder="fas fa-graduation-cap">
                            <small class="form-text text-muted">Font Awesome icon class (e.g., fas fa-graduation-cap)</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured">
                            <label class="form-check-label" for="is_featured">
                                Featured Service (will appear on homepage)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Service Modal -->
    <?php if ($edit_service): ?>
    <div class="modal fade show" id="editServiceModal" tabindex="-1" style="display: block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Service</h5>
                    <a href="services-management.php" class="close">
                        <span>&times;</span>
                    </a>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_service['id']; ?>">
                        
                        <div class="form-group">
                            <label for="edit_title">Service Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" value="<?php echo htmlspecialchars($edit_service['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_description">Description *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="4" required><?php echo htmlspecialchars($edit_service['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_features">Features <small>(comma separated, e.g. Expert Consultation, Document Preparation)</small></label>
                            <input type="text" class="form-control" id="edit_features" name="features" value="<?php echo htmlspecialchars($edit_service['features'] ?? ''); ?>" placeholder="Feature 1, Feature 2, Feature 3">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_icon">Icon Class</label>
                            <input type="text" class="form-control" id="edit_icon" name="icon" value="<?php echo htmlspecialchars($edit_service['icon']); ?>" placeholder="fas fa-graduation-cap">
                            <small class="form-text text-muted">Font Awesome icon class (e.g., fas fa-graduation-cap)</small>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="edit_is_featured" name="is_featured" <?php echo $edit_service['is_featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="edit_is_featured">
                                Featured Service (will appear on homepage)
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="services-management.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Service</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="deleteId">
    </form>

    <!-- Toggle Featured Form -->
    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_featured">
        <input type="hidden" name="id" id="toggleId">
        <input type="hidden" name="is_featured" id="toggleFeatured">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteService(id, title) {
            if (confirm('Are you sure you want to delete "' + title + '"?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function toggleFeatured(id, currentStatus) {
            document.getElementById('toggleId').value = id;
            document.getElementById('toggleFeatured').value = currentStatus;
            document.getElementById('toggleForm').submit();
        }

        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
</body>
</html> 