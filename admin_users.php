<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// Fetch all users
$users_sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$users_result = $conn->query($users_sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | COSMI BEAUTII Admin</title>
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
                <li><a href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a></li>
                <li><a href="admin_users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header">
                <h3><i class="fas fa-users"></i> Manage Users</h3>
            </div>

            <?php if ($users_result->num_rows > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users_result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($user['created_at'])); ?></td>
                                <td><button class="user-purchases-toggle" data-user-id="<?php echo $user['id']; ?>">View Purchases</button></td>
                            </tr>
                            <tr class="user-purchases-row" id="user-purchases-<?php echo $user['id']; ?>">
                                <td colspan="6">
                                    <?php
                                    // Fetch orders for this user by email
                                    $user_orders_sql = "SELECT * FROM orders WHERE customer_email = ? ORDER BY order_date DESC";
                                    $user_orders_stmt = $conn->prepare($user_orders_sql);
                                    $user_orders_stmt->bind_param("s", $user['email']);
                                    $user_orders_stmt->execute();
                                    $user_orders_result = $user_orders_stmt->get_result();
                                    ?>
                                    <?php if ($user_orders_result->num_rows > 0): ?>
                                        <table class="order-items-table">
                                            <thead>
                                                <tr>
                                                    <th>Order ID</th>
                                                    <th>Order Date</th>
                                                    <th>Total Price</th>
                                                    <th>Address</th>
                                                    <th>Items</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while($user_order = $user_orders_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td>#<?php echo htmlspecialchars($user_order['id']); ?></td>
                                                        <td><?php echo date('M d, Y', strtotime($user_order['order_date'])); ?></td>
                                                        <td>₱<?php echo number_format($user_order['total_price'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($user_order['customer_address']); ?></td>
                                                        <td><button class="order-details-toggle" data-order-id="<?php echo $user_order['id']; ?>">View Items</button></td>
                                                    </tr>
                                                    <tr class="order-items-row" id="order-items-<?php echo $user_order['id']; ?>">
                                                        <td colspan="5">
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
                                                                    $items_stmt->bind_param("i", $user_order['id']);
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
                                        <p style="padding: 15px; text-align: center;">This user has no purchase history.</p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No users found.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('.admin-main').addEventListener('click', function(e) {
                // Toggle user purchases
                if (e.target && e.target.classList.contains('user-purchases-toggle')) {
                    const userId = e.target.dataset.userId;
                    const purchasesRow = document.getElementById(`user-purchases-${userId}`);
                    if (purchasesRow) {
                        if (purchasesRow.style.display === 'none' || purchasesRow.style.display === '') {
                            purchasesRow.style.display = 'table-row';
                            e.target.textContent = 'Hide Purchases';
                        } else {
                            purchasesRow.style.display = 'none';
                            e.target.textContent = 'View Purchases';
                        }
                    }
                }

                // Toggle order items within user purchases
                if (e.target && e.target.classList.contains('order-details-toggle')) {
                    const orderId = e.target.dataset.orderId;
                    const itemsRow = document.getElementById(`order-items-${orderId}`);
                    if (itemsRow) {
                        if (itemsRow.style.display === 'none' || itemsRow.style.display === '') {
                            itemsRow.style.display = 'table-row';
                            e.target.textContent = 'Hide Items';
                        } else {
                            itemsRow.style.display = 'none';
                            e.target.textContent = 'View Items';
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>