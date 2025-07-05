# MYBERATUNG - Professional Visa Consultancy Website

A comprehensive and modern visa consultancy website with client and admin interfaces, built with PHP and MySQL. The platform provides professional visa consultation services with a focus on German Ausbildung (vocational training) programs.

## 🌟 Features

### Client Side
- **Modern Homepage** with hero section, featured services, and testimonials
- **About Us** section with company information and team details
- **Visa Services** listing with detailed descriptions for various visa types
- **Blog Section** with visa updates and immigration news
- **Contact Form** with consultation requests
- **User Authentication System** with secure login/signup
- **User Dashboard** with application tracking
- **Visa Application System** for Ausbildung programs
- **Testimonials System** for client reviews
- **Newsletter Subscription** functionality
- **Support Messages** system for user inquiries

### Admin Panel
- **Secure Admin Login** with session management
- **Dashboard** with overview statistics and recent activities
- **Blog Management** for visa updates and content creation
- **User Management** for client accounts
- **Visa Applications Management** with status updates
- **Contact Messages** management and response system
- **Support Messages** handling
- **Services Management** for visa service offerings
- **Settings Management** for site configuration
- **Password Reset** functionality

## 🛠 Technology Stack
- **Frontend**: HTML5, CSS3, Bootstrap 4, JavaScript, Font Awesome
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Web Server**: Apache/Nginx
- **Additional**: PDO for database operations, Session management

## 📁 Project Structure
```
├── admin/                          # Admin panel files
│   ├── dashboard.php              # Admin dashboard
│   ├── login.php                  # Admin login
│   ├── blog-management.php        # Blog post management
│   ├── user-management.php        # User account management
│   ├── manage-applications.php    # Visa applications management
│   ├── contact-messages.php       # Contact form submissions
│   ├── support-messages.php       # Support messages handling
│   ├── services-management.php    # Services management
│   ├── settings.php               # Site settings
│   ├── change-password.php        # Password change
│   ├── reset-password.php         # Password reset
│   └── logout.php                 # Admin logout
├── user/                          # User/client area
│   ├── login.php                  # User login
│   ├── signup.php                 # User registration
│   ├── dashboard.php              # User dashboard
│   ├── visa-application.php       # Visa application form
│   ├── settings.php               # User settings
│   ├── contact.php                # User contact form
│   ├── verify-otp.php             # OTP verification
│   └── logout.php                 # User logout
├── includes/                      # Core files
│   ├── config.php                 # Configuration settings
│   └── db.php                     # Database connection class
├── assets/                        # Static assets
│   ├── css/
│   │   └── style.css              # Main stylesheet
│   └── js/
│       └── navigation.js          # Navigation functionality
├── database/                      # Database files
│   └── consultant.sql             # Database schema
├── uploads/                       # File uploads directory
├── images/                        # Image assets
├── index.php                      # Homepage
├── about.php                      # About page
├── services.php                   # Services page
├── blog.php                       # Blog listing
├── blog-post.php                  # Individual blog posts
├── contact.php                    # Contact page
├── subscribe.php                  # Newsletter subscription
├── setup-database.php             # Database setup script
├── change-admin-password.php      # Admin password change
└── README.md                      # This file
```

## 🚀 Setup Instructions

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- XAMPP/WAMP/MAMP (for local development)

### Installation Steps

1. **Clone or Download** the project to your web server directory
   ```bash
   # If using git
   git clone [repository-url]
   # Or download and extract to your web server directory
   ```

2. **Configure Database**
   - Open `includes/config.php`
   - Update database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_username');
     define('DB_PASS', 'your_password');
     define('DB_NAME', 'consultant');
     ```

3. **Set Up Database**
   - Method 1: Run the setup script
     - Navigate to `http://localhost/consultancy/setup-database.php`
     - This will create the database and all tables automatically
   
   - Method 2: Manual import
     - Create a database named `consultant`
     - Import `database/consultant.sql`

4. **Configure Site Settings**
   - Update `SITE_URL` in `includes/config.php` to match your domain
   - Update email addresses in the configuration

5. **Set Permissions**
   - Ensure the `uploads/` directory is writable by the web server

6. **Access the Website**
   - Frontend: `http://localhost/consultancy/`
   - Admin Panel: `http://localhost/consultancy/admin/login.php`

### Default Admin Credentials
- **Username**: admin
- **Password**: admin123
- **Email**: admin@visaconsultancy.com

⚠️ **Important**: Change the default admin password after first login!

## 🔐 Security Features
- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **SQL Injection Prevention**: Prepared statements throughout the application
- **XSS Protection**: Input sanitization and output escaping
- **Session Security**: Secure session management with token validation
- **CSRF Protection**: Form token validation
- **File Upload Security**: Restricted file types and size limits
- **Input Validation**: Comprehensive server-side validation

## 📊 Database Schema

### Core Tables
- **users**: User accounts (admin and clients)
- **services**: Visa service offerings
- **blog_posts**: Blog content and updates
- **contact_messages**: Contact form submissions
- **settings**: Site configuration
- **visa_applications**: Client visa applications
- **testimonials**: Client reviews and ratings
- **newsletter_subscribers**: Newsletter subscription list
- **support_messages**: User support inquiries
- **password_resets**: Password reset tokens

## 🎨 Design Features
- **Responsive Design**: Mobile-first approach with Bootstrap 4
- **Modern UI**: Clean and professional interface
- **Interactive Elements**: Smooth animations and transitions
- **User-Friendly Navigation**: Intuitive menu structure
- **Professional Branding**: Consistent color scheme and typography

## 📧 Communication Features
- **Contact Forms**: Multiple contact points for different purposes
- **Newsletter System**: Email subscription management
- **Support System**: Direct messaging for user inquiries
- **Email Notifications**: Automated email responses

## 🔧 Customization

### Adding New Services
1. Access admin panel → Services Management
2. Add new service with title, description, and icon
3. Set featured status as needed

### Modifying Site Settings
1. Access admin panel → Settings
2. Update contact information, social media links, etc.

### Styling Changes
- Main stylesheet: `assets/css/style.css`
- Bootstrap customization available
- Color variables defined in CSS root

## 🐛 Troubleshooting

### Common Issues
1. **Database Connection Error**
   - Verify database credentials in `includes/config.php`
   - Ensure MySQL service is running

2. **File Upload Issues**
   - Check `uploads/` directory permissions
   - Verify file size limits in configuration

3. **Session Issues**
   - Ensure PHP sessions are enabled
   - Check session storage permissions

4. **404 Errors**
   - Verify .htaccess configuration (if using Apache)
   - Check file paths and permissions

## 📝 License
This project is licensed under the MIT License.

## 🤝 Support
For technical support or questions about the visa consultancy services, please contact:
- **Email**: info@visaconsultancy.com
- **Phone**: +1-555-123-4567

## 🔄 Version History
- **v1.0**: Initial release with core functionality
- Complete visa consultancy platform
- Admin and user interfaces
- Database management system
- Security implementations 