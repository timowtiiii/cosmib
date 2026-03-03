<?php
include 'db.php';

$sql = "CREATE TABLE IF NOT EXISTS page_content (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section VARCHAR(255) NOT NULL,
    content_key VARCHAR(255) NOT NULL,
    content_value TEXT NOT NULL,
    reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table page_content created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>