<?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Database configuration
    $host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'inventory_management_system2';

    // Create MySQLi connection
    $mysqli = new mysqli($host, $db_user, $db_pass, $db_name);

    // Check for connection error
    if ($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }

    // Set charset (optional)
    $mysqli->set_charset('utf8mb4');
?>