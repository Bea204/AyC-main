<?php
$mysqli = new mysqli('localhost','root','','inventory_management_system2');
if ($mysqli->connect_errno) {
    die('Error de conexión: ' . $mysqli->connect_error . "\n");
}

// 1. Crear rol 'Administrador' si no existe
$role_id = null;
$res = $mysqli->query("SELECT role_id FROM roles WHERE role_name = 'Administrador' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    $role_id = $row['role_id'];
    echo "Rol 'Administrador' ya existe con role_id: $role_id\n";
} else {
    $mysqli->query("INSERT INTO roles (role_name) VALUES ('Administrador')");
    if ($mysqli->insert_id) {
        $role_id = $mysqli->insert_id;
        echo "Rol 'Administrador' creado con role_id: $role_id\n";
    } else {
        die("Error creando rol: " . $mysqli->error . "\n");
    }
}

// 2. Crear usuario 'admin' si no existe
$res = $mysqli->query("SELECT user_id FROM users WHERE username = 'admin' LIMIT 1");
if ($row = $res->fetch_assoc()) {
    echo "Usuario 'admin' ya existe.\n";
} else {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (username, password, role_id, is_active) VALUES ('admin', '$password', $role_id, 1)";
    if ($mysqli->query($sql)) {
        echo "Usuario 'admin' creado con contraseña 'admin123'.\n";
    } else {
        echo "Error creando usuario: " . $mysqli->error . "\n";
    }
}
$mysqli->close(); 