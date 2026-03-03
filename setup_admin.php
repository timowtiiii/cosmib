<?php
include 'db.php';

// sql to create table
$sql = "CREATE TABLE admin (
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(30) NOT NULL,
password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
  echo "Table admin created successfully";
} else {
  echo "Error creating table: " . $conn->error;
}

$conn->close();
?>