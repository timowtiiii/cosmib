<?php
session_start();
include 'db.php';

// Include the mPDF library autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true){
    header("HTTP/1.1 403 Forbidden");
    exit("Access denied.");
}

// Fetch detailed order data
$sql = "SELECT o.id AS order_id, o.order_date, o.customer_name, o.customer_email, o.customer_address, o.total_price AS order_total, p.name AS product_name, oi.quantity, oi.price AS price_per_item FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN products p ON oi.product_id = p.id ORDER BY o.order_date DESC, o.id, p.name";
$result = $conn->query($sql);

// Start building the HTML for the PDF
$html = '
<style>
    body { font-family: sans-serif; }
    h1 { color: #ffaf00; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 10px; }
    th { background-color: #2c2c2c; color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>
<h1>Detailed Orders Report</h1>
<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
<table>
    <thead>
        <tr>
            <th>Order ID</th>
            <th>Order Date</th>
            <th>Customer Name</th>
            <th>Customer Email</th>
            <th>Customer Address</th>
            <th>Order Total</th>
            <th>Product Name</th>
            <th>Qty</th>
            <th>Price/Item</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $item_subtotal = $row['quantity'] * $row['price_per_item'];
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['order_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars(date('Y-m-d', strtotime($row['order_date']))) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['customer_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['customer_email']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['customer_address']) . '</td>';
        $html .= '<td>₱' . htmlspecialchars(number_format($row['order_total'], 2)) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['product_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['quantity']) . '</td>';
        $html .= '<td>₱' . htmlspecialchars(number_format($row['price_per_item'], 2)) . '</td>';
        $html .= '<td>₱' . htmlspecialchars(number_format($item_subtotal, 2)) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="10">No orders found.</td></tr>';
}

$html .= '
    </tbody>
</table>';

// Create an instance of the mPDF class
$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4-L', // A4 Landscape
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 20,
    'margin_bottom' => 20,
    'margin_header' => 10,
    'margin_footer' => 10
]);

// Write the HTML content to the PDF
$mpdf->WriteHTML($html);

// Output the PDF as a download
$filename = "detailed_orders_report_" . date('Y-m-d') . ".pdf";
$mpdf->Output($filename, 'D');

exit;