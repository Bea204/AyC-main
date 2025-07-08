<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $ubicacion = trim($_POST['ubicacion']);
    $email = trim($_POST['email']);
    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        $stmt = $mysqli->prepare("UPDATE clientes SET nombre=?, telefono=?, ubicacion=?, email=? WHERE cliente_id=?");
        $stmt->bind_param("ssssi", $nombre, $telefono, $ubicacion, $email, $id);
        if ($stmt->execute()) {
            header("Location: index.php?success=2");
            exit;
        } else {
            $error = 'Error al actualizar cliente.';
        }
    }
} else {
    $result = $mysqli->query("SELECT * FROM clientes WHERE cliente_id = $id");
    $cliente = $result->fetch_assoc();
    if (!$cliente) {
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="container">
            <h2 class="mb-4"><i class="bi bi-pencil"></i> Editar Cliente</h2>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <form method="post" class="card p-4" style="max-width:500px;">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre *</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>">
                </div>
                <div class="mb-3">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <input type="text" class="form-control" id="ubicacion" name="ubicacion" value="<?= htmlspecialchars($cliente['ubicacion']) ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">
                </div>
                <button type="submit" class="btn btn-warning"><i class="bi bi-pencil"></i> Guardar Cambios</button>
                <a href="index.php" class="btn btn-secondary ms-2">Cancelar</a>
            </form>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 