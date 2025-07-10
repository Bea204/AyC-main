<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);
$success = $error = '';

// Obtener cotización
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, ec.nombre_estado
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    WHERE c.cotizacion_id = ? AND ec.nombre_estado = 'Aprobada'
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

if (!$cotizacion) {
    header('Location: index.php');
    exit;
}

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.sku, p.quantity as stock_actual
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    WHERE cp.cotizacion_id = ?
    ORDER BY cp.orden
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos = $stmt->get_result();

// Procesar conversión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli->begin_transaction();
    
    try {
        // Verificar si existe el tipo de movimiento "Venta", si no, crearlo
        $stmt = $mysqli->prepare("SELECT movement_type_id FROM movement_types WHERE name = 'Venta' LIMIT 1");
        $stmt->execute();
        $movement_type = $stmt->get_result()->fetch_assoc();
        $movement_type_id = $movement_type['movement_type_id'] ?? null;
        
        if (!$movement_type_id) {
            // Crear tipo de movimiento "Venta"
            $stmt = $mysqli->prepare("INSERT INTO movement_types (name) VALUES ('Venta')");
            $stmt->execute();
            $movement_type_id = $stmt->insert_id;
            $stmt->close();
        }
        
        // Registrar movimientos de inventario para cada producto
        while ($producto = $productos->fetch_assoc()) {
            if ($producto['product_id']) {
                // Verificar stock disponible
                if ($producto['stock_actual'] < $producto['cantidad']) {
                    throw new Exception("Stock insuficiente para {$producto['product_name']}. Disponible: {$producto['stock_actual']}, Requerido: {$producto['cantidad']}");
                }
                
                // Registrar movimiento de salida
                $stmt = $mysqli->prepare("
                    INSERT INTO movements (product_id, movement_type_id, quantity, movement_date) 
                    VALUES (?, ?, ?, NOW())
                ");
                $cantidad_negativa = -$producto['cantidad']; // Salida
                $stmt->bind_param('iii', $producto['product_id'], $movement_type_id, $cantidad_negativa);
                $stmt->execute();
                
                // Actualizar stock del producto
                $stmt = $mysqli->prepare("
                    UPDATE products 
                    SET quantity = quantity - ? 
                    WHERE product_id = ?
                ");
                $stmt->bind_param('ii', $producto['cantidad'], $producto['product_id']);
                $stmt->execute();
            }
        }
        
        // Actualizar estado de la cotización
        $stmt = $mysqli->prepare("
            UPDATE cotizaciones 
            SET estado_id = (SELECT est_cot_id FROM est_cotizacion WHERE nombre_estado = 'Convertida'), updated_at = NOW() 
            WHERE cotizacion_id = ?
        ");
        $stmt->bind_param('i', $cotizacion_id);
        $stmt->execute();
        
        // Registrar en historial
        $stmt = $mysqli->prepare("
            INSERT INTO cotizaciones_historial (cotizacion_id, accion_id, comentario, user_id) 
            VALUES (?, (SELECT accion_id FROM cotizaciones_acciones WHERE nombre_accion = 'Convertida'), 'Cotización convertida a venta', ?)
        ");
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        $stmt->bind_param('ii', $cotizacion_id, $usuario_id);
        $stmt->execute();
        
        $mysqli->commit();
        $success = "Cotización convertida exitosamente a venta. Se han registrado los movimientos de inventario.";
        
        // Redirigir después de 2 segundos
        header("refresh:2;url=ver.php?id=$cotizacion_id");
        
    } catch (Exception $e) {
        $mysqli->rollback();
        $error = "Error al convertir la cotización: " . $e->getMessage();
    }
}

// Reset productos para mostrar
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.sku, p.quantity as stock_actual
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    WHERE cp.cotizacion_id = ?
    ORDER BY cp.orden
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Convertir Cotización | Gestor de inventarios</title>
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
        .conversion-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #43a047;
        }
        .producto-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #121866;
        }
        .stock-warning {
            background: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        .stock-ok {
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 8px;
            font-size: 0.9rem;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-check-circle"></i> Convertir Cotización a Venta</h2>
            <a href="ver.php?id=<?= $cotizacion_id ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
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

        <div class="conversion-card">
            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Información de la Cotización</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Número:</strong> <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></p>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cotizacion['cliente_nombre']) ?></p>
                    <p><strong>Total:</strong> $<?= number_format($cotizacion['total'], 2) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) ?></p>
                    <p><strong>Estado:</strong> <span class="badge bg-success">Aprobada</span></p>
                    <p><strong>Creada por:</strong> <?= htmlspecialchars($cotizacion['usuario_nombre'] ?? 'Sistema') ?></p>
                </div>
            </div>
        </div>

        <div class="conversion-card">
            <h5 class="mb-3"><i class="bi bi-box"></i> Productos y Stock Disponible</h5>
            <p class="text-muted mb-3">Se registrarán movimientos de salida para los productos del inventario.</p>
            
            <?php 
            $todos_con_stock = true;
            while ($producto = $productos->fetch_assoc()): 
                $stock_suficiente = !$producto['product_id'] || $producto['stock_actual'] >= $producto['cantidad'];
                if ($producto['product_id'] && !$stock_suficiente) {
                    $todos_con_stock = false;
                }
            ?>
                <div class="producto-item">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-1"><?= htmlspecialchars($producto['descripcion']) ?></h6>
                            <?php if ($producto['product_id']): ?>
                                <small class="text-muted">SKU: <?= htmlspecialchars($producto['sku']) ?></small>
                            <?php else: ?>
                                <small class="text-muted">Producto externo</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-2">
                            <strong>Cantidad: <?= $producto['cantidad'] ?></strong>
                        </div>
                        <div class="col-md-2">
                            <strong>$<?= number_format($producto['precio_total'], 2) ?></strong>
                        </div>
                        <div class="col-md-2">
                            <?php if ($producto['product_id']): ?>
                                <?php if ($stock_suficiente): ?>
                                    <div class="stock-ok">
                                        <i class="bi bi-check-circle"></i> Stock: <?= $producto['stock_actual'] ?>
                                    </div>
                                <?php else: ?>
                                    <div class="stock-warning">
                                        <i class="bi bi-exclamation-triangle"></i> Stock: <?= $producto['stock_actual'] ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-muted">
                                    <i class="bi bi-info-circle"></i> Sin stock
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <?php if ($todos_con_stock): ?>
            <div class="conversion-card">
                <h5 class="mb-3"><i class="bi bi-check-circle"></i> Confirmar Conversión</h5>
                <p>Al convertir esta cotización a venta:</p>
                <ul>
                    <li>Se registrarán movimientos de salida en el inventario</li>
                    <li>Se actualizará el stock de los productos</li>
                    <li>La cotización cambiará a estado "Convertida"</li>
                    <li>Se registrará en el historial de la cotización</li>
                </ul>
                
                <form method="POST" class="mt-3">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="confirmar" required>
                        <label class="form-check-label" for="confirmar">
                            Confirmo que deseo convertir esta cotización a venta
                        </label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success" id="btnConvertir" disabled>
                            <i class="bi bi-check-circle"></i> Convertir a Venta
                        </button>
                        <a href="ver.php?id=<?= $cotizacion_id ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>No se puede convertir la cotización</strong><br>
                Algunos productos no tienen suficiente stock disponible. 
                Por favor, actualiza el inventario antes de proceder.
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
        
        // Habilitar botón cuando se confirma
        document.getElementById('confirmar').addEventListener('change', function() {
            document.getElementById('btnConvertir').disabled = !this.checked;
        });
    </script>
</body>
</html> 