<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// Handle POST requests for adding/deleting users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- ADD USER ---
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $role = $_POST['role'];

        if (empty($username) || empty($email) || empty($password)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Please fill in all required fields.'];
        } elseif ($password !== $confirm_password) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Passwords do not match.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid email format.'];
        } elseif (!in_array($role, ['user', 'admin'])) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid role selected.'];
        } else {
            // Check for existing username or email
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Username or email is already taken.'];
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $role);
                if ($stmt_insert->execute()) {
                    $_SESSION['message'] = ['type' => 'success', 'text' => 'User added successfully.'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to add user.'];
                }
            }
        }
        header("Location: admin_users.php");
        exit;
    }
    // --- DELETE USER ---
    elseif (isset($_POST['delete_user'])) {
        $user_id_to_delete = (int)$_POST['user_id'];
        $current_user_id = (int)$_SESSION['user_id'];

        if ($user_id_to_delete === $current_user_id) {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'You cannot delete your own account.'];
        } else {
            $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt_delete->bind_param("i", $user_id_to_delete);
            if ($stmt_delete->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User deleted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete user.'];
            }
        }
        header("Location: admin_users.php");
        exit;
    }
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
                <div>
                    <button id="add-user-btn" class="btn-secondary" style="padding: 10px 20px; font-size: 0.9rem; margin-right: 10px; cursor: pointer;"><i class="fas fa-user-plus"></i> Add User</button>
                    <a href="download_users.php" class="btn-primary" style="padding: 10px 20px; font-size: 0.9rem; text-decoration: none;"><i class="fas fa-file-pdf"></i> Download User List (PDF)</a>
                </div>
            </div>

            <?php
            if (isset($_SESSION['message'])) {
                $message = $_SESSION['message'];
                unset($_SESSION['message']); // Clear the message after displaying
                if ($message['type'] === 'error') {
                    echo '<div class="error-message" style="margin-bottom: 20px;">' . htmlspecialchars($message['text']) . '</div>';
                } else {
                    echo '<div style="color: #28a745; background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); text-align: center; margin-bottom: 20px; padding: 10px; border-radius: 5px;">' . htmlspecialchars($message['text']) . '</div>';
                }
            }
            ?>

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
                                <td style="display: flex; gap: 10px; align-items: center;">
                                    <button class="user-purchases-toggle" data-user-id="<?php echo $user['id']; ?>">View Purchases</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form action="admin_users.php" method="post" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="delete-product-btn" title="Delete User"><i class="fas fa-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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

    <!-- Add User Modal -->
    <div id="add-user-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2>Add New User</h2>
                <span class="close-btn">&times;</span>
            </div>
            <div class="modal-body">
                <form action="admin_users.php" method="post">
                    <div class="form-group">
                        <label for="add-username">Username</label>
                        <input type="text" id="add-username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add-email">Email Address</label>
                        <input type="email" id="add-email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add-password">Password</label>
                        <input type="password" id="add-password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add-confirm-password">Confirm Password</label>
                        <input type="password" id="add-confirm-password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="add-role">Role</label>
                        <select id="add-role" name="role" class="form-control" required>
                            <option value="user" selected>User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="btn-primary" style="width: 100%;">Add User</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // --- Modal Handling for Add User ---
            const addUserModal = document.getElementById('add-user-modal');
            const addUserBtn = document.getElementById('add-user-btn');
            const closeBtn = addUserModal.querySelector('.close-btn');

            addUserBtn.addEventListener('click', () => addUserModal.style.display = 'block');
            closeBtn.addEventListener('click', () => addUserModal.style.display = 'none');
            window.addEventListener('click', (event) => { if (event.target == addUserModal) { addUserModal.style.display = 'none'; } });


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