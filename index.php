<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COSMI BEAUTII | Modern Web Design</title>
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
                    <li><a href="#home">Home</a></li>
                    <li><a href="#featured">Featured</a></li>
                    <li><a href="#about">About</a></li>
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

    <section id="home" class="hero">
        <div class="container">
            <h1>Elegance in Every Drop.</h1>
            <p>Discover our exclusive collection of premium skincare and beauty products, crafted with the finest ingredients to bring out your natural radiance.</p>
            <a href="products.php" class="btn-primary">Explore Products</a>
        </div>
    </section>

    <section id="featured" class="services">
        <div class="container">
            <h2 class="section-title">Featured Products</h2>
            <div class="featured-carousel-container">
                <button class="carousel-button prev"><i class="fas fa-chevron-left"></i></button>
                <div class="featured-carousel-track">
                <?php
                include 'db.php';
                // Fetch products with their primary image
                $sql = "SELECT p.*, pi.image_path AS primary_image 
                        FROM products p 
                        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                        ORDER BY p.reg_date DESC LIMIT 8";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                <div class="card featured-carousel-slide" data-id="<?php echo $row['id']; ?>">
                    <img src="images/<?php echo htmlspecialchars($row['primary_image'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($row['description']); ?></p>
                    <p class="product-price">₱<span><?php echo htmlspecialchars($row['price']); ?></span></p>
                    <button class="add-to-cart-btn"
                        data-product-id="<?php echo htmlspecialchars($row['id']); ?>"
                        data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                        data-product-price="<?php echo htmlspecialchars($row['price']); ?>"
                        data-product-image="<?php echo htmlspecialchars($row['primary_image'] ?? 'placeholder.png'); ?>">
                        Add to Cart</button>
                </div>
                <?php
                    }
                } else {
                    echo "<p>No featured products available at the moment.</p>";
                }
                ?>
                </div>
                <button class="carousel-button next"><i class="fas fa-chevron-right"></i></button>
                <div class="carousel-nav"></div>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="products.php" class="btn-primary">Shop All Products</a>
            </div>
        </div>
    </section>

    <section id="about" class="about-section">
        <div class="container">
            <h2 class="section-title">About COSMI BEAUTII</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>At COSMI BEAUTII, we believe that beauty is an art, and your skin is the canvas. Our journey began with a simple mission: to create luxurious, effective, and ethically sourced beauty products that inspire confidence and celebrate individuality.</p>
                    <p>We blend the finest natural ingredients with cutting-edge science to develop skincare and makeup that not only beautifies but also nourishes. Our products are cruelty-free, paraben-free, and crafted with love and respect for our planet.</p>
                    <a href="#" class="btn-secondary">Learn More</a>
                </div>
                <div class="about-image">
                    <img src="images/about-us.PNG" alt="About Us">
                </div>
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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#featured">Featured</a></li>
                        <li><a href="#about">About</a></li>
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const carouselTrack = document.querySelector('.featured-carousel-track');
            const slides = Array.from(carouselTrack.children);
            const nextButton = document.querySelector('.carousel-button.next');
            const prevButton = document.querySelector('.carousel-button.prev');
            const carouselNav = document.querySelector('.carousel-nav');

            if (slides.length === 0) {
                // No slides, hide carousel controls
                if (nextButton) nextButton.style.display = 'none';
                if (prevButton) prevButton.style.display = 'none';
                if (carouselNav) carouselNav.style.display = 'none';
                return;
            }

            let slideWidth;
            let slidesPerView;
            let currentIndex = 0;

            const getSlidesPerView = () => {
                if (window.innerWidth <= 480) return 1;
                if (window.innerWidth <= 768) return 2;
                if (window.innerWidth <= 1200) return 3;
                return 4;
            };

            const updateCarouselDimensions = () => {
                slidesPerView = getSlidesPerView();
                // Calculate slideWidth based on the first slide's actual width including margin
                if (slides.length > 0) {
                    const firstSlide = slides[0];
                    const slideStyle = window.getComputedStyle(firstSlide);
                    const marginRight = parseFloat(slideStyle.marginRight);
                    slideWidth = firstSlide.offsetWidth + marginRight;
                } else {
                    slideWidth = 0; // No slides, no width
                }
                updateCarouselPosition();
                createIndicators(); // Recreate indicators on resize
                updateIndicators();
                toggleNavButtons();
            };

            const createIndicators = () => {
                carouselNav.innerHTML = ''; // Clear existing indicators
                const totalPages = Math.ceil(slides.length / slidesPerView);
                for (let i = 0; i < totalPages; i++) {
                    const indicator = document.createElement('span');
                    indicator.classList.add('carousel-indicator');
                    indicator.dataset.index = i;
                    indicator.addEventListener('click', () => {
                        currentIndex = i * slidesPerView; // Go to the start of the page
                        updateCarouselPosition();
                        updateIndicators();
                        toggleNavButtons();
                    });
                    carouselNav.appendChild(indicator);
                }
            };

            const updateIndicators = () => {
                const indicators = Array.from(carouselNav.children);
                indicators.forEach((indicator, i) => {
                    if (i === Math.floor(currentIndex / slidesPerView)) {
                        indicator.classList.add('active');
                    } else {
                        indicator.classList.remove('active');
                    }
                });
            };

            const updateCarouselPosition = () => {
                carouselTrack.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
            };

            const toggleNavButtons = () => {
                prevButton.disabled = currentIndex === 0;
                nextButton.disabled = currentIndex >= slides.length - slidesPerView;
            };

            nextButton.addEventListener('click', () => {
                if (currentIndex < slides.length - slidesPerView) {
                    currentIndex += slidesPerView;
                    updateCarouselPosition();
                    updateIndicators();
                    toggleNavButtons();
                }
            });

            prevButton.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex -= slidesPerView;
                    updateCarouselPosition();
                    updateIndicators();
                    toggleNavButtons();
                }
            });

            // Initial setup and on resize
            window.addEventListener('resize', updateCarouselDimensions);
            updateCarouselDimensions(); // Call initially
        });
    </script>
</body>
</html>
