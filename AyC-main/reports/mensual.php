<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Obtener mes y año actual
$mes_actual = date('Y-m');
$fecha_inicio = date('Y-m-01');
$fecha_fin = date('Y-m-t');

// Obtener estadísticas del mes
$stats_query = "
    SELECT 
        COUNT(DISTINCT m.movement_id) as total_movements,
        COUNT(CASE WHEN m.quantity > 0 THEN 1 END) as entradas,
        COUNT(CASE WHEN m.quantity < 0 THEN 1 END) as salidas,
        COUNT(DISTINCT m.product_id) as productos_movidos,
        COUNT(DISTINCT c.cotizacion_id) as cotizaciones,
        SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) ELSE 0 END) as unidades_vendidas,
        SUM(c.total) as total_ventas
    FROM movements m
    LEFT JOIN cotizaciones c ON DATE_FORMAT(c.fecha_cotizacion, '%Y-%m') = ?
    WHERE DATE_FORMAT(m.movement_date, '%Y-%m') = ?
";
$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param('ss', $mes_actual, $mes_actual);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Obtener movimientos semanales del mes
$weekly_movements_query = "
    SELECT 
        WEEK(movement_date) as semana,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as entradas,
        COUNT(CASE WHEN quantity < 0 THEN 1 END) as salidas
    FROM movements 
    WHERE DATE_FORMAT(movement_date, '%Y-%m') = ?
    GROUP BY WEEK(movement_date)
    ORDER BY semana
";
$stmt = $mysqli->prepare($weekly_movements_query);
$stmt->bind_param('s', $mes_actual);
$stmt->execute();
$weekly_movements = $stmt->get_result();

// Obtener categorías más movidas
$top_categories_query = "
    SELECT 
        c.name as categoria,
        COUNT(m.movement_id) as movimientos,
        SUM(ABS(m.quantity)) as unidades_movidas
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    JOIN categories c ON p.category_id = c.category_id
    WHERE DATE_FORMAT(m.movement_date, '%Y-%m') = ?
    GROUP BY c.category_id, c.name
    ORDER BY movimientos DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($top_categories_query);
$stmt->bind_param('s', $mes_actual);
$stmt->execute();
$top_categories = $stmt->get_result();

// Obtener proveedores más utilizados
$top_suppliers_query = "
    SELECT 
        s.name as supplier_name,
        COUNT(m.movement_id) as movimientos,
        SUM(ABS(m.quantity)) as unidades_movidas
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE DATE_FORMAT(m.movement_date, '%Y-%m') = ? 
        AND p.supplier_id IS NOT NULL
    GROUP BY p.supplier_id
    ORDER BY movimientos DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($top_suppliers_query);
$stmt->bind_param('s', $mes_actual);
$stmt->execute();
$top_suppliers = $stmt->get_result();

// Obtener cotizaciones del mes
$quotes_query = "
    SELECT 
        cliente_nombre,
        total,
        fecha_cotizacion,
        COUNT(*) OVER (PARTITION BY cliente_nombre) as cotizaciones_cliente
    FROM cotizaciones 
    WHERE DATE_FORMAT(fecha_cotizacion, '%Y-%m') = ?
    ORDER BY fecha_cotizacion DESC
";
$stmt = $mysqli->prepare($quotes_query);
$stmt->bind_param('s', $mes_actual);
$stmt->execute();
$quotes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Mensual | Gestor de inventarios</title>
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
            <h2><i class="bi bi-calendar-month"></i> Reporte Mensual</h2>
            <p class="mb-0">
                <?= date('F Y', strtotime($fecha_inicio)) ?> 
                (<?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>)
            </p>
        </div>

        <!-- Estadísticas del mes -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total_movements'] ?? 0 ?></div>
                <div class="stat-label">Total Movimientos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['entradas'] ?? 0 ?></div>
                <div class="stat-label">Entradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['salidas'] ?? 0 ?></div>
                <div class="stat-label">Salidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['productos_movidos'] ?? 0 ?></div>
                <div class="stat-label">Productos Movidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['unidades_vendidas'] ?? 0 ?></div>
                <div class="stat-label">Unidades Vendidas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($stats['total_ventas'] ?? 0, 0) ?></div>
                <div class="stat-label">Total Ventas</div>
            </div>
        </div>

        <!-- Gráfica de movimientos semanales -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-bar-chart"></i> Movimientos por Semana
            </h5>
            <div class="chart-container">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        <!-- Categorías más movidas -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-tags"></i> Categorías Más Movidas
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Categoría</th>
                            <th>Movimientos</th>
                            <th>Unidades Movidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_categories && $top_categories->num_rows > 0): ?>
                            <?php $rank = 1; while ($category = $top_categories->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rank ?></strong></td>
                                    <td><?= htmlspecialchars($category['categoria']) ?></td>
                                    <td><?= number_format($category['movimientos']) ?></td>
                                    <td><?= number_format($category['unidades_movidas']) ?></td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay movimientos este mes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Proveedores más utilizados -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-truck"></i> Proveedores Más Utilizados
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Proveedor</th>
                            <th>Movimientos</th>
                            <th>Unidades Movidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_suppliers && $top_suppliers->num_rows > 0): ?>
                            <?php $rank = 1; while ($supplier = $top_suppliers->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= $rank ?></strong></td>
                                    <td><?= htmlspecialchars($supplier['supplier_name']) ?></td>
                                    <td><?= number_format($supplier['movimientos']) ?></td>
                                    <td><?= number_format($supplier['unidades_movidas']) ?></td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay datos de proveedores este mes</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cotizaciones del mes -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-file-earmark-text"></i> Cotizaciones del Mes
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Fecha</th>
                            <th>Cotizaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($quotes && $quotes->num_rows > 0): ?>
                            <?php while ($quote = $quotes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quote['cliente_nombre']) ?></td>
                                    <td>$<?= number_format($quote['total'], 2) ?></td>
                                    <td><?= date('d/m/Y', strtotime($quote['fecha_cotizacion'])) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= $quote['cotizaciones_cliente'] ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">No hay cotizaciones este mes</td>
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
        // Gráfica de movimientos semanales
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        
        const weeklyData = <?= json_encode($weekly_movements ? $weekly_movements->fetch_all(MYSQLI_ASSOC) : []) ?>;
        const labels = weeklyData.map(item => `Semana ${item.semana}`);
        const entradas = weeklyData.map(item => parseInt(item.entradas));
        const salidas = weeklyData.map(item => parseInt(item.salidas));
        
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