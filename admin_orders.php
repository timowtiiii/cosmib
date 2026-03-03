<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// Fetch all orders
$orders_sql = "SELECT * FROM orders ORDER BY order_date DESC";
$orders_result = $conn->query($orders_sql);

// Fetch all order items and group them by order_id
$items_sql = "SELECT oi.*, p.name as product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id ORDER BY oi.order_id";
$items_result = $conn->query($items_sql);
$order_items = [];
if ($items_result) {
    while($item = $items_result->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .orders-table th, .orders-table td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
        .orders-table th { background-color: var(--card-bg); }
        .orders-table tr:hover { background-color: #333; }
        .order-items-list { list-style: none; padding-left: 0; margin-top: 10px; }
        .order-items-list li { font-size: 0.9em; color: var(--text-muted); }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="edit_products.php">Manage Products</a></li>
                <li><a href="admin_orders.php" class="active">Orders</a></li>
                <li><a href="#">Users</a></li>
                <li><a href="logout.php">Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header"><h3>Manage Orders</h3></div>
            <div class="card">
                <table class="orders-table">
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Address</th><th>Total</th><th>Date</th><th>Items</th></tr></thead>
                    <tbody>
                        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                            <?php while($order = $orders_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?><br><small><?php echo htmlspecialchars($order['customer_email']); ?></small></td>
                                    <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <?php if (isset($order_items[$order['id']])): ?>
                                            <ul class="order-items-list">
                                                <?php foreach($order_items[$order['id']] as $item): ?>
                                                    <li><?php echo $item['quantity']; ?> x <?php echo htmlspecialchars($item['product_name'] ?? 'Product not found'); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center;">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>