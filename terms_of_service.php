<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service | COSMI BEAUTII</title>
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
                <h2 class="section-title" style="margin-bottom: 40px; text-align: center;">Terms of Service</h2>
                <p><strong>Last Updated: <?php echo date("F j, Y"); ?></strong></p>
                <br>
                <h3>1. Agreement to Terms</h3>
                <p>By accessing and using our website, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.</p>
                <br>
                <h3>2. Use License</h3>
                <p>Permission is granted to temporarily download one copy of the materials on COSMI BEAUTII's website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>
                <br>
                <h3>3. Disclaimer</h3>
                <p>The materials on COSMI BEAUTII's website are provided on an 'as is' basis. COSMI BEAUTII makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>
                <br>
                <h3>4. Limitations</h3>
                <p>In no event shall COSMI BEAUTII or its suppliers be liable for any damages arising out of the use or inability to use the materials on COSMI BEAUTII's website.</p>
                <br>
                <h3>5. Governing Law</h3>
                <p>These terms and conditions are governed by and construed in accordance with the laws of the Philippines and you irrevocably submit to the exclusive jurisdiction of the courts in that State or location.</p>
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