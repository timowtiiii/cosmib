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
            $sql_insert = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"; // Include email
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password); // Bind email

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
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <form action="signup.php" method="post" class="card" style="padding: 40px;">
            <h2 class="section-title" style="text-align: center; margin-bottom: 30px;">Create Account</h2>
            
            <?php if (isset($error)): ?>
                <p style="color: red; text-align: center; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <label for="username" style="display: block; margin-bottom: 5px; font-weight: 600;">Username</label>
                <input type="text" id="username" name="username" required style="width: 100%; padding: 10px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; margin-bottom: 5px; font-weight: 600;">Email Address</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 10px;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: 600;">Password</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 10px;">
            </div>

            <div style="margin-bottom: 30px;">
                <label for="confirm_password" style="display: block; margin-bottom: 5px; font-weight: 600;">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required style="width: 100%; padding: 10px;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Sign Up</button>
            <p style="text-align: center; margin-top: 20px;">Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
</body>
</html>
