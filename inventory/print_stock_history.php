<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Obtener filtros de la URL
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';

// Construir consulta para obtener TODOS los productos con stock > 0
$query = "SELECT p.product_name, p.sku, p.quantity, c.name as categoria
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.category_id
          WHERE p.quantity > 0";

$params = [];
$types = '';

// Aplicar filtros
if ($categoria_filtro) {
    $query .= " AND c.category_id = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}

if ($busqueda) {
    $query .= " AND (p.product_name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; 
    $params[] = $like; 
    $params[] = $like;
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

$query .= " ORDER BY p.product_name ASC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
// Agregar depuración
$debug_query = $query;
foreach ($params as $i => $param) {
    $debug_query = str_replace('?', "'" . $param . "'", $debug_query);
}
error_log("DEBUG - Consulta SQL: " . $debug_query);

error_log("DEBUG - Parámetros: " . print_r($params, true));

error_log("DEBUG - Tipos: " . $types);

// Ejecutar consulta
$stmt->execute();
$productos = $stmt->get_result();

// Verificar si hay resultados
error_log("DEBUG - Número de filas: " . $productos->num_rows);

// Verificar errores en la consulta
if ($mysqli->error) {
    error_log("ERROR en la consulta: " . $mysqli->error);
}

// Obtener estadísticas generales
$stats_query = "SELECT COUNT(*) as total_productos,
    SUM(CASE WHEN quantity > 10 THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN quantity > 0 AND quantity <= 10 THEN 1 ELSE 0 END) as bajo_stock,
    SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as agotados,
    SUM(quantity) as total_unidades
    FROM products 
    WHERE quantity > 0";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Obtener nombre de categoría para el filtro
$categoria_nombre = '';
if ($categoria_filtro) {
    $cat_query = "SELECT name FROM categories WHERE category_id = ?";
    $cat_stmt = $mysqli->prepare($cat_query);
    $cat_stmt->bind_param('i', $categoria_filtro);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_row = $cat_result->fetch_assoc()) {
        $categoria_nombre = $cat_row['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Stock - Productos Disponibles</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #121866;
        }
        
        .header .subtitle {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .report-info div {
            flex: 1;
        }
        
        .report-info strong {
            color: #121866;
        }
        
        .filters-applied {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #000;
        }
        
        .filters-applied h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #1976d2;
        }
        
        .stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
        }
        
        .stat-box {
            text-align: center;
            padding: 10px;
            border: 1px solid #000;
        }
        
        .stat-number {
            font-size: 20px;
            font-weight: bold;
            color: #121866;
            display: block;
        }
        
        .stat-label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #000;
        }
        
        .products-table th,
        .products-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .products-table th {
            background: #f0f0f0;
            color: #000;
            border: 1px solid #000;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .products-table td {
            font-size: 11px;
        }
        
        .products-table tr:nth-child(even) {
            background: #f0f0f0;
        }
        
        .products-table td {
            border: 1px solid #000;
            padding: 8px;
        }
        
        .stock-high { font-weight: bold; }
        .stock-low { font-weight: bold; font-style: italic; }
        .stock-out { font-weight: bold; text-decoration: underline; }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
        }
        
        .btn {
            padding: 8px 16px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-primary {
            background: #121866;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn btn-primary" onclick="window.print()">
            🖨️ Imprimir
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            ✖️ Cerrar
        </button>
    </div>

    <div class="header">
        <h1>Historial de Stock - Productos Disponibles</h1>
        <div class="subtitle">Reporte generado el <?= date('d/m/Y H:i:s') ?></div>
    </div>

    <div class="report-info">
        <div>
            <strong>Total de productos:</strong> <?= $productos->num_rows ?>
        </div>
        <div>
            <strong>Usuario:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?>
        </div>
        <div>
            <strong>Fecha:</strong> <?= date('d/m/Y') ?>
        </div>
    </div>

    <?php if ($categoria_filtro || $busqueda || $estado_filtro): ?>
    <div class="filters-applied">
        <h3>Filtros Aplicados:</h3>
        <?php if ($categoria_nombre): ?>
            <p><strong>Categoría:</strong> <?= htmlspecialchars($categoria_nombre) ?></p>
        <?php endif; ?>
        <?php if ($busqueda): ?>
            <p><strong>Búsqueda:</strong> "<?= htmlspecialchars($busqueda) ?>"</p>
        <?php endif; ?>
        <?php if ($estado_filtro): ?>
            <p><strong>Estado:</strong> 
                <?php 
                switch($estado_filtro) {
                    case 'disponible': echo 'Disponible (Stock > 10)'; break;
                    case 'bajo_stock': echo 'Bajo Stock (1-10 unidades)'; break;
                    case 'agotado': echo 'Agotado (0 unidades)'; break;
                }
                ?>
            </p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="stats-summary">
        <div class="stat-box">
            <span class="stat-number"><?= $stats['total_productos'] ?></span>
            <div class="stat-label">Total Productos</div>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?= $stats['disponibles'] ?></span>
            <div class="stat-label">Disponibles</div>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?= $stats['bajo_stock'] ?></span>
            <div class="stat-label">Bajo Stock</div>
        </div>
        <div class="stat-box">
            <span class="stat-number"><?= $stats['total_unidades'] ?></span>
            <div class="stat-label">Total Unidades</div>
        </div>
    </div>

    <?php if ($productos->num_rows > 0): ?>
    <table class="products-table">
        <thead>
            <tr>
                <th style="width: 5%">#</th>
                <th style="width: 15%">SKU</th>
                <th style="width: 40%">Nombre del Producto</th>
                <th style="width: 30%">Categoría</th>
                <th style="width: 20%">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $contador = 1;
            while ($producto = $productos->fetch_assoc()): 
                $stock_class = '';
                if ($producto['quantity'] > 10) {
                    $stock_class = 'stock-high';
                } elseif ($producto['quantity'] > 0) {
                    $stock_class = 'stock-low';
                } else {
                    $stock_class = 'stock-out';
                }
            ?>
            <tr>
                <td><?= $contador++ ?></td>
                <td><?= htmlspecialchars($producto['sku']) ?></td>
                <td><?= htmlspecialchars($producto['product_name']) ?></td>
                <td><?= htmlspecialchars($producto['categoria'] ?? 'Sin categoría') ?></td>
                <td class="<?= $stock_class ?>"><?= number_format($producto['quantity']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align: center; padding: 40px; color: #666;">
        <h3>No se encontraron productos</h3>
        <p>No hay productos que coincidan con los filtros aplicados.</p>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Este reporte fue generado automáticamente por el Sistema de Gestión de Inventarios</p>
        <p>Fecha de generación: <?= date('d/m/Y H:i:s') ?> | Usuario: <?= htmlspecialchars($_SESSION['username'] ?? 'Usuario') ?></p>
    </div>

    <script>
        // Auto-imprimir si se especifica en la URL
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        }
    </script>
</body>
</html>
