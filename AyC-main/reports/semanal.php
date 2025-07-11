<?php
require_once '../auth/middleware.php';
require_once 'functions.php';

// Obtener período de la semana actual
$period = getReportPeriod('week');
$fecha_inicio = $period['inicio'];
$fecha_fin = $period['fin'];

// Obtener estadísticas de la semana usando las funciones auxiliares
$stats_query = "
    SELECT 
        COUNT(DISTINCT m.movement_id) as total_movements,
        COUNT(CASE WHEN m.quantity > 0 THEN 1 END) as entradas,
        COUNT(CASE WHEN m.quantity < 0 THEN 1 END) as salidas,
        COUNT(DISTINCT m.product_id) as productos_movidos,
        COUNT(DISTINCT c.cotizacion_id) as cotizaciones,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) ELSE 0 END) as unidades_vendidas,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) * p.price ELSE 0 END) as valor_ventas
    FROM movements m
    LEFT JOIN products p ON m.product_id = p.product_id
    LEFT JOIN cotizaciones c ON DATE(c.fecha_cotizacion) BETWEEN ? AND ?
    WHERE DATE(m.movement_date) BETWEEN ? AND ?
";
$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param('ssss', $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Obtener movimientos diarios de la semana
$daily_movements = getMovementsByPeriod($mysqli, $fecha_inicio, $fecha_fin);

// Obtener productos más movidos esta semana
$top_products_query = "
    SELECT 
        p.product_name,
        p.sku,
        p.price,
        c.name as category_name,
        ABS(SUM(m.quantity)) as total_movimientos,
        COUNT(m.movement_id) as frecuencia,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) * p.price ELSE 0 END) as valor_ventas
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE DATE(m.movement_date) BETWEEN ? AND ?
    GROUP BY p.product_id, p.product_name, p.sku, p.price, c.name
    ORDER BY total_movimientos DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($top_products_query);
$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$top_products = $stmt->get_result();

// Obtener cotizaciones de la semana
$quotes = getQuotesByPeriod($mysqli, $fecha_inicio, $fecha_fin);

// Obtener categorías más movidas esta semana
$top_categories_query = "
    SELECT 
        c.name as categoria,
        COUNT(m.movement_id) as movimientos,
        SUM(ABS(m.quantity)) as unidades_movidas,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) * p.price ELSE 0 END) as valor_ventas
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE DATE(m.movement_date) BETWEEN ? AND ?
    GROUP BY c.category_id, c.name
    ORDER BY movimientos DESC
    LIMIT 5
";
$stmt = $mysqli->prepare($top_categories_query);
$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$top_categories = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Semanal | Gestor de inventarios</title>
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
        .report-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 8px;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }
        .report-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .chart-container {
            height: 300px;
            margin: 20px 0;
        }
        .table th {
            background: #121866 !important;
            color: #fff !important;
            font-weight: 600;
            border: none;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="report-header">
            <h2><i class="bi bi-calendar-week"></i> Reporte Semanal</h2>
            <p class="mb-0">
                Del <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?>
            </p>
        </div>

        <!-- Estadísticas de la semana -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['total_movements'] ?? 0) ?></div>
                <div class="stat-label">Total Movimientos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['entradas'] ?? 0) ?></div>
                <div class="stat-label">Entradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['salidas'] ?? 0) ?></div>
                <div class="stat-label">Salidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['productos_movidos'] ?? 0) ?></div>
                <div class="stat-label">Productos Movidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['unidades_vendidas'] ?? 0) ?></div>
                <div class="stat-label">Unidades Vendidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatCurrency($stats['valor_ventas'] ?? 0) ?></div>
                <div class="stat-label">Valor de Ventas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatNumber($stats['cotizaciones'] ?? 0) ?></div>
                <div class="stat-label">Cotizaciones</div>
            </div>
        </div>

        <!-- Gráfica de movimientos diarios -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-bar-chart"></i> Movimientos Diarios
            </h5>
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Categorías más movidas -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-tags"></i> Categorías Más Movidas
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Categoría</th>
                            <th>Movimientos</th>
                            <th>Unidades Movidas</th>
                            <th>Valor Ventas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_categories && $top_categories->num_rows > 0): ?>
                            <?php $rank = 1; while ($category = $top_categories->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rank ?></strong></td>
                                    <td><?= htmlspecialchars($category['categoria']) ?></td>
                                    <td><?= formatNumber($category['movimientos']) ?></td>
                                    <td><?= formatNumber($category['unidades_movidas']) ?></td>
                                    <td><?= formatCurrency($category['valor_ventas']) ?></td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay movimientos esta semana</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Productos más movidos -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-star"></i> Productos Más Movidos
            </h5>
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
                        <?php if ($top_products && $top_products->num_rows > 0): ?>
                            <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rank ?></strong></td>
                                    <td><?= htmlspecialchars($product['product_name']) ?></td>
                                    <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? 'Sin categoría') ?></td>
                                    <td><?= formatNumber($product['total_movimientos']) ?></td>
                                    <td><?= $product['frecuencia'] ?> veces</td>
                                    <td><?= formatCurrency($product['valor_ventas']) ?></td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No hay movimientos esta semana</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cotizaciones de la semana -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-file-earmark-text"></i> Cotizaciones de la Semana
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($quotes && $quotes->num_rows > 0): ?>
                            <?php while ($quote = $quotes->fetch_assoc()): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($quote['numero_cotizacion']) ?></code></td>
                                    <td><?= htmlspecialchars($quote['cliente_nombre']) ?></td>
                                    <td><?= formatCurrency($quote['total']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($quote['fecha_cotizacion'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $quote['estado'] == 'Aprobada' ? 'success' : ($quote['estado'] == 'Enviada' ? 'info' : 'secondary') ?>">
                                            <?= htmlspecialchars($quote['estado']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($quote['usuario_nombre'] ?? 'Sistema') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No hay cotizaciones esta semana</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Reportes
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir Reporte
            </button>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfica de movimientos diarios
        const ctx = document.getElementById('dailyChart').getContext('2d');
        
        const dailyData = <?= json_encode($daily_movements ? $daily_movements->fetch_all(MYSQLI_ASSOC) : []) ?>;
        const labels = dailyData.map(item => {
            const date = new Date(item.fecha);
            return date.toLocaleDateString('es-ES', { weekday: 'short', day: 'numeric' });
        });
        const entradas = dailyData.map(item => parseInt(item.entradas));
        const salidas = dailyData.map(item => parseInt(item.salidas));
        
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
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html> 