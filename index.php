<?php
    require_once 'connection.php';

    // Redirect based on login status
    if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
        header('Location: dashboard/index.php');
        exit;
    } else {
        header('Location: auth/login.php');
        exit;
    }
?>