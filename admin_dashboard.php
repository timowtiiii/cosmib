<?php
session_start();
include 'db.php';

if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("location: admin_login.php");
    exit;
}

// --- Date Filter Logic ---
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-29 days'));
$end_date_for_query = $end_date . ' 23:59:59';

// --- WIDGET DATA ---

// Static Widgets (not affected by date filter)
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$total_users = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// Filtered Widgets
$stmt_widgets = $conn->prepare("
    SELECT
        SUM(total_price) AS total_revenue,
        COUNT(id) AS total_orders
    FROM orders
    WHERE order_date >= ? AND order_date <= ?
");
$stmt_widgets->bind_param("ss", $start_date, $end_date_for_query);
$stmt_widgets->execute();
$widget_data = $stmt_widgets->get_result()->fetch_assoc();

$total_revenue = (float)($widget_data['total_revenue'] ?? 0);
$total_orders = (int)($widget_data['total_orders'] ?? 0);
$avg_order_value = ($total_orders > 0) ? $total_revenue / $total_orders : 0;

$stmt_items = $conn->prepare("
    SELECT SUM(oi.quantity) AS items_sold
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_date >= ? AND o.order_date <= ?
");
$stmt_items->bind_param("ss", $start_date, $end_date_for_query);
$stmt_items->execute();
$items_sold = (int)($stmt_items->get_result()->fetch_assoc()['items_sold'] ?? 0);

// Fetch recent orders (within the filtered date range)
$recent_orders_sql = "SELECT id, customer_name, total_price, order_date FROM orders WHERE order_date >= ? AND order_date <= ? ORDER BY order_date DESC LIMIT 5";
$stmt_recent = $conn->prepare($recent_orders_sql);
$stmt_recent->bind_param("ss", $start_date, $end_date_for_query);
$stmt_recent->execute();
$recent_orders_result = $stmt_recent->get_result();

// --- Chart Data Fetching ---

// 1. Sales over time (last 30 days)
$sales_over_time_sql = "
    SELECT DATE(order_date) AS sale_date, SUM(total_price) AS daily_sales
    FROM orders
    WHERE order_date >= ? AND order_date <= ?
    GROUP BY DATE(order_date)
    ORDER BY sale_date ASC
";
$stmt_sales_chart = $conn->prepare($sales_over_time_sql);
$stmt_sales_chart->bind_param("ss", $start_date, $end_date_for_query);
$stmt_sales_chart->execute();
$sales_over_time_result = $stmt_sales_chart->get_result();
$sales_labels = [];
$sales_data = [];
while($row = $sales_over_time_result->fetch_assoc()){
    $sales_labels[] = $row['sale_date'];
    $sales_data[] = $row['daily_sales'];
}

// 2. Best-selling items (top 10 by quantity)
$product_sales_sql = "SELECT p.name, SUM(oi.quantity) AS total_quantity_sold
                      FROM order_items oi
                      JOIN products p ON oi.product_id = p.id
                      JOIN orders o ON oi.order_id = o.id
                      WHERE o.order_date >= ? AND o.order_date <= ?
                      GROUP BY p.name ORDER BY total_quantity_sold DESC LIMIT 10";
$stmt_product_chart = $conn->prepare($product_sales_sql);
$stmt_product_chart->bind_param("ss", $start_date, $end_date_for_query);
$stmt_product_chart->execute();
$product_sales_result = $stmt_product_chart->get_result();
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
    <!-- Date Range Picker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

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
                <div style="display: flex; align-items: center; gap: 20px; flex-wrap: wrap;">
                    <form id="date-filter-form" method="GET" action="admin_dashboard.php" style="display: flex; align-items: center; gap: 10px; background: var(--card-bg); padding: 10px; border-radius: 8px; border: 1px solid var(--border-color);">
                        <div id="daterange" style="background: var(--bg); cursor: pointer; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 5px; color: var(--text); display: flex; align-items: center; gap: 8px; width: 280px;">
                            <i class="fas fa-calendar-alt"></i>
                            <span></span>
                            <i class="fas fa-caret-down" style="margin-left: auto;"></i>
                        </div>
                        <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                        <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                        <a href="admin_dashboard.php" class="btn-secondary" style="margin-top:0; padding: 8px 15px; text-decoration: none;" title="Reset Dates"><i class="fas fa-sync-alt"></i></a>
                    </form>
                    <div class="admin-user-info">Welcome, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>!</div>
                </div>
            </div>

            <div class="admin-widgets">
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="widget-info">
                        <h4>Total Revenue</h4>
                        <p>₱<?php echo number_format($total_revenue, 2); ?></p>
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
                    <div class="widget-icon"><i class="fas fa-shopping-basket"></i></div>
                    <div class="widget-info">
                        <h4>Items Sold</h4>
                        <p><?php echo number_format($items_sold); ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="widget-info">
                        <h4>Avg. Order Value</h4>
                        <p>₱<?php echo number_format($avg_order_value, 2); ?></p>
                    </div>
                </div>
                <div class="widget">
                    <div class="widget-icon"><i class="fas fa-box"></i></div>
                    <div class="widget-info">
                        <h4>Total Products</h4>
                        <p><?php echo $total_products; ?></p>
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

            <div class="admin-charts">
                <div class="chart-container card">
                    <h3>Sales Over Time</h3>
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

    <script>
    // This script must run after jQuery and Moment.js are loaded from the <head>.
    $(function() {
        const start = moment('<?php echo htmlspecialchars($start_date); ?>');
        const end = moment('<?php echo htmlspecialchars($end_date); ?>');

        function cb(start, end) {
            $('#daterange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'));
            // Update hidden inputs for form submission
            $('input[name="start_date"]').val(start.format('YYYY-MM-DD'));
            $('input[name="end_date"]').val(end.format('YYYY-MM-DD'));
        }

        $('#daterange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
               'Today': [moment(), moment()],
               'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
               'Last 7 Days': [moment().subtract(6, 'days'), moment()],
               'Last 30 Days': [moment().subtract(29, 'days'), moment()],
               'This Month': [moment().startOf('month'), moment().endOf('month')],
               'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        }, cb);

        cb(start, end);

        // Automatically submit the form when a new date range is applied
        $('#daterange').on('apply.daterangepicker', function(ev, picker) {
            $('#date-filter-form').submit();
        });
    });
    </script>
</body>
</html>