<?php
session_start(); // Ensure session is started at the very beginning
include 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email']; // Get email from form
    $confirm_password = $_POST['confirm_password'];

    // Get address from form
    $region = trim($_POST['region']);
    $province = trim($_POST['province']);
    $city = trim($_POST['city']);
    $barangay = trim($_POST['barangay']);

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if username or email already exists
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($existing_id);
            $error = "Username or email already taken."; // More specific error
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // IMPORTANT: You must add these new columns (region, province, city, barangay) to your `users` table in the database.
            $sql_insert = "INSERT INTO users (username, email, password, region, province, city, barangay) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sssssss", $username, $email, $hashed_password, $region, $province, $city, $barangay);

            // Regenerate session ID after successful signup to prevent session fixation
            session_regenerate_id(true);
            if ($stmt_insert->execute()) {
                $_SESSION['login_success'] = "Account created successfully. Please login.";
                header('Location: login.php');
                exit;
            } else {
                $error = "An error occurred. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <form action="signup.php" method="post" class="card form-card">
            <h2 class="section-title">Create Account</h2>
            
            <?php if (isset($error)): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <hr>
            <h3>Address</h3>

            <div class="form-group">
                <label for="region">Region</label>
                <input type="text" id="region" name="region" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="province">Province</label>
                <input type="text" id="province" name="province" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="city">City / Municipality</label>
                <input type="text" id="city" name="city" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="barangay">Barangay</label>
                <input type="text" id="barangay" name="barangay" class="form-control" required>
            </div>

            <button type="submit" class="btn-primary">Sign Up</button>
            <p class="form-footer-text">Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>

</body>
</html>
