<?php
session_start();
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$settings = $db->fetchAll("SELECT * FROM settings");
$settings_array = array_column($settings, 'setting_value', 'setting_key');

// Get post ID from URL
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get post details
$post = $db->fetch(
    "SELECT p.*, u.username as author_name 
     FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     WHERE p.id = ? AND p.status = 'published'",
    [$post_id]
);

if (!$post) {
    header('Location: blog.php');
    exit();
}

// Get related posts
$related_posts = $db->fetchAll(
    "SELECT p.*, u.username as author_name 
     FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     WHERE p.id != ? AND p.status = 'published' 
     ORDER BY p.created_at DESC 
     LIMIT 3",
    [$post_id]
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 160)); ?>">
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
            padding: 1rem 0;
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
            color: var(--white) !important;
            border-color: var(--white) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
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

        .btn-light {
            background: var(--white) !important;
            border: 2px solid var(--white) !important;
            color: var(--primary) !important;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-light:hover {
            background: transparent !important;
            color: var(--white) !important;
            border-color: var(--white) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.3);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(rgba(6, 163, 218, 0.6), rgba(13, 202, 240, 0.6)), url('images/blog.jpeg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--white);
            padding: 120px 0 80px 0;
            position: relative;
            overflow: hidden;
            min-height: 60vh;
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

        /* Blog Post Section */
        .blog-post-section {
            padding: 5rem 0;
            background: var(--light);
            margin-top: 80px;
        }

        .blog-post {
            background: var(--white);
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .blog-post h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1.5rem;
            line-height: 1.3;
        }

        .post-meta {
            color: var(--gray);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }

        .post-meta span {
            margin-right: 1.5rem;
            display: inline-flex;
            align-items: center;
        }

        .post-meta i {
            margin-right: 0.5rem;
            color: var(--primary);
        }

        .blog-post img {
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--dark);
        }

        .post-content h2, .post-content h3, .post-content h4 {
            color: var(--dark);
            margin-top: 2rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .post-content p {
            margin-bottom: 1.5rem;
        }

        /* Social Sharing */
        .social-sharing {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #e9ecef;
        }

        .social-sharing h5 {
            color: var(--dark);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .btn-facebook {
            background: #1877f2;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-twitter {
            background: #1da1f2;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-linkedin {
            background: #0077b5;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-facebook:hover, .btn-twitter:hover, .btn-linkedin:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            color: white;
            text-decoration: none;
        }

        /* Sidebar */
        .sidebar-card {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .sidebar-card h5 {
            color: var(--dark);
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--primary);
        }

        .related-post {
            padding: 1rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .related-post:last-child {
            border-bottom: none;
        }

        .related-post h6 {
            margin-bottom: 0.5rem;
        }

        .related-post h6 a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .related-post h6 a:hover {
            color: var(--primary);
        }

        .related-post small {
            color: var(--gray);
        }

        .sidebar-card ul li {
            margin-bottom: 0.75rem;
        }

        .sidebar-card ul li a {
            color: var(--dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-card ul li a:hover {
            color: var(--primary);
            padding-left: 0.5rem;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: var(--white);
            padding: 3rem 0 1rem;
        }

        footer h5 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        footer ul {
            list-style: none;
            padding: 0;
        }

        footer ul li {
            margin-bottom: 0.5rem;
        }

        footer ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        footer ul li a:hover {
            color: var(--primary);
        }

        footer p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 0.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: var(--white);
            border-radius: 50%;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--secondary);
            transform: translateY(-3px);
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
                <img src="images/logo.png" alt="Logo" style="height:50px; width:auto; margin-right:10px;" class="d-inline-block align-middle">
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

    <!-- Blog Post Section -->
    <section class="blog-post-section">
        <div class="container">
        <div class="row">
            <!-- Blog Post Content -->
            <div class="col-lg-8">
                <article class="blog-post">
                    <h1 class="mb-4"><?php echo htmlspecialchars($post['title']); ?></h1>
                    
                        <div class="post-meta">
                            <span>
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?>
                        </span>
                            <span>
                            <i class="fas fa-calendar"></i> <?php echo date('F d, Y', strtotime($post['created_at'])); ?>
                        </span>
                    </div>

                    <?php if ($post['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($post['title']); ?>">
                    <?php endif; ?>

                    <div class="post-content">
                        <?php echo $post['content']; ?>
                    </div>

                    <!-- Social Sharing -->
                        <div class="social-sharing">
                        <h5>Share this article:</h5>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . '/blog-post.php?id=' . $post_id); ?>" class="btn btn-facebook" target="_blank">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . '/blog-post.php?id=' . $post_id); ?>&text=<?php echo urlencode($post['title']); ?>" class="btn btn-twitter" target="_blank">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode(SITE_URL . '/blog-post.php?id=' . $post_id); ?>&title=<?php echo urlencode($post['title']); ?>" class="btn btn-linkedin" target="_blank">
                            <i class="fab fa-linkedin-in"></i> LinkedIn
                        </a>
                    </div>
                </article>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Related Posts -->
                    <div class="sidebar-card">
                        <h5>Related Articles</h5>
                        <?php foreach ($related_posts as $related): ?>
                            <div class="related-post">
                                <h6>
                                    <a href="blog-post.php?id=<?php echo $related['id']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h6>
                                <small>
                                    <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($related['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                </div>

                <!-- Categories -->
                    <div class="sidebar-card">
                        <h5>Categories</h5>
                        <ul class="list-unstyled mb-0">
                            <li><a href="blog.php?category=business">Business Strategy</a></li>
                            <li><a href="blog.php?category=technology">Technology</a></li>
                            <li><a href="blog.php?category=marketing">Marketing</a></li>
                            <li><a href="blog.php?category=design">Design</a></li>
                            <li><a href="blog.php?category=development">Development</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Quick Links</h5>
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
</body>
</html> 