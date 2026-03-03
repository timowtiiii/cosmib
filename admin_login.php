<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT id, password FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Regenerate session ID to prevent session fixation and clear old session data
            session_regenerate_id(true);
            $_SESSION = array(); // Clear all session variables

            $_SESSION['admin_loggedin'] = true;
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['username'] = $username;
            header("location: admin_dashboard.php");
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "No account found with that username";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | COSMI BEAUTII</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 400px; margin-top: 100px;">
        <form action="admin_login.php" method="post" class="card" style="padding: 40px;">
            <h2 class="section-title" style="text-align: center; margin-bottom: 30px;">Admin Login</h2>

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
            <p style="text-align: center; margin-top: 20px;">
                <a href="login.php">Log in as User</a>
            </p>
            <p style="text-align: center; margin-top: 10px;">
                <a href="index.php" class="btn-secondary" style="text-decoration: none; padding: 10px 20px;">Back to Store</a>
            </p>
        </form>
    </div>
</body>
</html>