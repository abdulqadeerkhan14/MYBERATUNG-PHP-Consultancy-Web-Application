<?php
define('SECURE_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/db.php';

$db = Database::getInstance();
$settings = $db->fetchAll("SELECT * FROM settings");
$settings_array = array_column($settings, 'setting_value', 'setting_key');

// Get featured services
$featured_services = $db->fetchAll(
    "SELECT * FROM services WHERE is_featured = 1 ORDER BY title LIMIT 3"
);

// Get recent blog posts
$recent_posts = $db->fetchAll(
    "SELECT p.*, u.username as author_name 
     FROM blog_posts p 
     LEFT JOIN users u ON p.author_id = u.id 
     WHERE p.status = 'published' 
     ORDER BY p.created_at DESC 
     LIMIT 3"
);

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MYBERATUNG - Professional Visa Consultancy Services</title>
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
            background: linear-gradient(rgba(6, 163, 218, 0.6), rgba(13, 202, 240, 0.6)), url('images/hero-img.jpg');
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

        .hero-buttons .btn {
            margin: 0.5rem;
            min-width: 160px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
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
        }

        /* Feature Cards */
        .feature-card {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--gray);
        }

        /* Blog Cards */
        .blog-card {
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .blog-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }

        .blog-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .blog-card .card-body {
            padding: 1.5rem;
        }

        .blog-card h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .post-meta {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .post-meta i {
            margin-right: 0.5rem;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--dark) 0%, #1a365d 100%);
            color: var(--white);
            padding: 80px 0;
        }

        .cta-box {
            text-align: center;
        }

        .cta-box h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-box p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
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
            
            .hero-buttons .btn {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
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
                    <h6 class="text-uppercase mb-3" style="opacity: 0.8;">Best Visa Services</h6>
                    <h1>Welcome to MYBERATUNG</h1>
                    <p class="lead">Professional visa consultancy services to help you navigate the complex world of international travel, study, work, and immigration with confidence and success.</p>
                    <div class="hero-buttons">
                        <a href="services.php" class="btn btn-primary">Read More</a>
                        <a href="contact.php" class="btn btn-light">Join Now</a>
                    </div>
                </div>
            </div>
        </div>
    </section>  

    <!-- Services Section -->
    <section class="section-padding" style="background: var(--light);">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-uppercase text-primary mb-2">Our Services</h6>
                <h2 class="section-title">Visa Categories</h2>
                <p class="section-subtitle">Comprehensive visa solutions for all your international travel needs</p>
            </div>
            <div class="row">
                <?php foreach ($featured_services as $service): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="<?php echo htmlspecialchars($service['icon']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                        <a href="services.php" class="btn btn-outline-primary">Learn More</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section-padding">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-uppercase text-primary mb-2">Why Choose Us</h6>
                <h2 class="section-title">Expert Instructors</h2>
                <p class="section-subtitle">Experience the difference with our expert visa consultants</p>
            </div>
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-certificate feature-icon"></i>
                        <h3>Expert Consultants</h3>
                        <p>Our team consists of certified visa consultants with years of experience in immigration law and procedures.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-chart-line feature-icon"></i>
                        <h3>High Success Rate</h3>
                        <p>We've helped thousands of clients successfully obtain their visas with a proven track record of success.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-handshake feature-icon"></i>
                        <h3>Personalized Support</h3>
                        <p>We provide personalized guidance throughout your entire visa application process.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card">
                        <i class="fas fa-clock feature-icon"></i>
                        <h3>24/7 Support</h3>
                        <p>Round-the-clock support to answer your questions and provide assistance whenever you need it.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Section -->
    <section class="section-padding" style="background: var(--light);">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-uppercase text-primary mb-2">Latest Updates</h6>
                <h2 class="section-title">Visa Updates & News</h2>
                <p class="section-subtitle">Stay informed with the latest visa policies and immigration news</p>
            </div>
            <div class="row">
                <?php foreach ($recent_posts as $post): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="blog-card">
                        <?php if ($post['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <p class="post-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name']); ?>
                                <i class="fas fa-calendar ml-3"></i> <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                            </p>
                            <p><?php echo substr(strip_tags($post['content']), 0, 120) . '...'; ?></p>
                            <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline-primary">Read More</a>
                        </div>
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

    <!-- Testimonial Section -->
    <section class="section-padding bg-light" id="testimonials">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-uppercase text-primary mb-2">Testimonials</h6>
                <h2 class="section-title">What Our Clients Say</h2>
                <p class="section-subtitle">Real experiences from our valued clients</p>
            </div>
            <!-- Carousel -->
            <div id="testimonialCarousel" class="carousel slide mb-5" data-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $testimonials = $db->fetchAll("SELECT t.*, u.username FROM testimonials t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 6");
                    $active = 'active';
                    foreach ($testimonials as $testimonial): ?>
                        <div class="carousel-item <?php echo $active; ?>">
                            <div class="testimonial-card p-4 bg-white rounded shadow-sm mx-auto" style="max-width:600px;">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mr-3">
                                        <i class="fas fa-user-circle fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0"><?php echo htmlspecialchars($testimonial['username']); ?></h5>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php if ($i > $testimonial['rating']) echo '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="mb-2">"<?php echo htmlspecialchars($testimonial['review']); ?>"</p>
                                <small class="text-muted"><?php echo date('F j, Y', strtotime($testimonial['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php $active = ''; endforeach; ?>
                </div>
                <a class="carousel-control-prev" href="#testimonialCarousel" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#testimonialCarousel" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
            </div>
            <!-- Review Form (only for logged-in users) -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="card mx-auto" style="max-width:600px;">
                <div class="card-body">
                    <h5 class="card-title mb-3">Leave a Review</h5>
                    <?php if (!empty($review_success)): ?>
                        <div class="alert alert-success">Thank you for your review!</div>
                    <?php endif; ?>
                    <form method="POST" action="#testimonials">
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
            <?php endif; ?>
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
    <!-- Floating WhatsApp, Support, and Social Buttons -->
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
<script>
$(function(){
  $('#supportModal form').on('submit', function(e){
    e.preventDefault();
    var $form = $(this);
    var $btn = $form.find('button[type=submit]');
    $btn.prop('disabled', true).text('Sending...');
    $.ajax({
      url: 'admin/support-messages.php',
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      success: function(res){
        $btn.prop('disabled', false).text('Send');
        if(res.success){
          $form[0].reset();
          $('#supportModal').modal('hide');
          setTimeout(function(){ alert('Your support message was sent successfully!'); }, 400);
        }else{
          alert(res.message || 'Failed to send support message.');
        }
      },
      error: function(){
        $btn.prop('disabled', false).text('Send');
        alert('Failed to send support message.');
      }
    });
  });
});
</script>
</body>
</html> 