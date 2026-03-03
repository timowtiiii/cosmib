<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <li><a href="products.php" class="active">Shop</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span></li>
                        <li><a href="logout.php" class="btn-cta">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php" class="btn-cta">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section id="products" class="services" style="padding-top: 120px;">
        <div class="container">
            <h2 class="section-title">All Products</h2>
            <div class="product-grid">
                <?php
                include 'db.php';
                $sql = "SELECT * FROM products ORDER BY reg_date DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                <div class="card" data-id="<?php echo htmlspecialchars($row['id']); ?>">
                    <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($row['description']); ?></p>
                    <p class="product-price">₱<span><?php echo htmlspecialchars($row['price']); ?></span></p>
                    <button class="add-to-cart-btn"
                        data-product-id="<?php echo htmlspecialchars($row['id']); ?>"
                        data-product-name="<?php echo htmlspecialchars($row['name']); ?>"
                        data-product-price="<?php echo htmlspecialchars($row['price']); ?>"
                        data-product-image="<?php echo htmlspecialchars($row['image']); ?>">
                        Add to Cart</button>
                </div>
                <?php
                    }
                } else {
                    echo "<p>No products available at the moment.</p>";
                }
                $conn->close();
                ?>
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
                </div>
                <div class="footer-socials">
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
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const card = button.closest('.card');
                    card.querySelector('h3').contentEditable = true;
                    card.querySelector('p').contentEditable = true;
                    card.querySelector('span').contentEditable = true;
                    button.style.display = 'none';
                    card.querySelector('.save-btn').style.display = 'inline-block';
                });
            });

            document.querySelectorAll('.save-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const card = button.closest('.card');
                    const id = card.dataset.id;
                    const name = card.querySelector('h3').innerText;
                    const description = card.querySelector('p').innerText;
                    const price = card.querySelector('span').innerText;

                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('name', name);
                    formData.append('description', description);
                    formData.append('price', price);

                    fetch('save_product.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        console.log(data);
                        card.querySelector('h3').contentEditable = false;
                        card.querySelector('p').contentEditable = false;
                        card.querySelector('span').contentEditable = false;
                        button.style.display = 'none';
                        card.querySelector('.edit-btn').style.display = 'inline-block';
                    });
                });
            });

            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', () => {
                    if(confirm('Are you sure you want to delete this product?')){
                        const card = button.closest('.card');
                        const id = card.dataset.id;

                        const formData = new FormData();
                        formData.append('id', id);

                        fetch('delete_product.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.text())
                        .then(data => {
                            console.log(data);
                            card.remove();
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>