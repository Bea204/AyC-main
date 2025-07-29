<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener roles para los selects
$roles = $mysqli->query("SELECT role_id, role_name FROM roles ORDER BY role_name ASC");
$roles_array = [];
if ($roles) {
    while ($r = $roles->fetch_assoc()) {
        $roles_array[] = $r;
    }
}

// Editar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $user_id = intval($_POST['user_id']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $role_id = intval($_POST['role_id']);
    $password = $_POST['password'];
    if ($user_id && $username && $email && $role_id) {
        // Verificar duplicados (excepto el propio usuario)
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
        $stmt->bind_param('ssi', $username, $email, $user_id);
        $stmt->execute();
        $stmt->bind_result($existe);
        $stmt->fetch();
        $stmt->close();
        if ($existe > 0) {
            $error = 'El usuario o email ya existe.';
        } else {
            if ($password) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET username=?, email=?, password=?, role_id=? WHERE user_id=?");
                $stmt->bind_param('sssii', $username, $email, $hash, $role_id, $user_id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET username=?, email=?, role_id=? WHERE user_id=?");
                $stmt->bind_param('ssii', $username, $email, $role_id, $user_id);
            }
            if ($stmt->execute()) {
                $success = 'Usuario actualizado correctamente.';
            } else {
                $error = 'Error al actualizar usuario: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'Completa todos los campos correctamente.';
    }
}

// Eliminar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $user_id = intval($_POST['user_id']);
    if ($user_id) {
        $stmt = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            $success = 'Usuario eliminado correctamente.';
        } else {
            $error = 'Error al eliminar usuario: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Usuario no válido.';
    }
}

// Procesar alta de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);
    if ($username && $email && $password && $role_id) {
        // Verificar duplicados
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $stmt->bind_result($existe);
        $stmt->fetch();
        $stmt->close();
        if ($existe > 0) {
            $error = 'El usuario o email ya existe.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('sssi', $username, $email, $hash, $role_id);
            if ($stmt->execute()) {
                $success = 'Usuario agregado correctamente.';
            } else {
                $error = 'Error al agregar usuario: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'Completa todos los campos correctamente.';
    }
}

// Obtener usuarios con JOIN a roles
$usuarios = $mysqli->query("SELECT u.*, r.role_name AS role_name FROM users u LEFT JOIN roles r ON u.role_id = r.role_id ORDER BY u.created_at DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración | Gestión de usuarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 900px; margin: 40px auto 0 auto; padding: 24px; }
        .acciones { display: flex; gap: 6px; justify-content: center; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2><i class="bi bi-gear"></i> Configuración de usuarios</h2>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <div class="mb-3 d-flex justify-content-between align-items-center">
        <span class="text-muted">Gestión de usuarios y accesos</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario"><i class="bi bi-person-plus"></i> Agregar usuario</button>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Fecha de alta</th>
                    <th style="text-align:center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios && $usuarios->num_rows > 0): $i=1; ?>
                    <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-info text-dark"><?= htmlspecialchars($u['role_name']) ?></span></td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td class="acciones">
                                <button class="btn btn-outline-primary btn-sm btn-edit-user" 
                                    data-user-id="<?= $u['user_id'] ?>"
                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                    data-email="<?= htmlspecialchars($u['email']) ?>"
                                    data-role-id="<?= $u['role_id'] ?>"
                                    title="Editar"><i class="bi bi-pencil-square"></i></button>
                                <button class="btn btn-outline-danger btn-sm btn-delete-user" 
                                    data-user-id="<?= $u['user_id'] ?>"
                                    data-username="<?= htmlspecialchars($u['username']) ?>"
                                    title="Eliminar"><i class="bi bi-trash3"></i></button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No hay usuarios registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<!-- Modal agregar usuario -->
<div class="modal fade" id="modalAgregarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Agregar usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" name="username" id="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role_id" class="form-label">Rol</label>
                        <select class="form-select" name="role_id" id="role_id" required>
                            <option value="">Selecciona un rol</option>
                            <?php foreach ($roles_array as $r): ?>
                                <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal editar usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Editar usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Usuario</label>
                        <input type="text" class="form-control" name="username" id="edit_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Contraseña (dejar vacío para no cambiar)</label>
                        <input type="password" class="form-control" name="password" id="edit_password">
                    </div>
                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label">Rol</label>
                        <select class="form-select" name="role_id" id="edit_role_id" required>
                            <option value="">Selecciona un rol</option>
                            <?php foreach ($roles_array as $r): ?>
                                <option value="<?= $r['role_id'] ?>"><?= htmlspecialchars($r['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal eliminar usuario -->
<div class="modal fade" id="modalEliminarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-trash"></i> Eliminar usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar al usuario <strong id="delete_username"></strong>?</p>
                    <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('.sidebar-configuracion').classList.add('active');
    // Editar usuario
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_user_id').value = this.dataset.userId;
            document.getElementById('edit_username').value = this.dataset.username;
            document.getElementById('edit_email').value = this.dataset.email;
            document.getElementById('edit_role_id').value = this.dataset.roleId;
            document.getElementById('edit_password').value = '';
            const modal = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
            modal.show();
        });
    });
    // Eliminar usuario
    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('delete_user_id').value = this.dataset.userId;
            document.getElementById('delete_username').textContent = this.dataset.username;
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarUsuario'));
            modal.show();
        });
    });
</script>
</body>
</html>