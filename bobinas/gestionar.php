<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

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

// Procesar eliminación de bobina
if (isset($_POST['eliminar_bobina']) && isset($_POST['bobina_id'])) {
    $bobina_id = intval($_POST['bobina_id']);
    
    $mysqli->begin_transaction();
    try {
        // Eliminar bobina
        $stmt = $mysqli->prepare('DELETE FROM bobinas WHERE bobina_id = ? AND product_id = ?');
        $stmt->bind_param('ii', $bobina_id, $product_id);
        $stmt->execute();
        $stmt->close();

        // Actualizar stock del producto
        $stmt = $mysqli->prepare("UPDATE products SET quantity = (SELECT COALESCE(SUM(metros_actuales), 0) FROM bobinas WHERE product_id = ?) WHERE product_id = ?");
        $stmt->bind_param("ii", $product_id, $product_id);
        $stmt->execute();
        $stmt->close();

        $mysqli->commit();
        $success = 'Bobina eliminada correctamente. Stock actualizado.';
    } catch (Exception $e) {
        $mysqli->rollback();
        $error = 'Error al eliminar la bobina: ' . $e->getMessage();
    }
}

// Obtener bobinas del producto
$bobinas = $mysqli->query("SELECT * FROM bobinas WHERE product_id = $product_id ORDER BY created_at DESC");

// Calcular estadísticas
$stats = $mysqli->query("SELECT 
    COUNT(*) as total_bobinas,
    SUM(metros_actuales) as metros_actuales_totales,
    AVG(metros_actuales) as promedio_metros
FROM bobinas WHERE product_id = $product_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar bobinas | <?= htmlspecialchars($product_name) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 800px; margin: 40px auto 0 auto; }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #121866;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        .bobina-row {
            transition: all 0.2s ease;
        }
        .bobina-row:hover {
            background-color: #f8f9fa;
        }
        .metros-consumidos {
            color: #dc3545;
            font-weight: 600;
        }
        .metros-disponibles {
            color: #28a745;
            font-weight: 600;
        }
        .btn-eliminar {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gestionar bobinas</h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($product_name) ?></p>
        </div>
        <a href="add.php?product_id=<?= $product_id ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Agregar bobina
        </a>
    </div>

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

    <!-- Estadísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-number"><?= $stats['total_bobinas'] ?: 0 ?></div>
            <div class="stat-label">Bobinas totales</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['metros_actuales_totales'] ?: 0, 2) ?>m</div>
            <div class="stat-label">Metros disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= number_format($stats['promedio_metros'] ?: 0, 2) ?>m</div>
            <div class="stat-label">Promedio por bobina</div>
        </div>
    </div>

    <?php if ($bobinas && $bobinas->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Identificador</th>
                        <th>Metros disponibles</th>
                        <th>Fecha ingreso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i=1; while ($bobina = $bobinas->fetch_assoc()): ?>
                        <tr class="bobina-row">
                            <td><?= $i++ ?></td>
                            <td>
                                <?php if ($bobina['identificador']): ?>
                                    <strong><?= htmlspecialchars($bobina['identificador']) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Bobina #<?= $bobina['bobina_id'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="metros-disponibles"><?= number_format($bobina['metros_actuales'], 2) ?>m</td>
                            <td><?= date('d/m/Y', strtotime($bobina['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar esta bobina?')">
                                    <input type="hidden" name="bobina_id" value="<?= $bobina['bobina_id'] ?>">
                                    <button type="submit" name="eliminar_bobina" class="btn btn-outline-danger btn-eliminar" 
                                            title="Eliminar bobina">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            No hay bobinas registradas para este producto.
            <a href="add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm ms-2">
                <i class="bi bi-plus-circle"></i> Registrar primera bobina
            </a>
        </div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="../products/edit.php?id=<?= $product_id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Volver al producto
        </a>
    </div>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 