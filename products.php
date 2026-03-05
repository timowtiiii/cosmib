<?php
session_start();
include 'db.php'; // Assuming db.php exists and connects to the database

// Fetch all categories
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);
$categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="floating-cart.css">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <div class="logo">COSMI<span>BEAUTII</span></div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Shop</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="user-menu">
                            <a href="#" class="user-menu-toggle">
                                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span> <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="user-menu-dropdown">
                                <?php if (isset($_SESSION['admin_loggedin']) && $_SESSION['admin_loggedin'] === true): ?>
                                    <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><a href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="login.php" class="btn-cta">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section id="products-by-category" style="padding-top: 120px; padding-bottom: 100px;">
        <div class="container">
            <h2 class="section-title">Our Products</h2>

            <div class="category-navigation">
                <ul class="category-nav-list">
                    <?php foreach ($categories as $category): ?>
                        <li><a href="#category-<?php echo $category['id']; ?>" class="category-nav-link"><?php echo htmlspecialchars($category['name']); ?></a></li>
                    <?php endforeach; ?>
                    <?php
                    // Check if there are any uncategorized products to display the link
                    $uncategorized_check_sql = "SELECT COUNT(*) FROM products p LEFT JOIN product_categories pc ON p.id = pc.product_id WHERE pc.product_id IS NULL";
                    $uncategorized_count = $conn->query($uncategorized_check_sql)->fetch_row()[0];
                    if ($uncategorized_count > 0): ?>
                        <li><a href="#category-uncategorized" class="category-nav-link">Uncategorized</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if (empty($categories)): ?>
                <p style="text-align: center; color: var(--text-muted);">No categories found. Please add categories from the admin panel.</p>
            <?php else: ?>
                <?php foreach ($categories as $category): ?>
                    <div class="category-section" id="category-<?php echo $category['id']; ?>">
                        <h3 class="category-title"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <div class="product-grid">
                            <?php
                            $sql_products = "SELECT p.id, p.name, p.description, p.price, p.stock, pi.image_path AS primary_image
                                             FROM products p
                                             JOIN product_categories pc ON p.id = pc.product_id
                                             LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                             WHERE pc.category_id = ?
                                             ORDER BY p.name ASC";
                            $stmt_products = $conn->prepare($sql_products);
                            $stmt_products->bind_param("i", $category['id']);
                            $stmt_products->execute();
                            $products_result = $stmt_products->get_result();

                            if ($products_result->num_rows > 0) {
                                while ($product_row = $products_result->fetch_assoc()) {
                                    $stock = $product_row['stock'];
                                    $stock_indicator = '';
                                    if ($stock <= 0) {
                                        $stock_indicator = '<div class="stock-indicator out-of-stock">Out of Stock</div>';
                                    } elseif ($stock <= 10) { // Low stock threshold
                                        $stock_indicator = '<div class="stock-indicator low-stock">Low Stock</div>';
                                    }

                                    $image_path = $product_row['primary_image'] ? htmlspecialchars($product_row['primary_image']) : 'placeholder.png';
                                    echo '<div class="card">';
                                    echo $stock_indicator;
                                    echo '<img src="images/' . $image_path . '" alt="' . htmlspecialchars($product_row['name']) . '">';
                                    echo '<h3>' . htmlspecialchars($product_row['name']) . '</h3>';
                                    echo '<p>' . htmlspecialchars($product_row['description']) . '</p>';
                                    echo '<p style="font-weight: 600; color: var(--accent);">₱' . number_format($product_row['price'], 2) . '</p>';
                                    if ($stock > 0) {
                                        echo '<button class="add-to-cart-btn" data-product-id="' . $product_row['id'] . '" data-product-name="' . htmlspecialchars($product_row['name']) . '" data-product-price="' . $product_row['price'] . '" data-product-image="' . $image_path . '">Add to Cart</button>';
                                    } else {
                                        echo '<button class="add-to-cart-btn" disabled style="background: var(--border-color); color: var(--text-muted); cursor: not-allowed;">Out of Stock</button>';
                                    }
                                    echo '</div>';
                                }
                            } else {
                                echo '<p style="color: var(--text-muted); grid-column: 1 / -1;">No products in this category yet.</p>';
                            }
                            $stmt_products->close();
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php
            // Display uncategorized products if any
            $sql_uncategorized = "SELECT p.id, p.name, p.description, p.price, p.stock, pi.image_path AS primary_image
                                  FROM products p
                                  LEFT JOIN product_categories pc ON p.id = pc.product_id
                                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                  WHERE pc.product_id IS NULL
                                  ORDER BY p.name ASC";
            $uncategorized_result = $conn->query($sql_uncategorized);

            if ($uncategorized_result->num_rows > 0) {
                echo '<div class="category-section" id="category-uncategorized">';
                echo '<h3 class="category-title">Uncategorized Products</h3>';
                echo '<div class="product-grid">';
                while ($product_row = $uncategorized_result->fetch_assoc()) {
                    $stock = $product_row['stock'];
                    $stock_indicator = '';
                    if ($stock <= 0) {
                        $stock_indicator = '<div class="stock-indicator out-of-stock">Out of Stock</div>';
                    } elseif ($stock <= 10) { // Low stock threshold
                        $stock_indicator = '<div class="stock-indicator low-stock">Low Stock</div>';
                    }

                    $image_path = $product_row['primary_image'] ? htmlspecialchars($product_row['primary_image']) : 'placeholder.png';
                    echo '<div class="card">';
                    echo $stock_indicator;
                    echo '<img src="images/' . $image_path . '" alt="' . htmlspecialchars($product_row['name']) . '">';
                    echo '<h3>' . htmlspecialchars($product_row['name']) . '</h3>';
                    echo '<p>' . htmlspecialchars($product_row['description']) . '</p>';
                    echo '<p style="font-weight: 600; color: var(--accent);">₱' . number_format($product_row['price'], 2) . '</p>';
                    if ($stock > 0) {
                        echo '<button class="add-to-cart-btn" data-product-id="' . $product_row['id'] . '" data-product-name="' . htmlspecialchars($product_row['name']) . '" data-product-price="' . $product_row['price'] . '" data-product-image="' . $image_path . '">Add to Cart</button>';
                    } else {
                        echo '<button class="add-to-cart-btn" disabled style="background: var(--border-color); color: var(--text-muted); cursor: not-allowed;">Out of Stock</button>';
                    }
                    echo '</div>';
                }
                echo '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </section>

    <!-- Floating Cart HTML (assuming it's included via PHP or directly) -->
    <div id="floating-cart-container"> 
        <div id="cart-toggle-icon">
            <i class="fas fa-shopping-cart"></i>
            <span id="cart-count">0</span>
        </div>
        <div id="floating-cart">
            <div id="cart-header">
                <h2>Your Cart</h2>
                <button id="close-cart-btn"><i class="fas fa-times"></i></button>
            </div>
            <div id="cart-body">
                <div id="cart-items">
                    <!-- Cart items will be rendered here by JavaScript -->
                </div>
            </div>
            <div id="cart-footer">
                <span id="cart-total">₱0.00</span>
                <a href="checkout.php" class="btn-primary">Checkout</a>
            </div>
        </div>
    </div>

    <script src="cart.js"></script>
</body>
</html>