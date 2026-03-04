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

// --- Sales Data Fetching ---

// Total Sales Today
$today_sales_sql = "SELECT SUM(total_price) AS sales_today FROM orders WHERE DATE(order_date) = CURDATE()";
$today_sales_result = $conn->query($today_sales_sql);
$sales_today = $today_sales_result->fetch_assoc()['sales_today'] ?? 0;

// Total Sales This Month
$monthly_sales_sql = "SELECT SUM(total_price) AS sales_month FROM orders WHERE MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())";
$monthly_sales_result = $conn->query($monthly_sales_sql);
$sales_month = $monthly_sales_result->fetch_assoc()['sales_month'] ?? 0;

// Overall Total Sales
$overall_sales_sql = "SELECT SUM(total_price) AS sales_overall FROM orders";
$overall_sales_result = $conn->query($overall_sales_sql);
$sales_overall = $overall_sales_result->fetch_assoc()['sales_overall'] ?? 0;

// Ensure all sales figures are floats for number_format
$sales_today = (float)$sales_today;
$sales_month = (float)$sales_month;
$sales_overall = (float)$sales_overall;

// --- Chart Data Fetching ---

// 1. Sales over time (last 30 days)
$sales_over_time_sql = "
    SELECT DATE(order_date) AS sale_date, SUM(total_price) AS daily_sales
    FROM orders
    WHERE order_date >= CURDATE() - INTERVAL 30 DAY
    GROUP BY DATE(order_date)
    ORDER BY sale_date ASC
";
$sales_over_time_result = $conn->query($sales_over_time_sql);
$sales_labels = [];
$sales_data = [];
while($row = $sales_over_time_result->fetch_assoc()){
    $sales_labels[] = $row['sale_date'];
    $sales_data[] = $row['daily_sales'];
}

// 2. Best-selling items (top 10 by quantity)
$product_sales_sql = "SELECT p.name, SUM(oi.quantity) AS total_quantity_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.name ORDER BY total_quantity_sold DESC LIMIT 10";
$product_sales_result = $conn->query($product_sales_sql);
$product_labels = [];
$product_data = [];
while($row = $product_sales_result->fetch_assoc()){
    $product_labels[] = $row['name'];
    $product_data[] = $row['total_quantity_sold'];
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="widget-info">
                        <h4>Sales Today</h4>
                        <p>₱<?php echo number_format($sales_today, 2); ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="widget-info">
                        <h4>Sales This Month</h4>
                        <p>₱<?php echo number_format($sales_month, 2); ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="widget-info">
                        <h4>Overall Sales</h4>
                        <p>₱<?php echo number_format($sales_overall, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="admin-charts">
                <div class="chart-container card">
                    <h3>Sales Over Time (Last 30 Days)</h3>
                    <canvas id="salesOverTimeChart"></canvas>
                </div>
                <div class="chart-container card">
                    <h3>Top 10 Best-Selling Items</h3>
                    <canvas id="bestSellingItemsChart"></canvas>
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

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Chart Data from PHP ---
        const salesLabels = <?php echo json_encode($sales_labels); ?>;
        const salesData = <?php echo json_encode($sales_data); ?>;
        const productLabels = <?php echo json_encode($product_labels); ?>;
        const productData = <?php echo json_encode($product_data); ?>;

        const chartTextColor = '#a0a0a0';
        const chartGridColor = '#444';
        const chartAccentColor = '#ffaf00';

        // --- Chart 1: Sales Over Time ---
        const salesCtx = document.getElementById('salesOverTimeChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: salesLabels,
                datasets: [{
                    label: 'Daily Sales (₱)',
                    data: salesData,
                    backgroundColor: 'rgba(255, 175, 0, 0.2)',
                    borderColor: chartAccentColor,
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                aspectRatio: 2.5,
                scales: {
                    y: { beginAtZero: true, ticks: { color: chartTextColor }, grid: { color: chartGridColor } },
                    x: { ticks: { color: chartTextColor }, grid: { color: chartGridColor } }
                },
                plugins: { legend: { labels: { color: chartTextColor } } }
            }
        });

        // --- Chart 2: Best-Selling Items ---
        const productsCtx = document.getElementById('bestSellingItemsChart').getContext('2d');
        new Chart(productsCtx, {
            type: 'bar',
            data: {
                labels: productLabels,
                datasets: [{
                    label: 'Quantity Sold',
                    data: productData,
                    backgroundColor: 'rgba(255, 175, 0, 0.5)',
                    borderColor: chartAccentColor,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                aspectRatio: 2.5,
                scales: {
                    y: { ticks: { color: chartTextColor }, grid: { color: chartGridColor } },
                    x: { ticks: { color: chartTextColor }, grid: { color: chartGridColor } }
                },
                plugins: {
                    legend: { display: false } // Hide legend for a cleaner look
                }
            }
        });
    });
    </script>
</body>
</html>