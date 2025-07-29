<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';

// --- PAGINADO ---
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta base con información de bobinas
$query = "SELECT p.*, c.name as categoria, s.name as proveedor,
          COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
          COUNT(b.bobina_id) as total_bobinas
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
          LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
          WHERE 1=1";
$params = [];
$types = '';

if ($categoria_filtro) {
    $query .= " AND c.category_id = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}
if ($busqueda) {
    $query .= " AND (p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
if ($estado_filtro) {
    if ($estado_filtro === 'disponible') {
        $query .= " AND p.quantity > 10";
    } elseif ($estado_filtro === 'bajo_stock') {
        $query .= " AND p.quantity > 0 AND p.quantity <= 10";
    } elseif ($estado_filtro === 'agotado') {
        $query .= " AND p.quantity = 0";
    }
}
$query .= " GROUP BY p.product_id, p.product_name, p.sku, p.price, p.quantity, p.category_id, p.supplier_id, p.description, p.barcode, p.image, p.tipo_gestion, p.cost_price, p.min_stock, p.max_stock, p.unit_measure, p.is_active, p.created_at, p.updated_at, c.name, s.name ORDER BY p.product_name ASC";
$query .= " LIMIT $por_pagina OFFSET $offset";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result();

// Obtener categorías únicas
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");

// Calcular estadísticas
$stats_query = "SELECT COUNT(*) as total_productos,
    SUM(CASE WHEN quantity > 10 THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END) as bajo_stock,
    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados,
    SUM(quantity * price) as valor_total
    FROM products";
$stats = $mysqli->query($stats_query)->fetch_assoc();
$total_productos = $stats['total_productos'];
$disponibles = $stats['disponibles'];
$bajo_stock = $stats['bajo_stock'];
$agotados = $stats['agotados'];
$valor_total = $stats['valor_total'];

// Contar total de productos (con filtros)
$count_query = "SELECT COUNT(DISTINCT p.product_id) as total FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    WHERE 1=1";
$count_params = [];
$count_types = '';
if ($categoria_filtro) {
    $count_query .= " AND c.category_id = ?";
    $count_params[] = $categoria_filtro;
    $count_types .= 'i';
}
if ($busqueda) {
    $count_query .= " AND (p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $like = "%$busqueda%";
    $count_params[] = $like; $count_params[] = $like; $count_params[] = $like;
    $count_types .= 'sss';
}
if ($estado_filtro) {
    if ($estado_filtro === 'disponible') {
        $count_query .= " AND p.quantity > 10";
    } elseif ($estado_filtro === 'bajo_stock') {
        $count_query .= " AND p.quantity > 0 AND p.quantity <= 10";
    } elseif ($estado_filtro === 'agotado') {
        $count_query .= " AND p.quantity = 0";
    }
}
$stmt_count = $mysqli->prepare($count_query);
if (!empty($count_params)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_productos_filtrados = $res_count->fetch_assoc()['total'] ?? 0;
$stmt_count->close();
$total_paginas = max(1, ceil($total_productos_filtrados / $por_pagina));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario | Gestor de inventarios</title>
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
        .inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
        }
        .inventory-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .inventory-stats {
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
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .product-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .product-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .product-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            line-height: 1.3;
        }
        .product-sku {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }
        .product-category {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .product-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 8px;
            background: #f7f9fc;
            border-radius: 8px;
            border: 1px solid #e3e6f0;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
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
        .product-actions {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            flex: 1;
            padding: 10px 0;
            border-radius: 8px;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            min-height: 42px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
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
        .btn-print {
            background: #fff3cd;
            color: #856404;
        }
        .btn-print:hover {
            background: #ffe082;
            color: #6d4c00;
        }
        .btn-prices {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-prices:hover {
            background: #1565c0;
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
        .tipo-gestion-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #f0f4ff;
            color: #3f51b5;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-left: 8px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .inventory-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .inventory-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="inventory-header">
            <div>
                <h1 class="inventory-title">
                    <i class="bi bi-boxes"></i> Inventario
                </h1>
                <p class="text-muted mb-0">Stock actual de productos del catálogo</p>
            </div>
            <div class="inventory-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_productos ?></span>
                    <span class="stat-label">Productos</span>
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
            </div>
        </div>

        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Buscar producto</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Nombre, SKU, descripción...">
                </div>
                <div class="col-md-2">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php 
                        if ($categorias) {
                            while ($row = $categorias->fetch_assoc()) {
                                $selected = ($categoria_filtro == $row['category_id']) ? 'selected' : '';
                                echo "<option value='{$row['category_id']}' $selected>{$row['name']}</option>";
                            }
                        }
                        ?>
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
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <a href="../products/list.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-box-seam"></i> Catálogo
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($productos->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-boxes"></i>
                <h5>No se encontraron productos</h5>
                <p>Intenta ajustar los filtros de búsqueda o agrega productos al catálogo.</p>
                <a href="../products/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Producto
                </a>
            </div>
        <?php else: ?>
            <?php
            // --- PAGINADOR ARRIBA DEL GRID ---
            echo '<nav aria-label="Paginado de inventario" class="mt-4">';
            echo '<ul class="pagination justify-content-center">';
            $params_url = $_GET;
            for ($i = 1; $i <= $total_paginas; $i++) {
                $params_url['pagina'] = $i;
                $active = $i == $pagina ? 'active' : '';
                echo '<li class="page-item ' . $active . '"><a class="page-link paginador-inv" href="?' . http_build_query($params_url) . '">' . $i . '</a></li>';
            }
            echo '</ul></nav>';
            ?>
            <div class="product-grid">
                <?php while ($producto = $productos->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-header">
                            <div style="width:100%;text-align:center;">
                                <?php if (!empty($producto['image'])): ?>
                                    <img src="../<?= htmlspecialchars($producto['image']) ?>" alt="Imagen de producto" style="max-height:90px;max-width:95%;object-fit:contain;margin-bottom:10px;display:block;margin-left:auto;margin-right:auto;">
                                <?php else: ?>
                                    <div style="height:90px;display:flex;align-items:center;justify-content:center;background:#f4f6fb;border-radius:10px;margin-bottom:10px;">
                                        <i class="bi bi-image" style="font-size:2.5rem;color:#cfd8dc;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h5 class="product-title"><?= htmlspecialchars($producto['product_name']) ?></h5>
                                <div class="product-sku">
                                    SKU: <?= htmlspecialchars($producto['sku']) ?>
                                    <span class="tipo-gestion-badge"><?= ucfirst($producto['tipo_gestion']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-category"><?= htmlspecialchars($producto['categoria']) ?></div>
                        
                        <div class="product-description">
                            <?= htmlspecialchars($producto['description']) ?>
                        </div>
                        
                        <div class="stock-status">
                            <span class="status-badge status-<?php
                                if ($producto['tipo_gestion'] === 'bobina') {
                                    $stock = $producto['metros_totales'];
                                } else {
                                    $stock = $producto['quantity'];
                                }
                                if ($stock > 10) echo 'disponible';
                                elseif ($stock > 0) echo 'bajo_stock';
                                else echo 'agotado';
                            ?>">
                                <?php
                                if ($producto['tipo_gestion'] === 'bobina') {
                                    $stock = $producto['metros_totales'];
                                } else {
                                    $stock = $producto['quantity'];
                                }
                                if ($stock > 10) {
                                    echo '<i class="bi bi-check-circle"></i> Disponible';
                                } elseif ($stock > 0) {
                                    echo '<i class="bi bi-exclamation-triangle"></i> Bajo Stock';
                                } else {
                                    echo '<i class="bi bi-x-circle"></i> Agotado';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="product-details">
                            <div class="detail-item">
                                <div class="detail-label">Stock</div>
                                <div class="detail-value">
                                    <?php
                                    if ($producto['tipo_gestion'] === 'bobina') {
                                        echo $producto['metros_totales'] . ' m';
                                    } else {
                                        echo $producto['quantity'];
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Precio</div>
                                <div class="detail-value">$<?= number_format($producto['price'], 2) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Proveedor</div>
                                <div class="detail-value"><?= htmlspecialchars($producto['proveedor']) ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Valor Total</div>
                                <div class="detail-value">
                                    $<?= number_format(
                                        ($producto['tipo_gestion'] === 'bobina' ? $producto['metros_totales'] : $producto['quantity']) * $producto['price'], 
                                        2
                                    ) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn-action btn-edit" onclick="editarProducto(<?= $producto['product_id'] ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn-action btn-movement" onclick="registrarMovimiento(<?= $producto['product_id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Movimiento
                            </button>
                            <button class="btn-action btn-prices" onclick="buscarPrecios(<?= $producto['product_id'] ?>)">
                                <i class="bi bi-search"></i> Precios
                            </button>
                            <?php if (!empty($producto['barcode'])): ?>
                            <button class="btn-action btn-print" onclick="imprimirEtiqueta(<?= $producto['product_id'] ?>, '<?= htmlspecialchars($producto['product_name']) ?>', '<?= htmlspecialchars($producto['barcode']) ?>')">
                                <i class="bi bi-printer"></i> Imprimir
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <?php
            // --- PAGINADOR ABAJO DEL GRID ---
            echo '<nav aria-label="Paginado de inventario" class="mt-4">';
            echo '<ul class="pagination justify-content-center">';
            $params_url = $_GET;
            for ($i = 1; $i <= $total_paginas; $i++) {
                $params_url['pagina'] = $i;
                $active = $i == $pagina ? 'active' : '';
                echo '<li class="page-item ' . $active . '"><a class="page-link paginador-inv" href="?' . http_build_query($params_url) . '">' . $i . '</a></li>';
            }
            echo '</ul></nav>';
            ?>
        <?php endif; ?>
    </main>

    <!-- Modal para imprimir etiquetas -->
    <div class="modal fade" id="modalImprimirEtiqueta" tabindex="-1" aria-labelledby="modalImprimirEtiquetaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalImprimirEtiquetaLabel">
                        <i class="bi bi-printer"></i> Imprimir etiqueta de código de barras
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Producto:</strong> <span id="modalProductName"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Código de barras:</strong> <code id="modalBarcode"></code>
                    </div>
                    
                    <form id="formImprimirEtiqueta">
                        <input type="hidden" id="modalProductId">
                        <input type="hidden" id="modalBarcodeValue">
                        <input type="hidden" id="modalProductNameValue">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label for="cantidadEtiquetas" class="form-label">Cantidad de etiquetas</label>
                                <input type="number" class="form-control" id="cantidadEtiquetas" value="1" min="1" max="50">
                            </div>
                            <div class="col-md-6">
                                <label for="tamanoEtiqueta" class="form-label">Tamaño de etiqueta</label>
                                <select class="form-select" id="tamanoEtiqueta">
                                    <option value="40x20">40x20mm (Pequeña)</option>
                                    <option value="50x30" selected>50x30mm (Mediana)</option>
                                    <option value="60x40">60x40mm (Grande)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                <strong>Nota:</strong> Esta funcionalidad está optimizada para impresoras térmicas de etiquetas adhesivas.
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-outline-info" onclick="vistaPreviaEtiqueta()">
                        <i class="bi bi-eye"></i> Vista previa
                    </button>
                    <button type="button" class="btn btn-warning" onclick="imprimirEtiquetaFinal()">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-inventario').classList.add('active');
        
        function editarProducto(id) {
            window.location.href = '../products/edit.php?id=' + id;
        }
        
        function registrarMovimiento(id) {
            window.location.href = '../movements/new.php?product_id=' + id;
        }
        
        function buscarPrecios(id) {
            window.location.href = '../proveedores/buscar_producto.php?id=' + id;
        }
        
        function imprimirEtiqueta(productId, productName, barcode) {
            // Abrir modal de configuración de impresión
            const modal = document.getElementById('modalImprimirEtiqueta');
            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalBarcode').textContent = barcode;
            document.getElementById('modalProductId').value = productId;
            document.getElementById('modalBarcodeValue').value = barcode;
            document.getElementById('modalProductNameValue').value = productName;
            
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }
        
        function vistaPreviaEtiqueta() {
            const barcode = document.getElementById('modalBarcodeValue').value;
            const productName = document.getElementById('modalProductNameValue').value;
            const tamano = document.getElementById('tamanoEtiqueta').value;
            
            const url = '../products/print_labels.php?barcode=' + encodeURIComponent(barcode) + 
                       '&product_name=' + encodeURIComponent(productName) + 
                       '&cantidad=1&tamano=' + tamano;
            
            window.open(url, '_blank', 'width=600,height=800');
        }
        
        function imprimirEtiquetaFinal() {
            const barcode = document.getElementById('modalBarcodeValue').value;
            const productName = document.getElementById('modalProductNameValue').value;
            const cantidad = document.getElementById('cantidadEtiquetas').value;
            const tamano = document.getElementById('tamanoEtiqueta').value;
            
            const url = '../products/print_labels.php?barcode=' + encodeURIComponent(barcode) + 
                       '&product_name=' + encodeURIComponent(productName) + 
                       '&cantidad=' + cantidad + 
                       '&tamano=' + tamano + 
                       '&autoprint=1';
            
            window.open(url, '_blank', 'width=600,height=800');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalImprimirEtiqueta'));
            modal.hide();
        }
    </script>
</body>
</html> 