<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Obtener estadísticas reales del sistema
$stats_query = "
    SELECT 
        COUNT(*) as total_products,
        SUM(quantity * price) as total_value,
        COUNT(CASE WHEN quantity <= 10 AND quantity > 0 THEN 1 END) as low_stock_products,
        COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
        COUNT(CASE WHEN quantity > 10 THEN 1 END) as good_stock_products
    FROM products
";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// Obtener movimientos de los últimos 6 meses
$movements_query = "
    SELECT 
        DATE_FORMAT(movement_date, '%Y-%m') as month,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as entradas,
        COUNT(CASE WHEN quantity < 0 THEN 1 END) as salidas
    FROM movements 
    WHERE movement_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(movement_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
";
$movements = $mysqli->query($movements_query);

// Obtener top categorías por valor
$categories_query = "
    SELECT 
        c.name as category_name,
        COUNT(p.product_id) as product_count,
        SUM(p.quantity * p.price) as total_value
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    WHERE p.product_id IS NOT NULL
    GROUP BY c.category_id, c.name
    HAVING total_value > 0
    ORDER BY total_value DESC
    LIMIT 5
";
$categories = $mysqli->query($categories_query);

// Obtener top proveedores
$suppliers_query = "
    SELECT 
        s.name as supplier_name,
        COUNT(p.product_id) as product_count,
        SUM(p.quantity * p.price) as total_value
    FROM products p
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE p.supplier_id IS NOT NULL
    GROUP BY p.supplier_id
    HAVING total_value > 0
    ORDER BY total_value DESC
    LIMIT 5
";
$suppliers = $mysqli->query($suppliers_query);

// Obtener productos más vendidos (por movimientos de salida)
$top_products_query = "
    SELECT 
        p.product_name,
        p.sku,
        ABS(SUM(m.quantity)) as total_movements,
        COUNT(m.movement_id) as movement_count
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    WHERE m.quantity < 0
    GROUP BY p.product_id, p.product_name, p.sku
    ORDER BY total_movements DESC
    LIMIT 10
";
$top_products = $mysqli->query($top_products_query);

// Obtener estadísticas de cotizaciones
$quotes_stats_query = "
    SELECT 
        COUNT(*) as total_quotes,
        COUNT(DISTINCT cliente_id) as unique_clients,
        SUM(total) as total_sales,
        AVG(total) as avg_quote_value
    FROM cotizaciones
";
$quotes_stats = $mysqli->query($quotes_stats_query)->fetch_assoc();
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

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-number"><?= number_format($stats['total_products'] ?? 0) ?></div>
                <div class="stat-label">Total de Productos</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-number">$<?= number_format($stats['total_value'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Valor Total del Inventario</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div class="stat-number"><?= $stats['low_stock_products'] ?? 0 ?></div>
                <div class="stat-label">Productos con Stock Bajo</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-number"><?= $stats['out_of_stock'] ?? 0 ?></div>
                <div class="stat-label">Productos Sin Stock</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-number"><?= number_format($quotes_stats['total_quotes'] ?? 0) ?></div>
                <div class="stat-label">Total Cotizaciones</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-number"><?= number_format($quotes_stats['unique_clients'] ?? 0) ?></div>
                <div class="stat-label">Clientes Únicos</div>
            </div>
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
                            <th>Porcentaje</th>
                            <th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories && $categories->num_rows > 0): ?>
                            <?php while ($category = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($category['category_name']) ?></strong></td>
                                    <td><?= $category['product_count'] ?></td>
                                    <td>$<?= number_format($category['total_value'], 0, ',', '.') ?></td>
                                    <td><?= round(($category['total_value'] / ($stats['total_value'] ?? 1)) * 100, 1) ?>%</td>
                                    <td style="width: 200px;">
                                        <div class="progress-custom">
                                            <div class="progress-bar-custom" style="width: <?= ($category['total_value'] / ($stats['total_value'] ?? 1)) * 100 ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay datos de categorías disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top proveedores -->
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
                            <th>Promedio por Producto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($suppliers && $suppliers->num_rows > 0): ?>
                            <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($supplier['supplier_name']) ?></strong></td>
                                    <td><?= $supplier['product_count'] ?></td>
                                    <td>$<?= number_format($supplier['total_value'], 0, ',', '.') ?></td>
                                    <td>$<?= number_format($supplier['total_value'] / $supplier['product_count'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay datos de proveedores disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Productos más vendidos -->
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
                            <th>Movimientos</th>
                            <th>Frecuencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_products && $top_products->num_rows > 0): ?>
                            <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rank ?></strong></td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                    <td><?= number_format($product['total_movements']) ?></td>
                                    <td><?= $product['movement_count'] ?> veces</td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay datos de productos vendidos disponibles</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

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
            alert('Función de exportación a PDF en desarrollo');
        }

        function exportToExcel(type) {
            alert('Función de exportación a Excel en desarrollo');
        }

        function exportToCSV(type) {
            alert('Función de exportación a CSV en desarrollo');
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