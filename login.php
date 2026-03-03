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

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hash = $user['password'];
    } else {
        // Dummy hash to prevent timing attacks
        $hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    }

    if (password_verify($password, $hash)) {
        if (isset($user)) {
            // Regenerate session ID to prevent session fixation and clear old session data
            session_regenerate_id(true);
            $_SESSION = array(); // Clear all session variables
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email']; // Store email in session
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                $_SESSION['admin_loggedin'] = true;
                // For admin, redirect to admin dashboard
                header('Location: admin_dashboard.php');
            } else {
                $_SESSION['admin_loggedin'] = false; // Explicitly set to false for regular users
                // For regular user, redirect to index
                header('Location: index.php');
            }
            exit;
        }
    }
    $error = "Invalid username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <form action="login.php" method="post" class="card" style="padding: 40px;">
            <h2 class="section-title" style="text-align: center; margin-bottom: 30px;">Login</h2>

            <?php if (isset($_SESSION['login_success'])): ?>
                <div style="color: green; text-align: center; margin-bottom: 20px;">
                    <?php echo $_SESSION['login_success']; unset($_SESSION['login_success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <p style="color: red; text-align: center; margin-bottom: 20px;"><?php echo $error; ?></p>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <label for="username" style="display: block; margin-bottom: 5px; font-weight: 600;">Username</label>
                <input type="text" id="username" name="username" required style="width: 100%; padding: 10px;">
            </div>

            <div style="margin-bottom: 30px;">
                <label for="password" style="display: block; margin-bottom: 5px; font-weight: 600;">Password</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 10px;">
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Login</button>
            <p style="text-align: center; margin-top: 20px;">Don't have an account? <a href="signup.php">Sign up here</a>.</p>
            <p style="text-align: center; margin-top: 10px;">
                <a href="admin_login.php">Log in as Admin</a>
            </p>
            <p style="text-align: center; margin-top: 10px;">
                <a href="index.php" class="btn-secondary" style="text-decoration: none; padding: 10px 20px;">Back to Store</a>
            </p>
        </form>
    </div>
</body>
</html>
