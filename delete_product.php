<?php
session_start(); // Start the session

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    die("Unauthorized access."); // Or you could redirect to a login page or display a more user-friendly error
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'])) {
        $id = $_POST['id'];

        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Product deleted successfully.";
        } else {
            echo "Error deleting product: " . $conn->error;
        }

        $stmt->close();
    } else {
        echo "Invalid data.";
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>