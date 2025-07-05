<?php
// Database setup script
// Run this file once to set up your database

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Connect to MySQL without selecting a database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Setup</h2>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS consultant");
    echo "<p>✅ Database 'consultant' created/verified</p>";
    
    // Select the database
    $pdo->exec("USE consultant");
    
    // Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
            first_name VARCHAR(50),
            last_name VARCHAR(50),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Users table created</p>";
    
    // Create services table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS services (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            icon VARCHAR(50),
            image_url VARCHAR(255),
            is_featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Services table created</p>";
    
    // Create blog_posts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blog_posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            content TEXT,
            image_url VARCHAR(255),
            author_id INT,
            status ENUM('draft', 'published') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "<p>✅ Blog posts table created</p>";
    
    // Create contact_messages table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200),
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Contact messages table created</p>";
    
    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(50) UNIQUE NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "<p>✅ Settings table created</p>";
    
    // Create password_resets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS password_resets (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            token VARCHAR(64) UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        )
    ");
    echo "<p>✅ Password resets table created</p>";
    
    // Insert default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, role, first_name, last_name) 
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            password = VALUES(password)
    ");
    $stmt->execute(['admin', 'admin@visaconsultancy.com', $admin_password, 'admin', 'Admin', 'User']);
    echo "<p>✅ Admin user created/updated</p>";
    
    // Insert default settings
    $settings = [
        ['site_name', 'Visa Consultancy Services'],
        ['site_description', 'Professional Visa and Immigration Consultancy'],
        ['contact_email', 'info@visaconsultancy.com'],
        ['contact_phone', '+1-555-123-4567'],
        ['address', '123 Immigration Street, Visa City, VC 12345'],
        ['social_facebook', 'https://facebook.com/visaconsultancy'],
        ['social_twitter', 'https://twitter.com/visaconsultancy'],
        ['social_linkedin', 'https://linkedin.com/company/visaconsultancy']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value)
    ");
    
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "<p>✅ Default settings created</p>";
    
    // Insert sample services
    $services = [
        ['Student Visa', 'Comprehensive student visa services for international education opportunities. We help students secure visas for universities, colleges, and educational institutions worldwide. Our services include university applications, document preparation, interview coaching, and financial planning guidance.', 'fas fa-graduation-cap', TRUE],
        ['Work Visa', 'Expert guidance for work permits and employment-based visas. We assist professionals in securing work visas for various countries and industries. Services include employment sponsorship, skills assessment, job search support, and work permit renewal assistance.', 'fas fa-briefcase', TRUE],
        ['Business Visa', 'Professional business visa services for entrepreneurs and investors. We help business professionals establish international business operations. Our services cover business registration, investment planning, market research, and partnership setup.', 'fas fa-chart-line', TRUE],
        ['Tourist Visa', 'Hassle-free tourist visa applications for leisure travel. We make your vacation planning smooth with quick visa processing and travel guidance. Services include quick processing, travel planning, documentation help, and travel insurance assistance.', 'fas fa-plane', TRUE],
        ['Family Immigration', 'Family reunification and spouse visa services. We help families stay together by facilitating smooth immigration processes for loved ones. Services include spouse visas, parent visas, child visas, and family sponsorship applications.', 'fas fa-users', TRUE],
        ['Permanent Residency', 'PR applications and citizenship guidance. We assist individuals in achieving permanent residency and citizenship in their desired countries. Services include PR applications, citizenship process, points assessment, and legal representation.', 'fas fa-home', TRUE]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO services (title, description, icon, is_featured) 
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            description = VALUES(description),
            icon = VALUES(icon),
            is_featured = VALUES(is_featured)
    ");
    
    foreach ($services as $service) {
        $stmt->execute($service);
    }
    echo "<p>✅ Sample services created</p>";
    
    echo "<hr>";
    echo "<h3>✅ Database setup completed successfully!</h3>";
    echo "<p><strong>Admin Login Details:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "<li><strong>Email:</strong> admin@visaconsultancy.com</li>";
    echo "</ul>";
    echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
    echo "<p><a href='admin/test-login.php'>Test Login System</a></p>";
    
} catch(PDOException $e) {
    echo "<h2>❌ Database Setup Failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration and try again.</p>";
}
?> 