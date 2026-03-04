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

// Fetch all users
$sql = "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Start building the HTML for the PDF
$html = '
<style>
    body { font-family: sans-serif; }
    h1 { color: #ffaf00; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background-color: #2c2c2c; color: #f2f2f2; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>
<h1>User List Report</h1>
<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
<table>
    <thead>
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Date Registered</th>
        </tr>
    </thead>
    <tbody>';

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($row['id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $html .= '<td>' . htmlspecialchars(ucfirst($row['role'])) . '</td>';
        $html .= '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['created_at']))) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="5">No users found.</td></tr>';
}

$html .= '
    </tbody>
</table>';

// Create an instance of the mPDF class
$mpdf = new \Mpdf\Mpdf();

// Write the HTML content to the PDF
$mpdf->WriteHTML($html);

// Output the PDF as a download
$filename = "users_list_" . date('Y-m-d') . ".pdf";
$mpdf->Output($filename, 'D');

exit;