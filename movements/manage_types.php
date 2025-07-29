<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Procesar eliminación
if (isset($_POST['delete_type']) && isset($_POST['movement_type_id'])) {
    $movement_type_id = intval($_POST['movement_type_id']);
    
    // Verificar si hay movimientos usando este tipo
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM movements WHERE movement_type_id = ?");
    $stmt->bind_param('i', $movement_type_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = "No se puede eliminar este tipo de movimiento porque está siendo usado en " . $result['count'] . " movimiento(s).";
    } else {
        $stmt = $mysqli->prepare("DELETE FROM movement_types WHERE movement_type_id = ?");
        $stmt->bind_param('i', $movement_type_id);
        if ($stmt->execute()) {
            $success = "Tipo de movimiento eliminado correctamente.";
        } else {
            $error = "Error al eliminar: " . $stmt->error;
        }
    }
}

// Procesar edición
if (isset($_POST['edit_type']) && isset($_POST['movement_type_id']) && isset($_POST['name'])) {
    $movement_type_id = intval($_POST['movement_type_id']);
    $name = trim($_POST['name']);
    
    if ($name === '') {
        $error = "El nombre del tipo de movimiento es obligatorio.";
    } else {
        // Verificar si ya existe otro con el mismo nombre
        $stmt = $mysqli->prepare("SELECT movement_type_id FROM movement_types WHERE name = ? AND movement_type_id != ?");
        $stmt->bind_param('si', $name, $movement_type_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Ya existe un tipo de movimiento con ese nombre.";
        } else {
            $stmt = $mysqli->prepare("UPDATE movement_types SET name = ? WHERE movement_type_id = ?");
            $stmt->bind_param('si', $name, $movement_type_id);
            if ($stmt->execute()) {
                $success = "Tipo de movimiento actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . $stmt->error;
            }
        }
    }
}

// Obtener tipos de movimiento con estadísticas
$query = "
    SELECT 
        mt.movement_type_id,
        mt.name,
        mt.is_entry,
        COUNT(m.movement_id) as total_movements,
        SUM(CASE WHEN m.quantity > 0 THEN m.quantity ELSE 0 END) as total_entradas,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) ELSE 0 END) as total_salidas
    FROM movement_types mt
    LEFT JOIN movements m ON mt.movement_type_id = m.movement_type_id
    GROUP BY mt.movement_type_id, mt.name, mt.is_entry
    ORDER BY mt.name
";
$movement_types = $mysqli->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Tipos de Movimiento | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .main-content {
            margin-top: 40px;
            margin-left: 250px;
            padding: 24px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
        .type-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
            transition: transform 0.2s ease;
        }
        .type-card:hover {
            transform: translateY(-2px);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .stat-item {
            text-align: center;
            padding: 8px;
            background: #f7f9fc;
            border-radius: 8px;
            border: 1px solid #e3e6f0;
        }
        .stat-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
            display: block;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #666;
        }
        .btn-edit {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        .btn-delete {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-gear"></i> Gestionar Tipos de Movimiento</h2>
            <a href="new.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nuevo Movimiento
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

        <?php if ($movement_types && $movement_types->num_rows > 0): ?>
            <?php while ($type = $movement_types->fetch_assoc()): ?>
                <div class="type-card">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <h5 class="mb-1"><?= htmlspecialchars($type['name']) ?></h5>
                            <small class="text-muted">ID: <?= $type['movement_type_id'] ?></small>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-grid">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $type['total_movements'] ?></span>
                                    <span class="stat-label">Movimientos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $type['total_entradas'] ?: 0 ?></span>
                                    <span class="stat-label">Entradas</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $type['total_salidas'] ?: 0 ?></span>
                                    <span class="stat-label">Salidas</span>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span class="badge <?= $type['is_entry'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $type['is_entry'] ? 'Entrada' : 'Salida' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-outline-primary btn-edit me-2" 
                                    onclick="editarTipo(<?= $type['movement_type_id'] ?>, '<?= htmlspecialchars($type['name']) ?>')">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <?php if ($type['total_movements'] == 0): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este tipo de movimiento?')">
                                    <input type="hidden" name="movement_type_id" value="<?= $type['movement_type_id'] ?>">
                                    <button type="submit" name="delete_type" class="btn btn-outline-danger btn-delete">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary btn-delete" disabled title="No se puede eliminar porque tiene movimientos asociados">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-gear" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay tipos de movimiento</h4>
                <p class="text-muted">Los tipos de movimiento se crean automáticamente al registrar movimientos.</p>
                <a href="new.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Primer Movimiento
                </a>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Movimientos
            </a>
        </div>
    </main>

    <!-- Modal para editar tipo de movimiento -->
    <div class="modal fade" id="modalEditarTipo" tabindex="-1" aria-labelledby="modalEditarTipoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarTipoLabel">
                        <i class="bi bi-pencil"></i> Editar Tipo de Movimiento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="movement_type_id" id="editMovementTypeId">
                        <div class="mb-3">
                            <label for="editTypeName" class="form-label">Nombre del tipo de movimiento:</label>
                            <input type="text" class="form-control" id="editTypeName" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="submit" name="edit_type" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-movimientos').classList.add('active');
        
        function editarTipo(id, nombre) {
            document.getElementById('editMovementTypeId').value = id;
            document.getElementById('editTypeName').value = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarTipo'));
            modal.show();
        }
    </script>
</body>
</html> 