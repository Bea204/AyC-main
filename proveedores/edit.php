<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';
$supplier = null;

// Obtener ID del proveedor
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$supplier_id = intval($_GET['id']);

// Obtener datos del proveedor
$stmt = $mysqli->prepare("SELECT * FROM suppliers WHERE supplier_id = ?");
$stmt->bind_param("i", $supplier_id);
$stmt->execute();
$result = $stmt->get_result();
$supplier = $result->fetch_assoc();
$stmt->close();

if (!$supplier) {
    header("Location: index.php");
    exit;
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $contact_name = trim($_POST['contact_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    if (!empty($name)) {
        $stmt = $mysqli->prepare("UPDATE suppliers SET name = ?, contact_name = ?, phone = ?, email = ?, address = ? WHERE supplier_id = ?");
        $stmt->bind_param("sssssi", $name, $contact_name, $phone, $email, $address, $supplier_id);
        if ($stmt->execute()) {
            $success = "Proveedor actualizado correctamente.";
            // Actualizar datos en la variable
            $supplier['name'] = $name;
            $supplier['contact_name'] = $contact_name;
            $supplier['phone'] = $phone;
            $supplier['email'] = $email;
            $supplier['address'] = $address;
        } else {
            $error = "Error al actualizar proveedor: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "El nombre del proveedor es obligatorio.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Proveedor | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 600px; margin: 40px auto 0 auto; }
        .form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
            border: none;
        }
        .form-card .card-body {
            padding: 24px;
        }
        @media (max-width: 900px) { .main-content { max-width: 95vw; padding: 0 2vw; } }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-pencil"></i> 
                    Editar Proveedor: <?= htmlspecialchars($supplier['name']) ?>
                </h5>
            </div>
            <div class="card-body">
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

                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre del proveedor</label>
                        <input type="text" class="form-control" name="name" id="name" required 
                               value="<?= htmlspecialchars($supplier['name'] ?? '') ?>"
                               placeholder="Ej: Syscom, PCH, Amazon, etc.">
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">Nombre de contacto</label>
                        <input type="text" class="form-control" name="contact_name" id="contact_name"
                               value="<?= htmlspecialchars($supplier['contact_name'] ?? '') ?>"
                               placeholder="Ej: Juan Pérez">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="phone" id="phone"
                               value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>"
                               placeholder="Ej: 9991234567">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" id="email"
                               value="<?= htmlspecialchars($supplier['email'] ?? '') ?>"
                               placeholder="Ej: contacto@proveedor.com">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" name="address" id="address" rows="2"
                                  placeholder="Dirección del proveedor"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-check-circle"></i> Actualizar Proveedor
                        </button>
                        <a href="index.php" class="btn btn-secondary flex-fill">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 