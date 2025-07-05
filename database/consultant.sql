-- Create database
CREATE DATABASE IF NOT EXISTS consultant;
USE consultant;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    session_token VARCHAR(64) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session_token (session_token),
    INDEX idx_email (email)
);

-- Services table
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    image_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Blog posts table
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
);

-- Contact messages table
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'replied') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Password resets table
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
);

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'unsubscribed') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Testimonials table
CREATE TABLE IF NOT EXISTS testimonials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    review TEXT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating)
);

-- Visa Applications Table
CREATE TABLE `visa_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `full_name` VARCHAR(255),
    `email` VARCHAR(255),
    `phone` VARCHAR(50),
    `whatsapp` VARCHAR(50),
    `ausbildung_category` VARCHAR(100),
    `address` VARCHAR(255),
    `cnic` VARCHAR(50),
    `passport_file` VARCHAR(255),
    `matric_file` VARCHAR(255),
    `inter_file` VARCHAR(255),
    `exp_file` VARCHAR(255),
    `language_file` VARCHAR(255),
    `apply_session` VARCHAR(100),
    `status` VARCHAR(100) DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Messages Table
CREATE TABLE IF NOT EXISTS support_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    email VARCHAR(255) NULL,
    message TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX (user_id),
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
INSERT INTO users (username, email, password, role, first_name, last_name, is_active)
VALUES ('admin', 'admin@visaconsultancy.com', '$2y$10$Hsmw1DyE08u/bFR6Ga8cVOah6Ox/txHjaFHGiqQY3dkLfLeG4RntS', 'admin', 'Admin', 'User', TRUE);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Visa Consultancy Services'),
('site_description', 'Professional Visa and Immigration Consultancy'),
('contact_email', 'info@visaconsultancy.com'),
('contact_phone', '+1-555-123-4567'),
('address', '123 Immigration Street, Visa City, VC 12345'),
('social_facebook', 'https://facebook.com/visaconsultancy'),
('social_twitter', 'https://twitter.com/visaconsultancy'),
('social_linkedin', 'https://linkedin.com/company/visaconsultancy');

-- Insert sample visa services with enhanced descriptions
INSERT INTO services (title, description, icon, is_featured) VALUES
('Student Visa', 'Comprehensive student visa services for international education opportunities. We help students secure visas for universities, colleges, and educational institutions worldwide. Our services include university applications, document preparation, interview coaching, and financial planning guidance.', 'fas fa-graduation-cap', TRUE),
('Work Visa', 'Expert guidance for work permits and employment-based visas. We assist professionals in securing work visas for various countries and industries. Services include employment sponsorship, skills assessment, job search support, and work permit renewal assistance.', 'fas fa-briefcase', TRUE),
('Business Visa', 'Professional business visa services for entrepreneurs and investors. We help business professionals establish international business operations. Our services cover business registration, investment planning, market research, and partnership setup.', 'fas fa-chart-line', TRUE),
('Tourist Visa', 'Hassle-free tourist visa applications for leisure travel. We make your vacation planning smooth with quick visa processing and travel guidance. Services include quick processing, travel planning, documentation help, and travel insurance assistance.', 'fas fa-plane', TRUE),
('Family Immigration', 'Family reunification and spouse visa services. We help families stay together by facilitating smooth immigration processes for loved ones. Services include spouse visas, parent visas, child visas, and family sponsorship applications.', 'fas fa-users', TRUE),
('Permanent Residency', 'PR applications and citizenship guidance. We assist individuals in achieving permanent residency and citizenship in their desired countries. Services include PR applications, citizenship process, points assessment, and legal representation.', 'fas fa-home', TRUE),
('Visa Renewal & Extension', 'Professional assistance for visa renewals and extensions. We ensure your legal status remains valid throughout your stay with timely processing and compliance guidance.', 'fas fa-passport', FALSE),
('Legal Consultation', 'Expert legal advice on immigration matters, visa appeals, and complex immigration cases with experienced attorneys. We provide comprehensive legal support for challenging immigration situations.', 'fas fa-balance-scale', FALSE),
('Document Preparation', 'Comprehensive document preparation and verification services to ensure your application meets all requirements. We help you gather, organize, and verify all necessary documentation.', 'fas fa-file-alt', FALSE),
('Interview Preparation', 'Professional interview coaching and preparation to help you succeed in visa interviews and assessments. We provide mock interviews, question preparation, and confidence-building sessions.', 'fas fa-comments', FALSE),
('Medical Visa', 'Specialized medical visa services for patients seeking treatment abroad. We assist with medical documentation, hospital arrangements, and treatment visa applications.', 'fas fa-heartbeat', FALSE),
('Investor Visa', 'Comprehensive investor visa services for high-net-worth individuals. We help with investment planning, business proposals, and investor visa applications for various countries.', 'fas fa-coins', FALSE),
('Transit Visa', 'Quick and efficient transit visa services for travelers passing through countries. We ensure smooth transit with proper documentation and quick processing.', 'fas fa-exchange-alt', FALSE),
('Emergency Visa', 'Urgent visa processing services for emergency situations. We provide expedited processing for medical emergencies, family crises, and urgent business needs.', 'fas fa-exclamation-triangle', FALSE);

-- Insert sample testimonials
INSERT INTO testimonials (user_id, review, rating) VALUES
(1, 'Excellent service! The team helped me get my student visa for Germany within 3 weeks. Very professional and responsive throughout the process.', 5),
(1, 'Highly recommend MYBERATUNG for visa services. They made the complex process simple and guided me at every step.', 5),
(1, 'Outstanding support during my visa application. The staff is knowledgeable and always available to answer questions.', 5); 