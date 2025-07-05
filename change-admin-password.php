<?php
// Change admin password to Password123
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h2>Changing Admin Password</h2>";

try {
    $db = Database::getInstance();
    
    // Generate new password hash for 'Password123'
    $new_password = 'Password123';
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update admin user password
    $result = $db->execute(
        "UPDATE users SET password = ? WHERE username = 'admin' AND role = 'admin'",
        [$password_hash]
    );
    
    // Verify the update
    $admin_user = $db->fetch("SELECT * FROM users WHERE username = 'admin' AND role = 'admin'");
    
    if ($admin_user) {
        echo "<p>✅ Admin password updated successfully!</p>";
        echo "<p><strong>Username:</strong> admin</p>";
        echo "<p><strong>New Password:</strong> Password123</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($admin_user['email']) . "</p>";
        
        // Test the login logic
        $test_user = $db->fetch(
            "SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1",
            ['admin']
        );
        
        if ($test_user && password_verify('Password123', $test_user['password'])) {
            echo "<p>✅ Login test successful!</p>";
            echo "<p>✅ You can now log in with username: <strong>admin</strong> and password: <strong>Password123</strong></p>";
        } else {
            echo "<p>❌ Login test failed!</p>";
        }
        
    } else {
        echo "<p>❌ Admin user not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='admin/login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a></p>";
?> 