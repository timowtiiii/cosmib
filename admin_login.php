<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $error = "Invalid credentials or not an admin account."; // Default error

    // --- Method 1: Try logging in from 'users' table (preferred) ---
    $sql_user = "SELECT id, password, role FROM users WHERE username = ? AND role = 'admin'";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $username);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();

    if ($user_row = $result_user->fetch_assoc()) {
        if (password_verify($password, $user_row['password'])) {
            // Success with modern method
            session_regenerate_id(true);
            $_SESSION = array();
            $_SESSION['admin_loggedin'] = true;
            $_SESSION['user_id'] = $user_row['id'];
            $_SESSION['username'] = $username;
            header("location: admin_dashboard.php");
            exit;
        }
    }
    $stmt_user->close();

    // --- Method 2: Fallback to legacy 'admin' table ---
    // This part only runs if the first method fails.
    $table_exists_res = $conn->query("SHOW TABLES LIKE 'admin'");
    if ($table_exists_res && $table_exists_res->num_rows > 0) {
        $sql_admin = "SELECT password FROM admin WHERE username = ?";
        $stmt_admin = $conn->prepare($sql_admin);
        $stmt_admin->bind_param("s", $username);
        $stmt_admin->execute();
        $result_admin = $stmt_admin->get_result();

        if ($admin_row = $result_admin->fetch_assoc()) {
            if (password_verify($password, $admin_row['password'])) {
                // Legacy admin authenticated. Now, find their corresponding user_id.
                $sql_get_id = "SELECT id FROM users WHERE username = ?";
                $stmt_get_id = $conn->prepare($sql_get_id);
                $stmt_get_id->bind_param("s", $username);
                $stmt_get_id->execute();
                $result_get_id = $stmt_get_id->get_result();
                
                if ($user_for_admin = $result_get_id->fetch_assoc()) {
                    // Success with legacy method, and we found a corresponding user entry.
                    session_regenerate_id(true);
                    $_SESSION = array();
                    $_SESSION['admin_loggedin'] = true;
                    $_SESSION['user_id'] = $user_for_admin['id']; // Use the ID from the 'users' table
                    $_SESSION['username'] = $username;
                    header("location: admin_dashboard.php");
                    exit;
                } else {
                    // Self-healing: No corresponding user found. Create one to migrate the legacy admin.
                    $placeholder_email = $username . '@placeholder.user'; // A placeholder email
                    $password_hash_from_admin = $admin_row['password']; // The hash from the 'admin' table
                    $role = 'admin';

                    $stmt_create_user = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
                    $stmt_create_user->bind_param("ssss", $username, $placeholder_email, $password_hash_from_admin, $role);
                    
                    if ($stmt_create_user->execute()) {
                        $new_user_id = $stmt_create_user->insert_id;
                        
                        // Now log the user in with the newly created user account
                        session_regenerate_id(true);
                        $_SESSION = array();
                        $_SESSION['admin_loggedin'] = true;
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['username'] = $username;
                        header("location: admin_dashboard.php");
                        exit;
                    } else {
                        $error = "Authentication successful, but failed to create a corresponding user account. Please contact support.";
                    }
                    $stmt_create_user->close();
                }
                $stmt_get_id->close();
            }
        }
        $stmt_admin->close();
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