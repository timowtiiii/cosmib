<?php
session_start(); // Start the session

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    die("Unauthorized access."); // Or you could redirect to a login page or display a more user-friendly error
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['name'], $_POST['description'], $_POST['price'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];

        $sql = "UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $name, $description, $price, $id);

        if ($stmt->execute()) {
            echo "Product updated successfully.";
        } else {
            echo "Error updating product: " . $conn->error;
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