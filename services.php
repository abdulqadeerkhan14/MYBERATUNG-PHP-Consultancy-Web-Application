<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/db.php';

session_start();

if (isset($_SESSION['user_id'])) {
    $db = Database::getInstance();
    $user = $db->fetch("SELECT session_token FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if (!$user || !isset($_SESSION['session_token']) || $user['session_token'] !== $_SESSION['session_token'] || (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600))) {
        // Session expired or token mismatch
        session_unset();
        session_destroy();
        header('Location: user/login.php?expired=1');
        exit();
    } else {
        $_SESSION['last_activity'] = time(); // Refresh activity
    }
}

$db = Database::getInstance();
$settings = $db->fetchAll("SELECT * FROM settings");
$settings_array = array_column($settings, 'setting_value', 'setting_key');

// Get all services
$services = $db->fetchAll("SELECT * FROM services ORDER BY title");

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - MYBERATUNG</title>
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
            background-color: var(--white);
        }
        
        /* Modern Navbar */
        .navbar {
            background: var(--white) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 0.4rem 0;
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary) !important;
            text-decoration: none;
        }
        
        .navbar-nav .nav-link {
            color: var(--dark) !important;
            font-weight: 500;
            padding: 0.5rem 1.5rem !important;
            transition: all 0.3s ease;
            position: relative;
        }

        .navbar-nav .nav-link:hover {
            color: var(--primary) !important;
        }
        
        .navbar-nav .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .navbar-nav .nav-link:hover::after {
            width: 80%;
        }
        
        .btn-primary {
            background: var(--primary) !important;
            border: 2px solid var(--primary) !important;
            color: var(--white) !important;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
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
        
        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(6, 163, 218, 0.6), rgba(13, 202, 240, 0.6)), url('images/services.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--white);
            padding: 120px 0 80px 0;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="white" opacity="0.1"><polygon points="0,100 1000,0 1000,100"/></svg>');
            background-size: cover;
        }

        .hero .container {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .hero h6 {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        /* Section Styling */
        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--gray);
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-padding {
            padding: 80px 0;
        }

        /* Service Cards */
        .service-card {
            background: var(--white);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            margin-bottom: 2rem;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary), #0DCAF0);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--white);
            font-size: 2rem;
        }
        
        .service-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .service-card p {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .service-card .price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .service-card .features {
            list-style: none;
            padding: 0;
            margin-bottom: 2rem;
        }

        .service-card .features li {
            padding: 0.5rem 0;
            color: var(--gray);
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .service-card .features li:last-child {
            border-bottom: none;
        }

        .service-card .features li i {
            color: var(--primary);
            margin-right: 0.5rem;
        }

        /* Categories Section */
        .categories-section {
            background: var(--light);
            padding: 80px 0;
        }

        .category-card {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
            margin-bottom: 2rem;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .category-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .category-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .category-card .count {
            background: var(--primary);
            color: var(--white);
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: var(--white);
            padding: 60px 0 30px;
        }

        footer h5 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        footer p, footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        footer a:hover {
            color: var(--primary);
        }

        footer ul {
            list-style: none;
            padding: 0;
        }

        footer ul li {
            margin-bottom: 0.5rem;
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero {
                background-attachment: scroll;
                padding: 100px 0 60px 0;
                min-height: auto;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .service-card {
                margin-bottom: 2rem;
            }
        }

        /* Animation */
        .animate-fade-in {
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar .btn.btn-primary:hover {
            color: var(--primary) !important;
            background: var(--white) !important;
            border-color: var(--primary) !important;
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="images/logo.png" alt="Logo" style="max-height:60px; width:auto; margin-right:10px;" class="d-inline-block align-middle">
                MYBERATUNG
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ml-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item ml-lg-3">
                        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
                            <a class="btn btn-primary" href="user/dashboard.php">
                                <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                            </a>
                        <?php else: ?>
                        <a class="btn btn-primary" href="user/login.php">Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-lg-8 mx-auto text-center animate-fade-in">
                    <h6 class="text-uppercase mb-3" style="opacity: 0.8;">Our Services</h6>
                    <h1>Visa Categories</h1>
                    <p class="lead">Comprehensive visa solutions for all your international travel, study, work, and immigration needs. We offer expert guidance for various visa types.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-uppercase text-primary mb-2">Our Services</h6>
                <h2 class="section-title">Popular Services</h2>
                <p class="section-subtitle">Choose from our comprehensive range of visa services</p>
            </div>
            <div class="row ">
                <?php foreach ($services as $service): ?>
                <div class="col-lg-4 col-md-6 pb-2 bt-3">
                    <div class="service-card mt-3">
                        <div class="service-icon">
                            <i class="<?php echo htmlspecialchars($service['icon']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <?php if (!empty($service['features'])): ?>
                        <ul class="features">
                                <?php foreach (explode(',', $service['features']) as $feature): ?>
                                    <li><i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($feature)); ?></li>
                                <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <a href="user/visa-application.php" class="btn btn-primary">Apply Now</a>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </section>

    <!-- CTA Section -->
    <section class="section-padding" style="background: linear-gradient(135deg, var(--dark) 0%, #1a365d 100%); color: var(--white);">
            <div class="container">
            <div class="text-center">
                <h2 class="mb-4">Ready to Start Your Visa Journey?</h2>
                <p class="mb-4" style="opacity: 0.9;">Let our experts guide you through the visa application process and make your dreams a reality.</p>
                <a href="contact.php" class="btn btn-primary btn-lg">Get Free Consultation</a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Quick Link</h5>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="services.php">Visa Services</a></li>
                        <li><a href="blog.php">Visa Updates</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Contact</h5>
                    <p><i class="fas fa-envelope me-2"></i> <?php echo $settings_array['contact_email']; ?></p>
                    <p><i class="fas fa-phone me-2"></i> <?php echo $settings_array['contact_phone']; ?></p>
                    <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo $settings_array['address']; ?></p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>User Account</h5>
                    <ul>
                            <li><a href="user/login.php">Login</a></li>
                            <li><a href="user/faqs.php">FAQs</a></li>
                        <li><a href="user/contact.php">Support</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Follow Us</h5>
                    <div class="social-links">
                        <a href="<?php echo $settings_array['social_facebook']; ?>"><i class="fab fa-facebook-f"></i></a>
                        <a href="<?php echo $settings_array['social_twitter']; ?>"><i class="fab fa-twitter"></i></a>
                        <a href="<?php echo $settings_array['social_linkedin']; ?>"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center">
                <p>&copy; <?php echo date('Y'); ?> MYBERATUNG. All rights reserved. Designed By HTML Codex</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/navigation.js"></script>
<?php
// Example: Fetch social links from settings (already loaded as $settings_array)
$whatsapp = $settings_array['whatsapp'] ?? '';
$facebook = $settings_array['facebook'] ?? '';
$instagram = $settings_array['instagram'] ?? '';
$twitter = $settings_array['twitter'] ?? '';
?>
<?php if ($whatsapp): ?>
<a href="https://wa.me/<?php echo htmlspecialchars($whatsapp); ?>" target="_blank" class="fab-btn whatsapp-btn" title="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
<?php endif; ?>
<?php if ($facebook): ?>
<a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank" class="fab-btn facebook-btn" title="Facebook">
    <i class="fab fa-facebook-f"></i>
</a>
<?php endif; ?>
<?php if ($instagram): ?>
<a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank" class="fab-btn instagram-btn" title="Instagram">
    <i class="fab fa-instagram"></i>
</a>
<?php endif; ?>
<?php if ($twitter): ?>
<a href="<?php echo htmlspecialchars($twitter); ?>" target="_blank" class="fab-btn twitter-btn" title="Twitter">
    <i class="fab fa-twitter"></i>
</a>
<?php endif; ?>
<button type="button" class="fab-btn support-btn" title="Support" data-toggle="modal" data-target="#supportModal">
    <i class="fas fa-headset"></i>
</button>
<!-- Support Modal -->
<div class="modal fade" id="supportModal" tabindex="-1" role="dialog" aria-labelledby="supportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="supportModalLabel">Contact Support</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="admin/support-messages.php">
          <div class="form-group">
            <label for="support-email">Your Email</label>
            <input type="email" class="form-control" id="support-email" name="email" placeholder="Enter your email" required>
          </div>
          <div class="form-group">
            <label for="support-message">Your Message</label>
            <textarea class="form-control" id="support-message" name="message" rows="4" placeholder="Type your message..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Send</button>
        </form>
      </div>
    </div>
  </div>
</div>
<style>
.fab-btn {
    position: fixed;
    right: 24px;
    z-index: 1050;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    border: none;
    outline: none;
    cursor: pointer;
    transition: box-shadow 0.2s, background 0.2s;
    margin-bottom: 10px;
}
.whatsapp-btn { background: #25D366; bottom: 100px; }
.facebook-btn { background: #3b5998; bottom: 170px; }
.instagram-btn { background: #E1306C; bottom: 240px; }
.twitter-btn { background: #1da1f2; bottom: 310px; }
.support-btn { background: var(--primary); bottom: 30px; }
.fab-btn:hover { box-shadow: 0 8px 24px rgba(6,163,218,0.25); filter: brightness(1.1); }
@media (max-width: 600px) {
    .fab-btn { width: 48px; height: 48px; font-size: 1.5rem; right: 16px; }
    .whatsapp-btn { bottom: 85px; }
    .facebook-btn { bottom: 145px; }
    .instagram-btn { bottom: 205px; }
    .twitter-btn { bottom: 265px; }
    .support-btn { bottom: 20px; }
}
</style>
</body>
</html> 