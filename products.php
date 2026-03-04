<?php
session_start();
include 'db.php';

// Fetch all categories for the filter menu
$categories = [];
$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Check for a category filter from the URL
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$category_name = 'All Products';

// Base SQL query for products
$sql_products = "SELECT p.*, pi.image_path AS primary_image 
                 FROM products p 
                 LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                 ";

// If a category is selected, modify the query and get the category name for the title
if ($category_filter) {
    // Find category name for the title
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_filter) {
            $category_name = $cat['name'];
            break;
        }
    }
    $sql_products .= " JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_id = ?";
    $stmt = $conn->prepare($sql_products);
    $stmt->bind_param("i", $category_filter);
    $stmt->execute();
    $products_result = $stmt->get_result();
} else {
    // No filter, get all products
    $products_result = $conn->query($sql_products);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="floating-cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .category-filters {
            text-align: center;
            margin-bottom: 40px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
        }
        .category-filters a {
            text-decoration: none;
            color: var(--text-muted);
            background: var(--card-bg);
            padding: 10px 20px;
            border-radius: 50px;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
        }
        .category-filters a:hover, .category-filters a.active {
            background: var(--accent);
            color: var(--bg);
            border-color: var(--accent);
        }
    </style>
</head>
<body>

    <header class="navbar">
        <div class="container">
            <div class="logo">COSMI<span>BEAUTII</span></div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
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

    <section id="products" style="padding-top: 120px;">
        <div class="container">
            <h2 class="section-title"><?php echo htmlspecialchars($category_name); ?></h2>

            <!-- Category Filters -->
            <div class="category-filters">
                <a href="products.php" class="<?php echo !$category_filter ? 'active' : ''; ?>">All</a>
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?php echo $category['id']; ?>" class="<?php echo ($category_filter == $category['id']) ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="product-grid">
                <?php if ($products_result && $products_result->num_rows > 0): ?>
                    <?php while($row = $products_result->fetch_assoc()): ?>
                    <div class="card">
                        <img src="images/<?php echo htmlspecialchars($row['primary_image'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                        <p style="font-size: 1.2rem; font-weight: 600; color: var(--accent); margin-top: 10px;">₱<?php echo htmlspecialchars($row['price']); ?></p>
                        <button class="add-to-cart-btn" data-product-id="<?php echo $row['id']; ?>" data-product-name="<?php echo htmlspecialchars($row['name']); ?>" data-product-price="<?php echo $row['price']; ?>" data-product-image="<?php echo htmlspecialchars($row['primary_image'] ?? 'placeholder.png'); ?>">Add to Cart</button>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="grid-column: 1 / -1; text-align: center; color: var(--text-muted);">No products found in this category.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'floating_cart.php'; ?>
    <script src="cart.js"></script>

</body>
</html>