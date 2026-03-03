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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders | COSMI BEAUTII Admin</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="edit_products.php"><i class="fas fa-box"></i> Manage Products</a></li>
                <li><a href="admin_orders.php" class="active"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header">
                <h3><i class="fas fa-receipt"></i> Manage Orders</h3>
            </div>

            <?php if ($orders_result->num_rows > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders_result->fetch_assoc()): ?>
                            <tr data-order-id="<?php echo $order['id']; ?>">
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                                <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td><button class="order-details-toggle" data-order-id="<?php echo $order['id']; ?>">View Items</button></td>
                            </tr>
                            <tr class="order-items-row" id="order-items-<?php echo $order['id']; ?>">
                                <td colspan="7">
                                    <table class="order-items-table">
                                        <thead>
                                            <tr>
                                                <th>Product Name</th>
                                                <th>Quantity</th>
                                                <th>Price per Item</th>
                                                <th>Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Fetch items for this order
                                            $items_sql = "SELECT oi.quantity, oi.price, p.name AS product_name
                                                          FROM order_items oi
                                                          JOIN products p ON oi.product_id = p.id
                                                          WHERE oi.order_id = ?";
                                            $items_stmt = $conn->prepare($items_sql);
                                            $items_stmt->bind_param("i", $order['id']);
                                            $items_stmt->execute();
                                            $items_result = $items_stmt->get_result();

                                            if ($items_result->num_rows > 0):
                                                while($item = $items_result->fetch_assoc()):
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                    <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; else: ?>
                                                <tr><td colspan="4">No items found for this order.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders have been placed yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.order-details-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const orderId = this.dataset.orderId;
                    const itemsRow = document.getElementById(`order-items-${orderId}`);
                    if (itemsRow) {
                        if (itemsRow.style.display === 'none' || itemsRow.style.display === '') {
                            itemsRow.style.display = 'table-row';
                            this.textContent = 'Hide Items';
                        } else {
                            itemsRow.style.display = 'none';
                            this.textContent = 'View Items';
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>