<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$proveedor_filtro = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta base
$query = "SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
          LEFT JOIN products p ON i.product_id = p.product_id
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE 1=1";
$params = [];
$types = '';

if ($categoria_filtro) {
    $query .= " AND i.categoria = ?";
    $params[] = $categoria_filtro;
    $types .= 's';
}
if ($proveedor_filtro) {
    $query .= " AND s.supplier_id = ?";
    $params[] = $proveedor_filtro;
    $types .= 'i';
}
if ($estado_filtro) {
    $query .= " AND i.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}
if ($busqueda) {
    $query .= " AND (i.nombre LIKE ? OR i.categoria LIKE ? OR s.name LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
$query .= " ORDER BY i.nombre ASC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$insumos = $stmt->get_result();

// Obtener categorías únicas
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
// Obtener proveedores únicos
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

// Calcular estadísticas
$stats_query = "SELECT COUNT(*) as total_insumos,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'bajo_stock' THEN 1 ELSE 0 END) as bajo_stock,
    SUM(CASE WHEN estado = 'agotado' THEN 1 ELSE 0 END) as agotados,
    SUM(cantidad * precio_unitario) as valor_total,
    SUM(consumo_semanal) as consumo_semanal_total
    FROM insumos";
$stats = $mysqli->query($stats_query)->fetch_assoc();
$total_insumos = $stats['total_insumos'];
$disponibles = $stats['disponibles'];
$bajo_stock = $stats['bajo_stock'];
$agotados = $stats['agotados'];
$valor_total = $stats['valor_total'];
$consumo_semanal_total = $stats['consumo_semanal_total'];

// --- Endpoint para alta real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar_insumo') {
    header('Content-Type: application/json');
    $product_id = intval($_POST['product_id'] ?? 0);
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $minimo = floatval($_POST['minimo'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'pieza');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
    if (!$product_id || $cantidad <= 0 || $minimo < 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    // Obtener producto de origen
    $stmt = $mysqli->prepare("SELECT product_id, product_name, quantity, price, category_id, supplier_id FROM products WHERE product_id = ?");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$producto) {
        echo json_encode(['success'=>false,'message'=>'Producto de origen no encontrado.']);
        exit;
    }
    if ($producto['quantity'] < $cantidad) {
        echo json_encode(['success'=>false,'message'=>'No hay suficiente stock disponible en el producto de origen.']);
        exit;
    }
    // Calcular precio unitario
    $precio_unitario = floatval($producto['price']);
    // Insertar insumo
    $stmt = $mysqli->prepare("INSERT INTO insumos (product_id, nombre, categoria, unidad, cantidad, minimo, precio_unitario, ubicacion, estado, consumo_semanal, ultima_actualizacion, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'disponible', 0, NOW(), 1)");
    $nombre_insumo = $producto['product_name'];
    // Obtener nombre de categoría
    $cat_stmt = $mysqli->prepare("SELECT name FROM categories WHERE category_id = ?");
    $cat_stmt->bind_param('i', $producto['category_id']);
    $cat_stmt->execute();
    $cat = $cat_stmt->get_result()->fetch_assoc();
    $cat_stmt->close();
    $categoria = $cat['name'] ?? '';
    $stmt->bind_param('isssddss', $product_id, $nombre_insumo, $categoria, $unidad, $cantidad, $minimo, $precio_unitario, $ubicacion);
    if ($stmt->execute()) {
        // Descontar stock del producto de origen
        $stmt2 = $mysqli->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
        $stmt2->bind_param('di', $cantidad, $product_id);
        $stmt2->execute();
        $stmt2->close();
        echo json_encode(['success'=>true,'message'=>'Insumo registrado correctamente y stock actualizado.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al registrar el insumo.']);
    }
    $stmt->close();
    exit;
}

// --- Endpoint para edición real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_insumo') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $minimo = floatval($_POST['minimo'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'pieza');
    $ubicacion = trim($_POST['ubicacion'] ?? '');
    if (!$insumo_id || $cantidad < 0 || $minimo < 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    // Obtener insumo actual
    $stmt = $mysqli->prepare("SELECT * FROM insumos WHERE insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    // Actualizar insumo
    $stmt = $mysqli->prepare("UPDATE insumos SET cantidad = ?, minimo = ?, unidad = ?, ubicacion = ?, ultima_actualizacion = NOW() WHERE insumo_id = ?");
    $stmt->bind_param('ddssi', $cantidad, $minimo, $unidad, $ubicacion, $insumo_id);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Insumo actualizado correctamente.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al actualizar el insumo.']);
    }
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insumos y Materiales | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
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
        .insumos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
        }
        .insumos-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .insumos-stats {
            display: flex;
            gap: 24px;
        }
        .stat-item {
            text-align: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 12px;
            min-width: 120px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .filters-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .insumos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .insumo-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .insumo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .insumo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .insumo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .insumo-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            line-height: 1.3;
        }
        .insumo-category {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .stock-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-disponible {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-bajo_stock {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-agotado {
            background: #ffebee;
            color: #c62828;
        }
        .insumo-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .detail-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .insumo-actions {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-movement {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-movement:hover {
            background: #7b1fa2;
            color: #fff;
        }
        .btn-report {
            background: #fff3e0;
            color: #f57c00;
        }
        .btn-report:hover {
            background: #f57c00;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        .alert-section {
            margin-bottom: 24px;
        }
        .alert-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #e53935;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
        }
        .alert-card.warning {
            border-left-color: #ffc107;
        }
        .alert-card.info {
            border-left-color: #00bcd4;
        }
        .producto-origen {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
        }
        .consumo-semanal {
            display: inline-block;
            padding: 2px 8px;
            background: #e8f5e8;
            color: #2e7d32;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .insumos-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .insumos-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .insumos-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="insumos-header">
            <div>
                <h1 class="insumos-title">
                    <i class="bi bi-box2"></i> Insumos y Materiales
                </h1>
                <p class="text-muted mb-0">Materiales de trabajo derivados de productos del catálogo</p>
            </div>
            <div class="insumos-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_insumos ?></span>
                    <span class="stat-label">Insumos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $disponibles ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $bajo_stock ?></span>
                    <span class="stat-label">Bajo Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $agotados ?></span>
                    <span class="stat-label">Agotados</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">$<?= number_format($valor_total, 0) ?></span>
                    <span class="stat-label">Valor Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $consumo_semanal_total ?></span>
                    <span class="stat-label">Consumo Sem.</span>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div class="alert-section">
            <?php 
            $agotados_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'agotado' ORDER BY i.nombre ASC");
            $bajo_stock_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'bajo_stock' ORDER BY i.nombre ASC");
            ?>
            
            <?php if ($agotados_insumos->num_rows > 0): ?>
                <div class="alert-card">
                    <h6><i class="bi bi-exclamation-triangle"></i> Insumos Agotados</h6>
                    <p class="mb-0">Los siguientes insumos están agotados y requieren reabastecimiento:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $agotados_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= htmlspecialchars($insumo['proveedor']) ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($bajo_stock_insumos->num_rows > 0): ?>
                <div class="alert-card warning">
                    <h6><i class="bi bi-exclamation-circle"></i> Stock Bajo</h6>
                    <p class="mb-0">Los siguientes insumos tienen stock bajo:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $bajo_stock_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= $insumo['cantidad'] ?> <?= $insumo['unidad'] ?> (mínimo: <?= $insumo['minimo'] ?>)</li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Buscar insumo</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Nombre, categoría, proveedor...">
                </div>
                <div class="col-md-2">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category_id']) ?>" 
                                    <?= $categoria_filtro == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select class="form-select" id="proveedor" name="proveedor">
                        <option value="">Todos</option>
                        <?php while ($prov = $proveedores->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($prov['supplier_id']) ?>" 
                                    <?= $proveedor_filtro == $prov['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="disponible" <?= $estado_filtro === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="bajo_stock" <?= $estado_filtro === 'bajo_stock' ? 'selected' : '' ?>>Bajo Stock</option>
                        <option value="agotado" <?= $estado_filtro === 'agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="insumos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                        <button type="button" class="btn btn-success" onclick="agregarInsumo()">
                            <i class="bi bi-plus-circle"></i> Agregar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($insumos->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-box2"></i>
                <h5>No se encontraron insumos</h5>
                <p>Intenta ajustar los filtros de búsqueda o agrega nuevos insumos.</p>
                <button type="button" class="btn btn-primary" onclick="agregarInsumo()">
                    <i class="bi bi-plus-circle"></i> Agregar Insumo
                </button>
            </div>
        <?php else: ?>
            <div class="insumos-grid">
                <?php while ($insumo = $insumos->fetch_assoc()): ?>
                    <div class="insumo-card">
                        <div class="insumo-header">
                            <div>
                                <h5 class="insumo-title"><?= htmlspecialchars($insumo['nombre']) ?></h5>
                                <div class="insumo-category"><?= htmlspecialchars($insumo['categoria']) ?></div>
                                <div class="producto-origen">
                                    <i class="bi bi-arrow-right"></i> Derivado de: <?= htmlspecialchars($insumo['producto_origen']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stock-status">
                            <span class="status-badge status-<?= $insumo['estado'] ?>">
                                <?php
                                switch($insumo['estado']) {
                                    case 'disponible': echo '<i class="bi bi-check-circle"></i> Disponible'; break;
                                    case 'bajo_stock': echo '<i class="bi bi-exclamation-triangle"></i> Bajo Stock'; break;
                                    case 'agotado': echo '<i class="bi bi-x-circle"></i> Agotado'; break;
                                }
                                ?>
                            </span>
                            <span class="consumo-semanal">
                                <i class="bi bi-calendar-week"></i> <?= $insumo['consumo_semanal'] ?> <?= $insumo['unidad'] ?>/sem
                            </span>
                        </div>
                        
                        <div class="insumo-details">
                            <div class="detail-item">
                                <span class="detail-number"><?= $insumo['cantidad'] ?></span>
                                <span class="detail-label">Stock (<?= $insumo['unidad'] ?>)</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number"><?= $insumo['minimo'] ?></span>
                                <span class="detail-label">Mínimo</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Precio Unit.</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['cantidad'] * $insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Valor Total</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Proveedor:</strong> <?= htmlspecialchars($insumo['proveedor']) ?> | 
                                <strong>Ubicación:</strong> <?= htmlspecialchars($insumo['ubicacion']) ?> | 
                                <strong>Última actualización:</strong> <?= date('d/m/Y', strtotime($insumo['ultima_actualizacion'])) ?>
                            </small>
                        </div>
                        
                        <div class="insumo-actions">
                            <button class="btn-action btn-edit" onclick="editarInsumo(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn-action btn-movement" onclick="registrarMovimiento(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Movimiento
                            </button>
                            <button class="btn-action btn-report" onclick="verReporteSemanal(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-graph-up"></i> Reporte Sem.
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        window.insumosData = <?= json_encode($insumos->fetch_all(MYSQLI_ASSOC)) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        function agregarInsumo() {
            // Obtener productos del catálogo desde PHP
            const productos = <?= json_encode(getProductosInventario()) ?>;
            
            // Generar opciones del select dinámicamente
            let opcionesProductos = '<option value="">-- Selecciona un producto --</option>';
            productos.forEach(producto => {
                const precioUnitario = producto.tipo_gestion === 'bobina' ? 
                    (producto.precio / 305).toFixed(2) : 
                    (producto.precio / 100).toFixed(2);
                const unidad = producto.tipo_gestion === 'bobina' ? 'metros' : 'piezas';
                opcionesProductos += `<option value="${producto.id}" data-stock="${producto.stock}" data-tipo="${producto.tipo_gestion}" data-precio="${precioUnitario}">${producto.nombre} - $${precioUnitario}/${unidad} (Stock: ${producto.stock})</option>`;
            });
            
            // Mostrar modal para seleccionar producto del catálogo
            const modal = `
                <div class="modal fade" id="modalAgregarInsumo" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Agregar Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Seleccionar producto del catálogo:</label>
                                    <select class="form-select" id="productoOrigen" onchange="actualizarInfoProducto()">
                                        ${opcionesProductos}
                                    </select>
                                </div>
                                <div id="infoProducto" class="alert alert-info" style="display:none;">
                                    <i class="bi bi-info-circle"></i>
                                    <span id="infoProductoText"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad a extraer:</label>
                                    <input type="number" class="form-control" id="cantidadExtraer" min="1" placeholder="Ej: 50 metros, 10 piezas">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock mínimo para alertas:</label>
                                    <input type="number" class="form-control" id="stockMinimo" min="0" placeholder="Ej: 20">
                                </div>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>Nota:</strong> Los insumos se crean extrayendo cantidades de productos del catálogo. 
                                    Esto permite rastrear el consumo de materiales por proyecto y mantener control del stock.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarAgregarInsumo()">
                                    <i class="bi bi-check-circle"></i> Crear Insumo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', modal);
            
            // Mostrar modal
            const modalElement = document.getElementById('modalAgregarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            
            // Limpiar modal al cerrar
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function actualizarInfoProducto() {
            const select = document.getElementById('productoOrigen');
            const infoDiv = document.getElementById('infoProducto');
            const infoText = document.getElementById('infoProductoText');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const stock = option.dataset.stock;
                const tipo = option.dataset.tipo;
                const precio = option.dataset.precio;
                const unidad = tipo === 'bobina' ? 'metros' : 'piezas';
                
                infoText.innerHTML = `<strong>Stock disponible:</strong> ${stock} ${unidad} | <strong>Precio unitario:</strong> $${precio}/${unidad} | <strong>Tipo:</strong> ${tipo}`;
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        }
        
        function confirmarAgregarInsumo() {
            const producto = document.getElementById('productoOrigen').value;
            const cantidad = document.getElementById('cantidadExtraer').value;
            const minimo = document.getElementById('stockMinimo').value;
            const unidad = document.getElementById('productoOrigen').selectedOptions[0]?.dataset.tipo === 'bobina' ? 'metros' : 'piezas';
            const ubicacion = '';
            
            if (!producto || !cantidad || !minimo) {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos.' });
                return;
            }
            
            const select = document.getElementById('productoOrigen');
            const option = select.options[select.selectedIndex];
            const stockDisponible = parseFloat(option.dataset.stock);
            const cantidadExtraer = parseFloat(cantidad);
            
            if (cantidadExtraer > stockDisponible) {
                Swal.fire({ icon: 'error', title: 'Stock insuficiente', text: 'No hay suficiente stock disponible. Stock actual: ' + stockDisponible });
                return;
            }
            
            // Enviar datos al backend vía AJAX
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'agregar_insumo',
                    product_id: producto,
                    cantidad: cantidad,
                    minimo: minimo,
                    unidad: unidad,
                    ubicacion: ubicacion
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo registrado!', text: data.message, timer: 1800, showConfirmButton: false });
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarInsumo'));
            modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el insumo. Intenta de nuevo.' });
            });
        }
        
        function editarInsumo(id) {
            // Buscar insumo real en la lista global
            const insumos = window.insumosData || [];
            const insumo = insumos.find(i => i.insumo_id == id);
            if (!insumo) {
                Swal.fire({ icon: 'error', title: 'Insumo no encontrado', text: 'No se pudo cargar la información del insumo.' });
                return;
            }
            // Modal de edición real
            const modal = `
                <div class="modal fade" id="modalEditarInsumo" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" value="${insumo.nombre}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" class="form-control" id="editCantidad" value="${insumo.cantidad}" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock mínimo</label>
                                    <input type="number" class="form-control" id="editMinimo" value="${insumo.minimo}" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Unidad</label>
                                    <input type="text" class="form-control" id="editUnidad" value="${insumo.unidad}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ubicación</label>
                                    <input type="text" class="form-control" id="editUbicacion" value="${insumo.ubicacion ?? ''}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="guardarEdicionInsumo(${id})">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalElement = document.getElementById('modalEditarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function guardarEdicionInsumo(id) {
            const cantidad = document.getElementById('editCantidad').value;
            const minimo = document.getElementById('editMinimo').value;
            const unidad = document.getElementById('editUnidad').value;
            const ubicacion = document.getElementById('editUbicacion').value;
            if (cantidad === '' || minimo === '' || unidad === '') {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos obligatorios.' });
                return;
            }
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'editar_insumo',
                    insumo_id: id,
                    cantidad: cantidad,
                    minimo: minimo,
                    unidad: unidad,
                    ubicacion: ubicacion
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo actualizado correctamente!', text: 'Los cambios han sido guardados en la base de datos.', timer: 1800, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarInsumo'));
                    modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el insumo. Intenta de nuevo.' });
            });
        }
        
        function registrarMovimiento(id) {
            alert('Función para registrar movimiento del insumo ' + id + ' (maquetado)');
        }
        
        function verReporteSemanal(id) {
            // Obtener datos del insumo desde PHP
            const insumos = <?= json_encode($insumos->fetch_all(MYSQLI_ASSOC)) ?>;
            const insumo = insumos.find(i => i.insumo_id == id);
            
            if (!insumo) {
                alert('Insumo no encontrado');
                return;
            }
            
            // Generar reporte dinámico
            const reporte = `
                <div class="modal fade" id="modalReporteSemanal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-graph-up"></i> Reporte Semanal - ${insumo.nombre}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Consumo Semanal</h6>
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <h3 class="text-primary">${insumo.consumo_semanal} ${insumo.unidad}</h3>
                                                <p class="text-muted">Promedio semanal</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Estado Actual</h6>
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <h3 class="text-${insumo.estado === 'disponible' ? 'success' : insumo.estado === 'bajo_stock' ? 'warning' : 'danger'}">${insumo.cantidad} ${insumo.unidad}</h3>
                                                <p class="text-muted">Stock disponible</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <h6>Proyectos que usaron este insumo</h6>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Instalación Cámara Casa #123</span>
                                            <span class="badge bg-primary">15 ${insumo.unidad}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Mantenimiento Sistema #456</span>
                                            <span class="badge bg-primary">20 ${insumo.unidad}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between">
                                            <span>Reparación Cable #789</span>
                                            <span class="badge bg-primary">10 ${insumo.unidad}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="mt-3">
                                    <h6>Historial de Consumo (Últimas 4 semanas)</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Semana</th>
                                                    <th>Consumo</th>
                                                    <th>Proyectos</th>
                                                    <th>Costo</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Semana 1</td>
                                                    <td>${insumo.consumo_semanal + 5} ${insumo.unidad}</td>
                                                    <td>3 proyectos</td>
                                                    <td>$${(insumo.consumo_semanal + 5) * insumo.precio_unitario}</td>
                                                </tr>
                                                <tr>
                                                    <td>Semana 2</td>
                                                    <td>${insumo.consumo_semanal} ${insumo.unidad}</td>
                                                    <td>2 proyectos</td>
                                                    <td>$${insumo.consumo_semanal * insumo.precio_unitario}</td>
                                                </tr>
                                                <tr>
                                                    <td>Semana 3</td>
                                                    <td>${insumo.consumo_semanal + 3} ${insumo.unidad}</td>
                                                    <td>4 proyectos</td>
                                                    <td>$${(insumo.consumo_semanal + 3) * insumo.precio_unitario}</td>
                                                </tr>
                                                <tr>
                                                    <td>Semana 4</td>
                                                    <td>${insumo.consumo_semanal - 5} ${insumo.unidad}</td>
                                                    <td>2 proyectos</td>
                                                    <td>$${(insumo.consumo_semanal - 5) * insumo.precio_unitario}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="alert alert-info">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Información del insumo:</strong><br>
                                        <strong>Producto origen:</strong> ${insumo.producto_origen}<br>
                                        <strong>Proveedor:</strong> ${insumo.proveedor}<br>
                                        <strong>Precio unitario:</strong> $${insumo.precio_unitario}/${insumo.unidad}<br>
                                        <strong>Stock mínimo:</strong> ${insumo.minimo} ${insumo.unidad}
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-primary">
                                    <i class="bi bi-download"></i> Exportar PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', reporte);
            
            // Mostrar modal
            const modalElement = document.getElementById('modalReporteSemanal');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            
            // Limpiar modal al cerrar
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
    </script>
</body>
</html> 