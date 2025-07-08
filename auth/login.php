<?php
    require_once '../connection.php';

    // Redirect to dashboard if already logged in
    if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
        header('Location: ../dashboard/index.php');
        exit;
    }
?>

<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Admin Login</title>
            <link rel="stylesheet" href="../assets/css/style.css">
        </head>
        <body>
            <div class="login-container">
                <h2>Admin Login</h2>
                
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="error"><?= $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
                <?php endif; ?>
                
                <form action="authenticate.php" method="POST">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" required>
                    
                    <label for="admin_password">Password:</label>
                    <input type="password" name="admin_password" id="admin_password" required>
                    
                    <button type="submit">Login</button>
                </form>
            </div>
        </body>
    </html>