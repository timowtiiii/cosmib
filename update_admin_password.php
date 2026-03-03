<?php
include 'db.php';

$username = 'superadmin';
$password = 'password123';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$sql = "UPDATE admin SET password = '$hashed_password' WHERE username = '$username'";

if ($conn->query($sql) === TRUE) {
  echo "Admin password updated successfully. Please try to log in again with the password 'password123'.";
} else {
  echo "Error updating password: " . $conn->error;
}

$conn->close();
?>