<?php
require_once '../auth/middleware.php';
require_once 'functions.php';

// Obtener estadísticas usando las funciones auxiliares
$stats = getSystemStats($mysqli);
$quotes_stats = getQuotesStats($mysqli);
$bobinas_stats = getBobinasStats($mysqli);
$insumos_stats = getInsumosStats($mysqli);
$equipos_stats = getEquiposStats($mysqli);
$users_stats = getUsersStats($mysqli);

// Obtener movimientos de los últimos 6 meses
$movements = getMonthlyMovements($mysqli, 6);

// Obtener top categorías por valor
$categories = getTopCategories($mysqli, 5);

// Obtener top proveedores
$suppliers = getTopSuppliers($mysqli, 5);

// Obtener productos más vendidos
$top_products = getTopProducts($mysqli, 10);

// Obtener productos con stock bajo
$low_stock_products = getLowStockProducts($mysqli, 10);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes y Estadísticas | Gestor de inventarios</title>
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
        .titulo-lista {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .stat-icon {
            font-size: 2.5rem;
            color: #121866;
            margin-bottom: 12px;
        }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 500;
        }
        .report-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f0f0;
        }
        .report-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .table-responsive {
            border-radius: 12px;
            overflow: hidden;
        }
        .table th {
            background: #121866 !important;
            color: #fff !important;
            font-weight: 600;
            border: none;
        }
        .progress-custom {
            height: 8px;
            border-radius: 4px;
            background: #e3e6f0;
            overflow: hidden;
        }
        .progress-bar-custom {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(135deg, #121866, #232a7c);
            transition: width 0.3s ease;
        }
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        .btn-export {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-pdf {
            background: #dc3545;
            color: #fff;
        }
        .btn-pdf:hover {
            background: #c82333;
            color: #fff;
        }
        .btn-excel {
            background: #28a745;
            color: #fff;
        }
        .btn-excel:hover {
            background: #218838;
            color: #fff;
        }
        .btn-csv {
            background: #17a2b8;
            color: #fff;
        }
        .btn-csv:hover {
            background: #138496;
            color: #fff;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        .product-item:last-child {
            border-bottom: none;
        }
        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .product-rank {
            font-weight: 700;
            color: #121866;
            font-size: 1.1rem;
        }
        .product-details h6 {
            margin: 0;
            color: #121866;
            font-weight: 600;
        }
        .product-details small {
            color: #666;
        }
        .product-stats {
            text-align: right;
        }
        .product-stats .stat-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
        }
        .product-stats .stat-label {
            font-size: 0.8rem;
            color: #666;
        }
        .alert-stock {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .alert-stock .alert-title {
            font-weight: 600;
            margin-bottom: 8px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .stats-grid { grid-template-columns: 1fr; }
            .export-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-graph-up"></i> 
            Reportes y Estadísticas
        </div>

        <!-- Alertas de stock bajo -->
        <?php if ($low_stock_products && $low_stock_products->num_rows > 0): ?>
        <div class="alert-stock">
            <div class="alert-title">
                <i class="bi bi-exclamation-triangle"></i> 
                Productos con Stock Bajo
            </div>
            <div class="alert-content">
                Tienes <?= $low_stock_products->num_rows ?> productos que requieren atención inmediata.
                <a href="#low-stock-section" class="btn btn-sm btn-warning ms-2">Ver Detalles</a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <?php if (($stats['total_products'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-number"><?= formatNumber($stats['total_products'] ?? 0) ?></div>
                <div class="stat-label">Total de Productos</div>
            </div>
            <?php endif; ?>
            <?php if (($stats['total_value'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number"><?= formatCurrency($stats['total_value'] ?? 0) ?></div>
                <div class="stat-label">Valor Total del Inventario</div>
            </div>
            <?php endif; ?>
            <?php if (($stats['low_stock_products'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?= $stats['low_stock_products'] ?? 0 ?></div>
                <div class="stat-label">Productos con Stock Bajo</div>
            </div>
            <?php endif; ?>
            <?php if (($stats['out_of_stock'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['out_of_stock'] ?? 0 ?></div>
                <div class="stat-label">Productos Sin Stock</div>
            </div>
            <?php endif; ?>
            <?php if (($quotes_stats['total_quotes'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-number"><?= formatNumber($quotes_stats['total_quotes'] ?? 0) ?></div>
                <div class="stat-label">Total Cotizaciones</div>
            </div>
            <?php endif; ?>
            <?php if (($quotes_stats['unique_clients'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-number"><?= formatNumber($quotes_stats['unique_clients'] ?? 0) ?></div>
                <div class="stat-label">Clientes Únicos</div>
            </div>
            <?php endif; ?>
            <?php if (($bobinas_stats['total_bobinas'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="stat-number"><?= formatNumber($bobinas_stats['total_bobinas'] ?? 0) ?></div>
                <div class="stat-label">Total Bobinas</div>
            </div>
            <?php endif; ?>
            <?php if (($insumos_stats['total_insumos'] ?? 0) > 0): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-box2"></i>
                </div>
                <div class="stat-number"><?= formatNumber($insumos_stats['total_insumos'] ?? 0) ?></div>
                <div class="stat-label">Total Insumos</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Gráfica de movimientos mensuales -->
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-bar-chart"></i> 
                    Movimientos de los Últimos 6 Meses
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-pdf" onclick="exportToPDF('movements')">
                        <i class="bi bi-file-pdf"></i> PDF
                    </button>
                    <button class="btn-export btn-excel" onclick="exportToExcel('movements')">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="movementsChart"></canvas>
            </div>
        </div>

        <!-- Top categorías -->
        <?php if ($categories && $categories->num_rows > 0): ?>
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-tags"></i> 
                    Top Categorías por Valor
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-csv" onclick="exportToCSV('categories')">
                        <i class="bi bi-file-earmark-text"></i> CSV
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Productos</th>
                            <th>Valor Total</th>
                            <th>Precio Promedio</th>
                            <th>Porcentaje</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($category = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                                <td><?= $category['product_count'] ?></td>
                                <td><?= formatCurrency($category['total_value']) ?></td>
                                <td><?= formatCurrency($category['avg_price']) ?></td>
                                <td><?= getPercentage($category['total_value'], $stats['total_value'] ?? 1) ?>%</td>
                                <td style="width: 200px;">
                                    <div class="progress-custom">
                                        <div class="progress-bar-custom" style="width: <?= getPercentage($category['total_value'], $stats['total_value'] ?? 1) ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top proveedores -->
        <?php if ($suppliers && $suppliers->num_rows > 0): ?>
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-truck"></i> 
                    Top Proveedores
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-excel" onclick="exportToExcel('suppliers')">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Productos</th>
                            <th>Valor Total</th>
                            <th>Precio Promedio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($supplier['supplier_name']) ?></strong></td>
                                <td><?= $supplier['product_count'] ?></td>
                                <td><?= formatCurrency($supplier['total_value']) ?></td>
                                <td><?= formatCurrency($supplier['avg_price']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Productos más vendidos -->
        <?php if ($top_products && $top_products->num_rows > 0): ?>
        <div class="report-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-star"></i> 
                    Productos Más Vendidos
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-excel" onclick="exportToExcel('products')">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Categoría</th>
                            <th>Movimientos</th>
                            <th>Frecuencia</th>
                            <th>Valor Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= $rank ?></strong></td>
                                <td><?= htmlspecialchars($product['product_name']) ?></td>
                                <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?></td>
                                <td><?= formatNumber($product['total_movements']) ?></td>
                                <td><?= $product['movement_count'] ?> veces</td>
                                <td><?= formatCurrency($product['total_sales_value']) ?></td>
                            </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Productos con stock bajo -->
        <?php if ($low_stock_products && $low_stock_products->num_rows > 0): ?>
        <div class="report-section" id="low-stock-section">
            <div class="report-header">
                <h5 class="report-title">
                    <i class="bi bi-exclamation-triangle"></i> 
                    Productos con Stock Bajo
                </h5>
                <div class="export-buttons">
                    <button class="btn-export btn-excel" onclick="exportToExcel('low-stock')">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($product['product_name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                <td>
                                    <span class="badge bg-<?= $product['quantity'] == 0 ? 'danger' : 'warning' ?>">
                                        <?= $product['quantity'] ?>
                                    </span>
                                </td>
                                <td><?= $product['min_stock'] ?></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?></td>
                                <td><?= htmlspecialchars($product['supplier_name'] ?? 'Sin proveedor') ?></td>
                                <td><?= formatCurrency($product['price']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reportes adicionales -->
        <div class="row">
            <div class="col-md-6">
                <div class="report-section">
                    <div class="report-header">
                        <h5 class="report-title">
                            <i class="bi bi-calendar-event"></i> 
                            Reportes por Fecha
                        </h5>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-primary" onclick="generateReport('weekly')">
                            <i class="bi bi-calendar-week"></i> Reporte Semanal
                        </button>
                        <button class="btn btn-outline-primary" onclick="generateReport('monthly')">
                            <i class="bi bi-calendar-month"></i> Reporte Mensual
                        </button>
                        <button class="btn btn-outline-primary" onclick="generateReport('custom')">
                            <i class="bi bi-calendar-range"></i> Reporte Personalizado
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-section">
                    <div class="report-header">
                        <h5 class="report-title">
                            <i class="bi bi-gear"></i> 
                            Acciones Rápidas
                        </h5>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="../inventory/index.php" class="btn btn-outline-info">
                            <i class="bi bi-boxes"></i> Ver Inventario
                        </a>
                        <a href="../movements/index.php" class="btn btn-outline-info">
                            <i class="bi bi-arrow-left-right"></i> Ver Movimientos
                        </a>
                        <a href="../cotizaciones/index.php" class="btn btn-outline-info">
                            <i class="bi bi-file-earmark-text"></i> Ver Cotizaciones
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="../dashboard/index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </main>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-reportes').classList.add('active');
    </script>
    <script>
        // Gráfica de movimientos mensuales
        const ctx = document.getElementById('movementsChart').getContext('2d');
        
        // Preparar datos para la gráfica
        const movementsData = <?= json_encode($movements ? $movements->fetch_all(MYSQLI_ASSOC) : []) ?>;
        const labels = movementsData.map(item => {
            const date = new Date(item.month + '-01');
            return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
        });
        const entradas = movementsData.map(item => parseInt(item.entradas));
        const salidas = movementsData.map(item => parseInt(item.salidas));
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Entradas',
                        data: entradas,
                        backgroundColor: '#28a745',
                        borderColor: '#28a745',
                        borderWidth: 1
                    },
                    {
                        label: 'Salidas',
                        data: salidas,
                        backgroundColor: '#dc3545',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Funciones de exportación
        function exportToPDF(type) {
            window.open('export.php?type=' + type + '&format=pdf', '_blank');
        }

        function exportToExcel(type) {
            window.open('export.php?type=' + type + '&format=excel', '_blank');
        }

        function exportToCSV(type) {
            window.open('export.php?type=' + type + '&format=csv', '_blank');
        }

        function generateReport(type) {
            switch(type) {
                case 'weekly':
                    window.location.href = 'semanal.php';
                    break;
                case 'monthly':
                    window.location.href = 'mensual.php';
                    break;
                case 'custom':
                    window.location.href = 'personalizado.php';
                    break;
                default:
                    alert('Tipo de reporte no válido');
            }
        }
    </script>
</body>
</html> 