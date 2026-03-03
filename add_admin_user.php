<?php
include 'db.php';

$username = 'superadmin';
$password = password_hash('password123', PASSWORD_DEFAULT);

$sql = "INSERT INTO admin (username, password) VALUES ('$username', '$password')";

if ($conn->query($sql) === TRUE) {
  echo "New record created successfully";
} else {
  echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>