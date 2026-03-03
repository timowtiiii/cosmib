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
        if ($stmt->execute()) {
            move_uploaded_file($_FILES['image']['tmp_name'], $target);
            // Redirect to prevent form resubmission on refresh
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error adding product: " . $stmt->error;
        }
    } elseif(isset($_POST['update_product'])){
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $new_image = $_FILES['new_image']['name'];

        if(!empty($new_image)){
            $target = "images/".basename($new_image);
            $sql = "UPDATE products SET name=?, description=?, price=?, image=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsi", $name, $description, $price, $new_image, $id);
            if ($stmt->execute()) {
                move_uploaded_file($_FILES['new_image']['tmp_name'], $target);
                // Redirect to prevent form resubmission on refresh
                header("Location: edit_products.php");
                exit;
            } else {
                echo "Error updating product with new image: " . $stmt->error;
            }
        } else {
            $sql = "UPDATE products SET name=?, description=?, price=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdi", $name, $description, $price, $id);
        }
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission on refresh
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error updating product: " . $stmt->error;
        }
    } elseif(isset($_POST['delete_product'])){
        $id = $_POST['id'];
        $sql = "DELETE FROM products WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Redirect to prevent form resubmission on refresh
            header("Location: edit_products.php");
            exit;
        } else {
            echo "Error deleting product: " . $stmt->error;
        }
    }
}

$sql = "SELECT * FROM products";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" type="text/css" href="style.css">
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
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="card" data-id="<?php echo $row['id']; ?>">
                    <img src="images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="product-image">
                    <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    <p style="font-size: 1.2rem; font-weight: 600; color: var(--accent); margin-top: 10px;">₱<?php echo htmlspecialchars($row['price']); ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>