<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}
// Handle form submissions for adding/editing products
if($_SERVER["REQUEST_METHOD"] == "POST"){
    if(isset($_POST['add_product'])){
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $image = $_FILES['image']['name'];
        $target = "images/".basename($image);
        $sql = "INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssds", $name, $description, $price, $image);
        $stmt->execute();
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <header class="navbar">
        <div class="container">
            <div class="logo">COSMI<span>BEAUTII</span> (Admin)</div>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php" target="_blank">View Site</a></li>
                    <li><a href="logout.php" class="btn-cta">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section id="home" class="hero">
        <div class="container">
            <h1 id="hero-title" contenteditable="true" data-key="title">Elegance in Every Drop.</h1>
            <p id="hero-subtitle" contenteditable="true" data-key="subtitle">Discover our exclusive collection of premium skincare and beauty products, crafted with the finest ingredients to bring out your natural radiance.</p>
            <a href="products.php" class="btn-primary">Explore Products</a>
        </div>
    </section>

    <section id="featured" class="services">
        <div class="container">
            <h2 id="featured-title" class="section-title" contenteditable="true" data-key="title">Featured Products</h2>
            
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="text-align: left; margin-bottom: 20px;">Add New Product</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <input type="text" name="name" placeholder="Product Name" required style="padding: 10px;">
                        <input type="number" step="0.01" name="price" placeholder="Price" required style="padding: 10px;">
                    </div>
                    <textarea name="description" placeholder="Product Description" required style="width: 100%; padding: 10px; margin-top: 20px;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <input type="file" name="image" required>
                        <button type="submit" name="add_product" class="btn-primary" style="margin-top: 0;">Add Product</button>
                    </div>
                </form>
            </div>
            
            <div class="grid">
                <?php
                $sql = "SELECT * FROM products ORDER BY reg_date DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                ?>
                <div class="card" data-id="<?php echo $row['id']; ?>">
                    <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width:100%; border-radius: 15px; margin-bottom: 20px;">
                    <h3 contenteditable="true" data-key="name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p contenteditable="true" data-key="description"><?php echo htmlspecialchars($row['description']); ?></p>
                    <p style="font-size: 1.2rem; font-weight: 600; color: var(--accent); margin-top: 10px;">₱<span contenteditable="true" data-key="price"><?php echo htmlspecialchars($row['price']); ?></span></p>
                    <button class="save-btn">Save</button>
                    <button class="delete-btn">Delete</button>
                </div>
                <?php
                    }
                } else {
                    echo "<p>No products available at the moment.</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <section id="about" class="about-section">
        <div class="container">
            <h2 id="about-title" class="section-title" contenteditable="true" data-key="title">About COSMI BEAUTII</h2>
            <div class="about-content">
                <div class="about-text">
                    <p id="about-p1" contenteditable="true" data-key="p1">At COSMI BEAUTII, we believe that beauty is an art, and your skin is the canvas. Our journey began with a simple mission: to create luxurious, effective, and ethically sourced beauty products that inspire confidence and celebrate individuality.</p>
                    <p id="about-p2" contenteditable="true" data-key="p2">We blend the finest natural ingredients with cutting-edge science to develop skincare and makeup that not only beautifies but also nourishes. Our products are cruelty-free, paraben-free, and crafted with love and respect for our planet.</p>
                    <a href="#" class="btn-secondary">Learn More</a>
                </div>
                <div class="about-image">
                    <img src="images/about-us.jpg" alt="About Us">
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-info">
                    <h3 id="footer-title" contenteditable="true" data-key="title">COSMI<span>BEAUTII</span></h3>
                    <p id="footer-subtitle" contenteditable="true" data-key="subtitle">Elevating beauty with nature's finest ingredients.</p>
                    <div class="footer-contact">
                        <p id="footer-address" contenteditable="true" data-key="address"><i class="fas fa-map-marker-alt"></i> #99 MH Del Pilar Street, Brgy Balite,, Rodriguez, Philippines, 1860</p>
                        <p id="footer-email" contenteditable="true" data-key="email"><i class="fas fa-envelope"></i> wihiasiahrdepartment@gmail.com</p>
                        <p id="footer-phone" contenteditable="true" data-key="phone"><i class="fas fa-phone"></i> 02-7001-2508</p>
                    </div>
                </div>
                <div class="footer-links">
                    <h4 id="footer-links-title" contenteditable="true" data-key="title">Quick Links</h4>
                    <ul>
                        <li><a href="#home">Home</a></li>
                        <li><a href="#featured">Featured</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="products.php">Shop</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="footer-socials">
                    <h4 id="footer-socials-title" contenteditable="true" data-key="title">Follow Us</h4>
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
    
    <div id="save-changes-container">
        <button id="save-all-changes" class="btn-primary">Save All Changes</button>
    </div>
    
    <script>
        document.getElementById('save-all-changes').addEventListener('click', () => {
            const editableElements = document.querySelectorAll('[contenteditable="true"]');
            const changes = {};

            editableElements.forEach(el => {
                const id = el.id || el.parentElement.dataset.id || el.parentElement.parentElement.dataset.id;
                const key = el.dataset.key || el.tagName.toLowerCase();
                const content = el.innerHTML;
                
                if(id) {
                    if(!changes[id]) {
                        changes[id] = {};
                    }
                    changes[id][key] = content;
                } else {
                    if(!changes['page_content']) {
                        changes['page_content'] = {};
                    }
                    changes['page_content'][el.id] = content;
                }
            });

            const formData = new FormData();
            formData.append('changes', JSON.stringify(changes));

            fetch('update_content.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(data);
                alert('All changes saved successfully!');
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
                    alert('Product saved successfully!');
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
    </script>
</body>
</html>