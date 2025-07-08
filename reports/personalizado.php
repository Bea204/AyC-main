<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Obtener estadísticas del período
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
    LEFT JOIN cotizaciones c ON DATE(c.fecha_cotizacion) BETWEEN ? AND ?
    WHERE DATE(m.movement_date) BETWEEN ? AND ?
";
$stmt = $mysqli->prepare($stats_query);
$stmt->bind_param('ssss', $fecha_inicio, $fecha_fin, $fecha_inicio, $fecha_fin);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Obtener movimientos diarios del período
$daily_movements_query = "
    SELECT 
        DATE(movement_date) as fecha,
        COUNT(CASE WHEN quantity > 0 THEN 1 END) as entradas,
        COUNT(CASE WHEN quantity < 0 THEN 1 END) as salidas
    FROM movements 
    WHERE DATE(movement_date) BETWEEN ? AND ?
    GROUP BY DATE(movement_date)
    ORDER BY fecha
";
$stmt = $mysqli->prepare($daily_movements_query);
$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$daily_movements = $stmt->get_result();

// Obtener productos más movidos
$top_products_query = "
    SELECT 
        p.product_name,
        p.sku,
        ABS(SUM(m.quantity)) as total_movimientos,
        COUNT(m.movement_id) as frecuencia
    FROM movements m
    JOIN products p ON m.product_id = p.product_id
    WHERE DATE(m.movement_date) BETWEEN ? AND ?
    GROUP BY p.product_id, p.product_name, p.sku
    ORDER BY total_movimientos DESC
    LIMIT 10
";
$stmt = $mysqli->prepare($top_products_query);
$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$top_products = $stmt->get_result();

// Obtener cotizaciones del período
$quotes_query = "
    SELECT 
        cliente_nombre,
        total,
        fecha_cotizacion
    FROM cotizaciones 
    WHERE DATE(fecha_cotizacion) BETWEEN ? AND ?
    ORDER BY fecha_cotizacion DESC
";
$stmt = $mysqli->prepare($quotes_query);
$stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
$stmt->execute();
$quotes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Personalizado | Gestor de inventarios</title>
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
        .date-selector {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
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
            <h2><i class="bi bi-calendar-range"></i> Reporte Personalizado</h2>
            <p class="mb-0">
                Del <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al <?= date('d/m/Y', strtotime($fecha_fin)) ?>
            </p>
        </div>

        <!-- Selector de fechas -->
        <div class="date-selector">
            <h5 class="section-title">
                <i class="bi bi-calendar3"></i> Seleccionar Período
            </h5>
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?= $fecha_inicio ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin" class="form-label">Fecha de fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?= $fecha_fin ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Generar Reporte
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Estadísticas del período -->
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

        <!-- Gráfica de movimientos diarios -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-bar-chart"></i> Movimientos Diarios
            </h5>
            <div class="chart-container">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <!-- Productos más movidos -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-star"></i> Productos Más Movidos
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
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
                                    <td><?= number_format($product['total_movimientos']) ?></td>
                                    <td><?= $product['frecuencia'] ?> veces</td>
                                </tr>
                            <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No hay movimientos en este período</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cotizaciones del período -->
        <div class="report-section">
            <h5 class="section-title">
                <i class="bi bi-file-earmark-text"></i> Cotizaciones del Período
            </h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($quotes && $quotes->num_rows > 0): ?>
                            <?php while ($quote = $quotes->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quote['cliente_nombre']) ?></td>
                                    <td>$<?= number_format($quote['total'], 2) ?></td>
                                    <td><?= date('d/m/Y', strtotime($quote['fecha_cotizacion'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No hay cotizaciones en este período</td>
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
            return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
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

        // Validar fechas
        document.getElementById('fecha_fin').addEventListener('change', function() {
            const fechaInicio = document.getElementById('fecha_inicio').value;
            const fechaFin = this.value;
            
            if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                alert('La fecha de inicio no puede ser mayor a la fecha de fin');
                this.value = fechaInicio;
            }
        });
    </script>
</body>
</html> 