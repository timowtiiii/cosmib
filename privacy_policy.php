<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="floating-cart.css">
</head>
<body>

    <header class="navbar">
        <div class="container">
            <div class="logo">COSMI<span>BEAUTII</span></div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php#home">Home</a></li>
                    <li><a href="index.php#featured">Featured</a></li>
                    <li><a href="index.php#about">About</a></li>
                    <li><a href="products.php">Shop</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="user-menu">
                            <div class="user-menu-toggle">
                                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span> <i class="fas fa-caret-down"></i>
                            </div>
                            <ul class="user-menu-dropdown">
                                <?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
                                    <li><a href="admin_dashboard.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><a href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php" class="btn-cta">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section id="legal-content" style="padding: 120px 0 100px;">
        <div class="container">
            <div class="card" style="padding: 40px; text-align: left;">
                <h2 class="section-title" style="margin-bottom: 40px; text-align: center;">Privacy Policy</h2>
                <p><strong>Last Updated: <?php echo date("F j, Y"); ?></strong></p>
                <br>
                <h3>1. Introduction</h3>
                <p>Welcome to COSMI BEAUTII. We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.</p>
                <br>
                <h3>2. Information We Collect</h3>
                <p>We may collect personal information such as your name, email address, shipping address, and payment information when you place an order. We also collect non-personal information, such as browser type and pages visited, to improve our services.</p>
                <br>
                <h3>3. How We Use Your Information</h3>
                <p>We use the information we collect to process and manage your orders, communicate with you, improve our website, and send you promotional materials if you opt-in.</p>
                <br>
                <h3>4. Sharing Your Information</h3>
                <p>We do not sell, trade, or otherwise transfer to outside parties your Personally Identifiable Information unless we provide users with advance notice. This does not include trusted third parties who assist us in operating our website, so long as those parties agree to keep this information confidential.</p>
                <br>
                <h3>5. Security of Your Information</h3>
                <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that no security measures are perfect or impenetrable.</p>
                <br>
                <h3>6. Contact Us</h3>
                <p>If you have any questions about this Privacy Policy, please contact us at wihiasiahrdepartment@gmail.com.</p>
            </div>
        </div>
    </section>

    <?php include 'floating_cart.php'; ?>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-info">
                    <h3>COSMI<span>BEAUTII</span></h3>
                    <p>Elevating beauty with nature's finest ingredients.</p>
                    <div class="footer-contact">
                        <p><i class="fas fa-map-marker-alt"></i> #99 MH Del Pilar Street, Brgy Balite,, Rodriguez, Philippines, 1860</p>
                        <p><i class="fas fa-envelope"></i>  wihiasiahrdepartment@gmail.com</p>
                        <p><i class="fas fa-phone"></i>  02-7001-2508</p>
                    </div>
                </div>
                <div class="footer-links">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php#home">Home</a></li>
                        <li><a href="index.php#featured">Featured</a></li>
                        <li><a href="index.php#about">About</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="privacy_policy.php">Privacy Policy</a></li>
                        <li><a href="terms_of_service.php">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-socials">
                    <h4>Follow Us</h4>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-github"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date("2026"); ?> COSMI BEAUTII. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="cart.js"></script>
</body>
</html>