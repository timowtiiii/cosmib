<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// Fetch dashboard data
$total_products_sql = "SELECT COUNT(*) AS total_products FROM products";
$total_products_result = $conn->query($total_products_sql);
$total_products = $total_products_result->fetch_assoc()['total_products'];

$total_orders_sql = "SELECT COUNT(*) AS total_orders FROM orders";
$total_orders_result = $conn->query($total_orders_sql);
$total_orders = $total_orders_result->fetch_assoc()['total_orders'];

$total_users_sql = "SELECT COUNT(*) AS total_users FROM users";
$total_users_result = $conn->query($total_users_sql);
$total_users = $total_users_result->fetch_assoc()['total_users'];

// Fetch recent orders
$recent_orders_sql = "SELECT id, customer_name, total_price, order_date FROM orders ORDER BY order_date DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);
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
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="edit_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header">
                <h3><i class="fas fa-tachometer-alt"></i> Dashboard</h3>
                <div class="admin-user-info">Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>!</div>
            </div>

            <div class="admin-widgets">
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-box"></i></div>
                    <div class="widget-info">
                        <h4>Total Products</h4>
                        <p><?php echo $total_products; ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-receipt"></i></div>
                    <div class="widget-info">
                        <h4>Total Orders</h4>
                        <p><?php echo $total_orders; ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-users"></i></div>
                    <div class="widget-info">
                        <h4>Total Users</h4>
                        <p><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>

            <div class="admin-section">
                <h3>Recent Orders</h3>
                <?php if ($recent_orders_result->num_rows > 0): ?>
                    <ul class="activity-list">
                        <?php while($order = $recent_orders_result->fetch_assoc()): ?>
                            <li>
                                <i class="fas fa-shopping-cart"></i>
                                Order #<?php echo htmlspecialchars($order['id']); ?> by <?php echo htmlspecialchars($order['customer_name']); ?> for ₱<?php echo number_format($order['total_price'], 2); ?> on <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p>No recent orders.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>