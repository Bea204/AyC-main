<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once '../includes/bobina_helpers.php';

$success = $error = '';

// Obtener el producto
if (!isset($_GET['product_id']) || !is_numeric($_GET['product_id'])) {
    die('Producto no especificado.');
}
$product_id = intval($_GET['product_id']);
$stmt = $mysqli->prepare('SELECT product_name, tipo_gestion FROM products WHERE product_id = ?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$stmt->bind_result($product_name, $tipo_gestion);
$stmt->fetch();
$stmt->close();

// Verificar que el producto sea tipo bobina
if ($tipo_gestion !== 'bobina') {
    die('Este producto no está configurado como tipo bobina.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metros_actuales = floatval($_POST['metros_actuales']);
    $identificador = trim($_POST['identificador']);
    if ($metros_actuales > 0) {
        $mysqli->begin_transaction();
        try {
            // Insertar bobina
            $stmt = $mysqli->prepare('INSERT INTO bobinas (product_id, metros_actuales, identificador) VALUES (?, ?, ?)');
            $stmt->bind_param('ids', $product_id, $metros_actuales, $identificador);
            $stmt->execute();
            $stmt->close();

            // Actualizar stock del producto (suma de todas las bobinas)
            actualizarStockBobina($mysqli, $product_id);

            $mysqli->commit();
            $success = 'Bobina registrada correctamente. Stock actualizado automáticamente.';
        } catch (Exception $e) {
            $mysqli->rollback();
            $error = 'Error al registrar la bobina: ' . $e->getMessage();
        }
    } else {
        $error = 'Los metros deben ser mayores a 0.';
    }
}

// Obtener bobinas existentes para mostrar resumen
$bobinas_existentes = $mysqli->query("SELECT COUNT(*) as total, SUM(metros_actuales) as metros_totales FROM bobinas WHERE product_id = $product_id");
$resumen = $bobinas_existentes->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar bobina | <?= htmlspecialchars($product_name) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 440px; margin: 40px auto 0 auto; }
        .resumen-bobinas {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .resumen-bobinas h6 {
            color: #1565c0;
            margin-bottom: 10px;
        }
        .resumen-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar bobina para:<br><small><?= htmlspecialchars($product_name) ?></small></h2>
    
    <!-- Resumen de bobinas existentes -->
    <div class="resumen-bobinas">
        <h6><i class="bi bi-receipt"></i> Resumen de bobinas</h6>
        <div class="resumen-item">
            <span>Bobinas registradas:</span>
            <strong><?= $resumen['total'] ?: 0 ?></strong>
        </div>
        <div class="resumen-item">
            <span>Metros totales:</span>
            <strong><?= number_format($resumen['metros_totales'] ?: 0, 2) ?>m</strong>
        </div>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success d-flex flex-column align-items-start gap-2">
            <div><i class="bi bi-check-circle"></i> <?= $success ?></div>
            <div class="d-flex gap-2 mt-2">
                <a href="add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Agregar otra bobina</a>
                <a href="../products/edit.php?id=<?= $product_id ?>" class="btn btn-success btn-sm"><i class="bi bi-arrow-left"></i> Volver al producto</a>
            </div>
        </div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>
    
    <form action="" method="POST" class="mb-3">
        <div class="mb-3">
            <label for="metros_actuales" class="form-label">Metros actuales de la bobina</label>
            <input type="number" step="0.01" min="0.01" class="form-control" name="metros_actuales" id="metros_actuales" required>
            <small class="text-muted">Ingresa los metros totales de la bobina</small>
        </div>
        <div class="mb-3">
            <label for="identificador" class="form-label">Identificador (opcional)</label>
            <input type="text" class="form-control" name="identificador" id="identificador" placeholder="Ej: Bobina #1, Lote 2024, etc.">
            <small class="text-muted">Identificador único para la bobina</small>
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Registrar bobina</button>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 