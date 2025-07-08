<?php
    require_once '../connection.php';

    // Destroy session to log out the admin
    session_unset();      // Clear session variables
    session_destroy();    // Destroy the session itself

    // Invalidate session cookie (optional)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // Redirect to login page
    header('Location: login.php');
    exit;
?>