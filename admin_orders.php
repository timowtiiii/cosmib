<?php
session_start();
include 'db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: admin_login.php");
    exit;
}

// Handle delete order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $order_id_to_delete = (int)$_POST['order_id'];

    $conn->begin_transaction();
    try {
        // 1. Get items from the order to restore stock
        $sql_get_items = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
        $stmt_get_items = $conn->prepare($sql_get_items);
        $stmt_get_items->bind_param("i", $order_id_to_delete);
        $stmt_get_items->execute();
        $items_result = $stmt_get_items->get_result();
        $items_to_restore = [];
        while ($item = $items_result->fetch_assoc()) {
            $items_to_restore[] = $item;
        }
        $stmt_get_items->close();

        // 2. Restore stock for each product
        if (!empty($items_to_restore)) {
            $sql_update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
            $stmt_update_stock = $conn->prepare($sql_update_stock);
            foreach ($items_to_restore as $item) {
                $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
                $stmt_update_stock->execute();
            }
            $stmt_update_stock->close();
        }

        // 3. Delete from order_items and then the order itself
        $stmt_del_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_del_items->bind_param("i", $order_id_to_delete);
        $stmt_del_items->execute();
        $stmt_del_items->close();

        $stmt_del_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt_del_order->bind_param("i", $order_id_to_delete);
        $stmt_del_order->execute();
        $stmt_del_order->close();

        $conn->commit();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Order #' . $order_id_to_delete . ' has been deleted and stock restored.'];
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Error deleting order: ' . $e->getMessage()];
    }

    header("Location: admin_orders.php");
    exit;
}

// --- Fetch data for filters ---

// Fetch categories for filter dropdown
$categories = [];
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$categories_result = $conn->query($sql_categories);
if ($categories_result) {
    while ($cat_row = $categories_result->fetch_assoc()) {
        $categories[] = $cat_row;
    }
}

// Fetch products for filter dropdown
$products = [];
$sql_products = "SELECT id, name FROM products ORDER BY name ASC";
$products_result = $conn->query($sql_products);
if ($products_result) {
    while ($prod_row = $products_result->fetch_assoc()) {
        $products[] = $prod_row;
    }
}

// --- Handle filter inputs ---
$filter_category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? (int)$_GET['category_id'] : 0;
$filter_product_id = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : 0;

// --- Build and execute the main SQL query for orders ---
$sql = "SELECT DISTINCT o.*, u.username as registered_username
        FROM orders o
        LEFT JOIN users u ON o.customer_email = u.email"; // Join users to get username if customer is registered

$where_clauses = [];
$params = [];
$param_types = "";

// Use EXISTS for efficient filtering
if ($filter_category_id > 0) {
    $where_clauses[] = "EXISTS (
        SELECT 1 FROM order_items oi
        JOIN product_categories pc ON oi.product_id = pc.product_id
        WHERE oi.order_id = o.id AND pc.category_id = ?
    )";
    $params[] = $filter_category_id;
    $param_types .= "i";
}

if ($filter_product_id > 0) {
    $where_clauses[] = "EXISTS (
        SELECT 1 FROM order_items oi
        WHERE oi.order_id = o.id AND oi.product_id = ?
    )";
    $params[] = $filter_product_id;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY o.order_date DESC, o.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$orders_result = $stmt->get_result();

// --- Fetch order items for all displayed orders in a single query for efficiency ---
$order_ids = [];
$orders_data = [];
while ($order = $orders_result->fetch_assoc()) {
    $order_ids[] = $order['id'];
    $orders_data[] = $order;
}

$order_items = [];
if (!empty($order_ids)) {
    $order_ids_placeholder = implode(',', array_fill(0, count($order_ids), '?'));
    $sql_items = "SELECT oi.*, p.name as product_name 
                  FROM order_items oi 
                  JOIN products p ON oi.product_id = p.id 
                  WHERE oi.order_id IN ($order_ids_placeholder)";
    $stmt_items = $conn->prepare($sql_items);
    $types = str_repeat('i', count($order_ids));
    $stmt_items->bind_param($types, ...$order_ids);
    $stmt_items->execute();
    $items_result = $stmt_items->get_result();
    while ($item = $items_result->fetch_assoc()) {
        $order_items[$item['order_id']][] = $item;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="edit_products.php"><i class="fas fa-box-open"></i> Manage Products</a></li>
                <li><a href="admin_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="admin_users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="index.php" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sign Out</a></li>
            </ul>
        </div>
        <div class="admin-main">
            <div class="admin-header">
                <h3>Manage Orders</h3>
            </div>

            <?php
            if (isset($_SESSION['message'])) {
                $message = $_SESSION['message'];
                unset($_SESSION['message']); // Clear the message after displaying
                if ($message['type'] === 'error') {
                    // Use existing error-message class from style.css
                    echo '<div class="error-message" style="margin-bottom: 20px;">' . htmlspecialchars($message['text']) . '</div>';
                } else {
                    // Use inline style for success message
                    echo '<div style="color: #28a745; background: rgba(40, 167, 69, 0.1); border: 1px solid rgba(40, 167, 69, 0.3); text-align: center; margin-bottom: 20px; padding: 10px; border-radius: 5px;">' . htmlspecialchars($message['text']) . '</div>';
                }
            }
            ?>

            <!-- Filter Form -->
            <div class="card" style="margin-bottom: 30px; padding: 25px;">
                <form action="admin_orders.php" method="get" id="filter-form">
                    <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 20px; align-items: end;">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="filter-category">Filter by Category</label>
                            <select name="category_id" id="filter-category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php if ($filter_category_id == $category['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="filter-product">Filter by Product</label>
                            <select name="product_id" id="filter-product" class="form-control">
                                <option value="">All Products</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?php echo $product['id']; ?>" <?php if ($filter_product_id == $product['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top: 0; padding: 10px 25px;">Filter</button>
                        <a href="admin_orders.php" class="btn-secondary" style="margin-top: 0; padding: 10px 25px;">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-section">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Address</th>
                            <th>Total Price</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders_data)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 20px;">No orders found matching your criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders_data as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                        <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($order['customer_address']); ?></td>
                                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?></td>
                                    <td style="white-space: nowrap;">
                                        <button class="order-details-toggle" data-order-id="<?php echo $order['id']; ?>">View Items</button>
                                        <form action="admin_orders.php" method="post" onsubmit="return confirm('Are you sure you want to delete this order? This will also restore the stock for the items in this order. This action cannot be undone.');" style="display: inline; margin-left: 10px;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="delete_order" class="delete-product-btn" title="Delete Order" style="padding: 8px 12px; font-size: 0.9rem; vertical-align: middle;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="order-items-row" id="order-items-<?php echo $order['id']; ?>">
                                    <td colspan="6">
                                        <div style="padding: 15px;">
                                            <h4>Items for Order #<?php echo $order['id']; ?></h4>
                                            <table class="order-items-table">
                                                <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead>
                                                <tbody>
                                                <?php if (isset($order_items[$order['id']])): ?>
                                                    <?php foreach ($order_items[$order['id']] as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                            <td><?php echo $item['quantity']; ?></td>
                                                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                                            <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.order-details-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const orderId = this.dataset.orderId;
                const itemsRow = document.getElementById('order-items-' + orderId);
                if (itemsRow) {
                    const isVisible = itemsRow.style.display === 'table-row';
                    itemsRow.style.display = isVisible ? 'none' : 'table-row';
                    this.textContent = isVisible ? 'View Items' : 'Hide Items';
                }
            });
        });
    });
    </script>
</body>
</html>