<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// Handle form submissions for adding/editing products
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- CATEGORY MANAGEMENT ---
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $sql = "INSERT INTO categories (name) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            header("Location: edit_products.php");
            exit;
        } else {
            // Handle error, e.g., category name already exists
            echo "Error adding category: " . $stmt->error;
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $sql = "UPDATE categories SET name = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error updating category: " . $stmt->error;
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = $_POST['id'];
        // The foreign key in `products` is set to ON DELETE SET NULL,
        // so products in this category will become uncategorized.
        $sql = "DELETE FROM categories WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error deleting category: " . $stmt->error;
        }
    }
    // --- PRODUCT MANAGEMENT ---
    elseif (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_ids = $_POST['category_ids'] ?? [];
        $image_files = $_FILES['images'];

        $conn->begin_transaction();
        try {
            // 1. Insert product
            $sql_prod = "INSERT INTO products (name, description, price) VALUES (?, ?, ?)";
            $stmt_prod = $conn->prepare($sql_prod);
            $stmt_prod->bind_param("ssd", $name, $description, $price);
            $stmt_prod->execute();
            $product_id = $stmt_prod->insert_id;

            // 2. Link categories
            if (!empty($category_ids)) {
                $sql_cat = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                $stmt_cat = $conn->prepare($sql_cat);
                foreach ($category_ids as $category_id) {
                    $stmt_cat->bind_param("ii", $product_id, $category_id);
                    $stmt_cat->execute();
                }
            }

            // 3. Handle image uploads
            if (!empty($image_files['name'][0])) {
                $is_first_image = true;
                $sql_image = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                $stmt_image = $conn->prepare($sql_image);
                foreach ($image_files['name'] as $key => $image_name) {
                    $target_file = "images/" . basename($image_name);
                    if (move_uploaded_file($image_files['tmp_name'][$key], $target_file)) {
                        $is_primary = $is_first_image ? 1 : 0;
                        $stmt_image->bind_param("isi", $product_id, $image_name, $is_primary);
                        $stmt_image->execute();
                        $is_first_image = false;
                    }
                }
            }
            $conn->commit();
            header("Location: edit_products.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error adding product: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_product'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_ids = $_POST['category_ids'] ?? [];
        $new_images = $_FILES['new_images'];

        $conn->begin_transaction();
        try {
            // 1. Update basic product info
            $sql = "UPDATE products SET name=?, description=?, price=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdi", $name, $description, $price, $id);
            $stmt->execute();

            // 2. Update categories
            $stmt_del_cat = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
            $stmt_del_cat->bind_param("i", $id);
            $stmt_del_cat->execute();
            if (!empty($category_ids)) {
                $sql_cat = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                $stmt_cat = $conn->prepare($sql_cat);
                foreach ($category_ids as $category_id) {
                    $stmt_cat->bind_param("ii", $id, $category_id);
                    $stmt_cat->execute();
                }
            }

            // 3. Set primary image
            if (isset($_POST['primary_image_id'])) {
                $primary_image_id = $_POST['primary_image_id'];
                $stmt_reset = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
                $stmt_reset->bind_param("i", $id);
                $stmt_reset->execute();
                $stmt_set_primary = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
                $stmt_set_primary->bind_param("i", $primary_image_id);
                $stmt_set_primary->execute();
            }

            // 4. Delete selected images
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                $sql_select_path = "SELECT image_path FROM product_images WHERE id = ?";
                $stmt_select_path = $conn->prepare($sql_select_path);
                $sql_delete_img = "DELETE FROM product_images WHERE id = ?";
                $stmt_delete_img = $conn->prepare($sql_delete_img);
                foreach ($_POST['delete_images'] as $image_id_to_delete) {
                    $stmt_select_path->bind_param("i", $image_id_to_delete);
                    $stmt_select_path->execute();
                    if ($row_path = $stmt_select_path->get_result()->fetch_assoc()) {
                        @unlink("images/" . $row_path['image_path']);
                    }
                    $stmt_delete_img->bind_param("i", $image_id_to_delete);
                    $stmt_delete_img->execute();
                }
            }

            // 5. Add new images
            if (!empty($new_images['name'][0])) {
                $stmt_check_primary = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND is_primary = 1");
                $stmt_check_primary->bind_param("i", $id);
                $stmt_check_primary->execute();
                $has_primary = $stmt_check_primary->get_result()->fetch_assoc()['count'] > 0;

                $sql_image = "INSERT INTO product_images (product_id, image_path, is_primary) VALUES (?, ?, ?)";
                $stmt_image = $conn->prepare($sql_image);
                foreach ($new_images['name'] as $key => $image_name) {
                    $target_file = "images/" . basename($image_name);
                    if (move_uploaded_file($new_images['tmp_name'][$key], $target_file)) {
                        $is_primary = !$has_primary ? 1 : 0;
                        $stmt_image->bind_param("isi", $id, $image_name, $is_primary);
                        $stmt_image->execute();
                        $has_primary = true;
                    }
                }
            }

            $conn->commit(); // Commit the transaction

            // Check if it's an AJAX request
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

            if ($is_ajax) {
                // Fetch the updated product data to send back to the client
                $sql_updated = "SELECT p.*, pi.image_path AS primary_image, 
                                       GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS category_names,
                                       GROUP_CONCAT(DISTINCT c.id ORDER BY c.name) AS category_ids
                                FROM products p 
                                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                                LEFT JOIN product_categories pc ON p.id = pc.product_id
                                LEFT JOIN categories c ON pc.category_id = c.id
                                WHERE p.id = ?
                                GROUP BY p.id";
                $stmt_updated = $conn->prepare($sql_updated);
                $stmt_updated->bind_param("i", $id);
                $stmt_updated->execute();
                $updated_product_data = $stmt_updated->get_result()->fetch_assoc();

                // Also need the list of all images for this product for the image manager
                $sql_images_updated = "SELECT id, product_id, image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id";
                $stmt_images_updated = $conn->prepare($sql_images_updated);
                $stmt_images_updated->bind_param("i", $id);
                $stmt_images_updated->execute();
                $updated_images_data = $stmt_images_updated->get_result()->fetch_all(MYSQLI_ASSOC);

                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Product updated successfully.', 'product' => $updated_product_data, 'images' => $updated_images_data]);
                exit;
            } else {
                header("Location: edit_products.php");
                exit;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            if ($is_ajax) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => "Error updating product: " . $e->getMessage()]);
                exit;
            } else {
                echo "Error updating product: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_product'])) {
        $id = $_POST['id'];
        // Get image paths to delete files
        $sql_get_images = "SELECT image_path FROM product_images WHERE product_id = ?";
        $stmt_get_images = $conn->prepare($sql_get_images);
        $stmt_get_images->bind_param("i", $id);
        $stmt_get_images->execute();
        $images_result = $stmt_get_images->get_result();
        while ($img_row = $images_result->fetch_assoc()) {
            @unlink('images/' . $img_row['image_path']);
        }
        // Delete product from DB. ON DELETE CASCADE will handle product_images and product_categories.
        $sql = "DELETE FROM products WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error deleting product: " . $stmt->error;
        }
    }
}

