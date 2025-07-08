<?php
    require_once '../connection.php';

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Sanitize input
        $username = trim($_POST['username']);
        $password = $_POST['admin_password'];

        // Buscar en la tabla users primero
        $stmt = $mysqli->prepare("
            SELECT u.user_id, u.password, u.custom_permissions, r.role_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $user['role_name'];
                $_SESSION['custom_permissions'] = $user['custom_permissions'];
                // $_SESSION['role_permissions'] = $user['role_permissions']; // Eliminado porque no existe
                
                // Registrar login en logs
                $log_stmt = $mysqli->prepare("
                    INSERT INTO logs (action, description, user_id) 
                    VALUES ('login', ?, ?)
                ");
                $description = "Usuario $username inició sesión";
                $log_stmt->bind_param('si', $description, $user['user_id']);
                $log_stmt->execute();
                
                header("Location: ../dashboard/index.php");
                exit;
            } else {
                $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
        }
        $stmt->close();
        $mysqli->close();
    } else {
        $_SESSION['login_error'] = 'Por favor, envía el formulario correctamente.';
    }

    // Redirect back to login on failure
    header("Location: login.php");
    exit;
?>