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
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? 'client';
                
                if (empty($username) || empty($email) || empty($password)) {
                    $error = 'Username, email and password are required';
                } else {
                    // Check if username or email already exists
                    $existing = $db->fetch(
                        "SELECT id FROM users WHERE username = ? OR email = ?",
                        [$username, $email]
                    );
                    
                    if ($existing) {
                        $error = 'Username or email already exists';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $db->execute(
                            "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)",
                            [$username, $email, $hashed_password, $role]
                        );
                        $message = 'User added successfully';
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? '';
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $role = $_POST['role'] ?? 'client';
                $password = $_POST['password'] ?? '';
                
                if (empty($id) || empty($username) || empty($email)) {
                    $error = 'ID, username and email are required';
                } else {
                    // Check if username or email already exists for other users
                    $existing = $db->fetch(
                        "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?",
                        [$username, $email, $id]
                    );
                    
                    if ($existing) {
                        $error = 'Username or email already exists';
                    } else {
                        if (!empty($password)) {
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $db->execute(
                                "UPDATE users SET username = ?, email = ?, password = ?, role = ? WHERE id = ?",
                                [$username, $email, $hashed_password, $role, $id]
                            );
                        } else {
                            $db->execute(
                                "UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?",
                                [$username, $email, $role, $id]
                            );
                        }
                        $message = 'User updated successfully';
                    }
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? '';
                if (!empty($id) && $id != $_SESSION['admin_id']) {
                    $db->execute("DELETE FROM users WHERE id = ?", [$id]);
                    $message = 'User deleted successfully';
                } else {
                    $error = 'Cannot delete your own account';
                }
                break;
                
            case 'toggle_status':
                $id = $_POST['id'] ?? '';
                $current_status = $_POST['current_status'] ?? 1;
                $new_status = $current_status ? 0 : 1;
                
                if (!empty($id) && $id != $_SESSION['admin_id']) {
                    // Check if is_active column exists, if not add it
                    try {
                        $db->execute(
                            "UPDATE users SET is_active = ? WHERE id = ?",
                            [$new_status, $id]
                        );
                        $message = 'User status updated';
                    } catch (Exception $e) {
                        // If is_active column doesn't exist, add it first
                        $db->execute("ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
                        $db->execute(
                            "UPDATE users SET is_active = ? WHERE id = ?",
                            [$new_status, $id]
                        );
                        $message = 'User status updated (database updated)';
                    }
                } else {
                    $error = 'Cannot deactivate your own account';
                }
                break;
        }
    }
}

// Get all users
$users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

// Get user for editing
$edit_user = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $edit_user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_GET['edit']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MYBERATUNG</title>
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

        .badge-admin {
            background: #cce7ff;
            color: #004085;
        }

        .badge-client {
            background: #d4edda;
            color: #155724;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
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
            <a href="services-management.php" class="nav-link">
                <i class="fas fa-briefcase"></i> Manage Services
            </a>
            <a href="blog-management.php" class="nav-link">
                <i class="fas fa-newspaper"></i> Manage Blog
            </a>
            <a href="user-management.php" class="nav-link active">
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
            <h1>User Management</h1>
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
            <h3>All Users</h3>
            <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-plus"></i> Add New User
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

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                    <?php if ($user['id'] == $_SESSION['admin_id']): ?>
                                        <span class="badge badge-info">You</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="role-badge"><?php echo ucfirst($user['role']); ?></span>
                                </td>
                                <td>
                                    <?php 
                                    $is_active = isset($user['is_active']) ? $user['is_active'] : true;
                                    if ($is_active): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="toggleStatus(<?php echo $user['id']; ?>, <?php echo $is_active ? 1 : 0; ?>)">
                                                <i class="fas fa-toggle-on"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (empty($users)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4>No Users Found</h4>
                <p class="text-muted">Add your first user to get started.</p>
                <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-plus"></i> Add First User
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select class="form-control" id="role" name="role">
                                <option value="client">Client</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <?php if ($edit_user): ?>
    <div class="modal fade show" id="editUserModal" tabindex="-1" style="display: block;">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <a href="user-management.php" class="close">
                        <span>&times;</span>
                    </a>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?php echo $edit_user['id']; ?>">
                        
                        <div class="form-group">
                            <label for="edit_username">Username *</label>
                            <input type="text" class="form-control" id="edit_username" name="username" value="<?php echo htmlspecialchars($edit_user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_email">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_password">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit_role">Role</label>
                            <select class="form-control" id="edit_role" name="role">
                                <option value="client" <?php echo $edit_user['role'] === 'client' ? 'selected' : ''; ?>>Client</option>
                                <option value="admin" <?php echo $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="user-management.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update User</button>
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

    <!-- Toggle Status Form -->
    <form id="toggleForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="toggle_status">
        <input type="hidden" name="id" id="toggleId">
        <input type="hidden" name="current_status" id="toggleStatus">
    </form>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(id, username) {
            if (confirm('Are you sure you want to delete user "' + username + '"?')) {
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function toggleStatus(id, currentStatus) {
            document.getElementById('toggleId').value = id;
            document.getElementById('toggleStatus').value = currentStatus;
            document.getElementById('toggleForm').submit();
        }

        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
</body>
</html> 