// Fetch categories to use in forms and modals
$categories = [];
$sql_categories = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);
if ($categories_result) {
    while($cat_row = $categories_result->fetch_assoc()){
        $categories[] = $cat_row;
    }
}

// Fetch all images and group them by product_id for use in JavaScript
$all_images = [];
$sql_images = "SELECT id, product_id, image_path, is_primary FROM product_images ORDER BY product_id, is_primary DESC, id";
$images_result = $conn->query($sql_images);
if ($images_result) {
    while ($img_row = $images_result->fetch_assoc()) {
        $all_images[$img_row['product_id']][] = $img_row;
    }
}

// Fetch products with their category names and primary image
$sql = "SELECT p.*, pi.image_path AS primary_image, 
               GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS category_names,
               GROUP_CONCAT(DISTINCT c.id ORDER BY c.name) AS category_ids
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        LEFT JOIN product_categories pc ON p.id = pc.product_id
        LEFT JOIN categories c ON pc.category_id = c.id
        GROUP BY p.id ORDER BY p.id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-manager { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .image-manager-item { border: 1px solid var(--border-color); padding: 10px; border-radius: 8px; text-align: center; background: var(--bg); }
        .image-manager-item img { width: 100%; height: 80px; object-fit: cover; border-radius: 5px; margin-bottom: 10px; }
        .image-manager-item label { font-size: 0.8rem; display: block; margin-top: 5px; cursor: pointer; }
        .image-manager-item input { vertical-align: middle; }
        .checkbox-group {
            height: 120px; overflow-y: auto; border: 1px solid var(--border-color); padding: 10px; border-radius: 5px; background: #333;
        }
        .checkbox-group label {
            display: block;
            margin-bottom: 8px;
            cursor: pointer;
            font-weight: 400;
        }
        .checkbox-group input { margin-right: 8px; vertical-align: middle; }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="edit_products.php" class="active">Manage Products</a></li>
                <li><a href="admin_orders.php">Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header">
                <h3>Manage Products</h3>
            </div>

            <div class="card" style="margin-bottom: 30px;">
                <h3 style="text-align: left; margin-bottom: 20px;">Add New Product</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <input type="text" name="name" placeholder="Product Name" required style="padding: 10px;">
                        <input type="number" step="0.01" name="price" placeholder="Price" required style="padding: 10px;">
                        <div class="checkbox-group">
                            <?php if(empty($categories)): ?>
                                <p style="color: var(--text-muted);">Add a category first.</p>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                    <label>
                                        <input type="checkbox" name="category_ids[]" value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <textarea name="description" placeholder="Product Description" required style="width: 100%; padding: 10px; margin-top: 20px;"></textarea>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                        <input type="file" name="images[]" multiple required>
                        <button type="submit" name="add_product" class="btn-primary" style="margin-top: 0;">Add Product</button>
                    </div>
                </form>
            </div>

            <!-- Manage Categories Section -->
            <div class="card" style="margin-bottom: 30px;">
                <h3 style="text-align: left; margin-bottom: 20px;">Manage Categories</h3>
                <form action="edit_products.php" method="post" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="text" name="name" placeholder="New Category Name" required style="flex-grow: 1; padding: 10px;">
                    <button type="submit" name="add_category" class="btn-primary" style="margin-top: 0;">Add Category</button>
                </form>

                <div class="orders-table-container" style="max-height: 300px; overflow-y: auto;">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th style="width: 150px; text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="2">No categories found.</td></tr>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                <tr data-id="<?php echo $category['id']; ?>" data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                    <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    <td style="text-align: right;">
                                        <button class="edit-category-btn" style="background: var(--accent); color: var(--bg); border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer; margin-right: 5px;"><i class="fas fa-edit"></i></button>
                                        <form action="edit_products.php" method="post" onsubmit="return confirm('Are you sure you want to delete this category? Products in this category will become uncategorized.');" style="display: inline;">
                                            <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="delete-product-btn"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid">
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="card" 
                     data-id="<?php echo $row['id']; ?>"
                     data-name="<?php echo htmlspecialchars($row['name']); ?>"
                     data-description="<?php echo htmlspecialchars($row['description']); ?>"
                     data-price="<?php echo htmlspecialchars($row['price']); ?>"
                     data-category-ids="<?php echo htmlspecialchars($row['category_ids']); ?>">
                    <img src="images/<?php echo htmlspecialchars($row['primary_image'] ?? 'placeholder.png'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                    <h3 class="product-name"><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p class="product-categories" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 10px;"><?php echo htmlspecialchars($row['category_names'] ?? 'Uncategorized'); ?></p>
                    <p class="product-description"><?php echo htmlspecialchars($row['description']); ?></p>
                    <p class="product-price" style="font-size: 1.2rem; font-weight: 600; color: var(--accent); margin-top: 10px;">₱<?php echo htmlspecialchars($row['price']); ?></p>
                    <div class="admin-product-actions">
                        <button class="edit-product-btn"><i class="fas fa-edit"></i> Edit</button>
                        <form action="edit_products.php" method="post" onsubmit="return confirm('Are you sure you want to delete this product?');" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="delete_product" class="delete-product-btn"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="edit-product-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Product</h2>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <form action="edit_products.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit-product-id">
                    <div style="margin-bottom: 20px;">
                        <label for="edit-product-name" style="display: block; margin-bottom: 5px; font-weight: 600;">Product Name</label>
                        <input type="text" id="edit-product-name" name="name" required style="width: 100%; padding: 10px;">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <label for="edit-product-price" style="display: block; margin-bottom: 5px; font-weight: 600;">Price</label>
                            <input type="number" step="0.01" id="edit-product-price" name="price" required style="width: 100%; padding: 10px;">
                        </div>
                        <div>
                            <label for="edit-product-category" style="display: block; margin-bottom: 5px; font-weight: 600;">Category</label>
                            <div id="edit-product-category" class="checkbox-group">
                                <?php if(empty($categories)): ?>
                                    <p style="color: var(--text-muted);">No categories available.</p>
                                <?php else: ?>
                                    <?php foreach($categories as $category): ?>
                                        <label><input type="checkbox" name="category_ids[]" value="<?php echo $category['id']; ?>"> <?php echo htmlspecialchars($category['name']); ?></label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label for="edit-product-description" style="display: block; margin-bottom: 5px; font-weight: 600;">Description</label>
                        <textarea id="edit-product-description" name="description" required style="width: 100%; padding: 10px; min-height: 100px;"></textarea>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 10px; font-weight: 600;">Manage Images</label>
                        <div id="edit-product-image-manager" class="image-manager"></div>
                        
                        <label for="edit-product-new-images" style="display: block; margin-top: 20px; margin-bottom: 5px; font-weight: 600;">Upload New Images</label>
                        <input type="file" id="edit-product-new-images" name="new_images[]" multiple>
                    </div>
                    <button type="submit" name="update_product" class="btn-primary" style="width: 100%;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="edit-category-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Edit Category</h2>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <form action="edit_products.php" method="post">
                    <input type="hidden" name="id" id="edit-category-id">
                    <div style="margin-bottom: 20px;">
                        <label for="edit-category-name" style="display: block; margin-bottom: 5px; font-weight: 600;">Category Name</label>
                        <input type="text" id="edit-category-name" name="name" required style="width: 100%; padding: 10px;">
                    </div>
                    <button type="submit" name="update_category" class="btn-primary" style="width: 100%;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const allProductImages = <?php echo json_encode($all_images); ?>;
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const productModal = document.getElementById('edit-product-modal');
        const closeProductBtn = productModal.querySelector('.close-btn');
        const editButtons = document.querySelectorAll('.edit-product-btn');
        const editForm = productModal.querySelector('form');
        const imageManagerContainer = document.getElementById('edit-product-image-manager');

        editButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const card = e.target.closest('.card');
                const productId = card.dataset.id;

                // Populate basic form fields
                productModal.querySelector('#edit-product-id').value = productId;
                productModal.querySelector('#edit-product-name').value = card.dataset.name;
                productModal.querySelector('#edit-product-description').value = card.dataset.description;
                productModal.querySelector('#edit-product-price').value = card.dataset.price;
                productModal.querySelector('#edit-product-new-images').value = '';

                // Populate category checkboxes
                const categoryContainer = productModal.querySelector('#edit-product-category');
                const categoryIds = card.dataset.categoryIds ? card.dataset.categoryIds.split(',') : [];
                const checkboxes = categoryContainer.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => { checkbox.checked = categoryIds.includes(checkbox.value); });

                // Populate image manager (from previous implementation)
                imageManagerContainer.innerHTML = '';
                const productImages = allProductImages[productId] || [];
                if (productImages.length > 0) {
                    productImages.forEach(image => {
                        const isChecked = image.is_primary == 1 ? 'checked' : '';
                        const itemHTML = `
                            <div class="image-manager-item">
                                <img src="images/${image.image_path}" alt="Product Image">
                                <label><input type="radio" name="primary_image_id" value="${image.id}" ${isChecked}> Primary</label>
                                <label><input type="checkbox" name="delete_images[]" value="${image.id}"> Delete</label>
                            </div>`;
                        imageManagerContainer.insertAdjacentHTML('beforeend', itemHTML);
                    });
                } else {
                    imageManagerContainer.innerHTML = '<p style="color: var(--text-muted); font-size: 0.9rem;">No images for this product.</p>';
                }

                productModal.style.display = 'block';
            });
        });

        editForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(editForm);
            // Manually add the 'update_product' value, as it's not included by FormData on submit.
            formData.append('update_product', '1');

            const submitButton = editForm.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';

            try {
                const response = await fetch('edit_products.php', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || `Server error: ${response.status}`);
                }

                if (result.success) {
                    const product = result.product;
                    const images = result.images;
                    const productId = product.id;

                    // Update the global JS variable for images
                    allProductImages[productId] = images;

                    // Find the card on the page and update it
                    const card = document.querySelector(`.card[data-id='${productId}']`);
                    if (card) {
                        card.dataset.name = product.name;
                        card.dataset.description = product.description;
                        card.dataset.price = product.price;
                        card.dataset.categoryIds = product.category_ids || '';

                        const primaryImage = product.primary_image || 'placeholder.png';
                        card.querySelector('.product-image').src = `images/${primaryImage}`;
                        card.querySelector('.product-image').alt = product.name;
                        card.querySelector('.product-name').textContent = product.name;
                        card.querySelector('.product-categories').textContent = product.category_names || 'Uncategorized';
                        card.querySelector('.product-description').textContent = product.description;
                        card.querySelector('.product-price').textContent = `₱${product.price}`;
                    }

                    productModal.style.display = 'none';
                    // You could replace this with a more elegant toast notification
                    alert(result.message);
                } else {
                    alert('Error: ' + result.message);
                }

            } catch (error) {
                console.error('Fetch error:', error);
                alert('An error occurred while saving: ' + error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Save Changes';
            }
        });

        closeProductBtn.addEventListener('click', () => productModal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === productModal) productModal.style.display = 'none'; });

        // Category Modal
        const categoryModal = document.getElementById('edit-category-modal');
        const closeCategoryBtn = categoryModal.querySelector('.close-btn');
        document.querySelectorAll('.edit-category-btn').forEach(button => {
            button.addEventListener('click', (e) => {
                const row = e.target.closest('tr');
                categoryModal.querySelector('#edit-category-id').value = row.dataset.id;
                categoryModal.querySelector('#edit-category-name').value = row.dataset.name;
                categoryModal.style.display = 'block';
            });
        });
        closeCategoryBtn.addEventListener('click', () => categoryModal.style.display = 'none');
        window.addEventListener('click', (e) => { if (e.target === categoryModal) categoryModal.style.display = 'none'; });
    });
    </script>
</body>
</html>