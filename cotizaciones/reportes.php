<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Parámetros de filtro
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy
$estado_filtro = $_GET['estado'] ?? '';
$cliente_filtro = $_GET['cliente'] ?? '';

// Construir consulta con filtros
$where_conditions = ["DATE(fecha_cotizacion) BETWEEN ? AND ?"];
$params = [$fecha_inicio, $fecha_fin];
$param_types = 'ss';

if ($estado_filtro) {
    $where_conditions[] = "estado = ?";
    $params[] = $estado_filtro;
    $param_types .= 's';
}

if ($cliente_filtro) {
    $where_conditions[] = "cliente_nombre LIKE ?";
    $params[] = "%$cliente_filtro%";
    $param_types .= 's';
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Consulta principal usando la vista existente
$query = "
    SELECT * FROM v_cotizaciones_complete 
    $where_clause
    ORDER BY fecha_cotizacion DESC
";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$cotizaciones = $stmt->get_result();

// Estadísticas generales
$stats_query = "
    SELECT 
        COUNT(*) as total_cotizaciones,
        SUM(total) as valor_total,
        AVG(total) as promedio_valor,
        COUNT(CASE WHEN estado = 'Convertida' THEN 1 END) as convertidas,
        COUNT(CASE WHEN estado = 'Aprobada' THEN 1 END) as aprobadas,
        COUNT(CASE WHEN estado = 'Rechazada' THEN 1 END) as rechazadas,
        COUNT(CASE WHEN estado = 'Enviada' THEN 1 END) as enviadas
    FROM v_cotizaciones_complete 
    $where_clause
";

$stmt_stats = $mysqli->prepare($stats_query);
if (!empty($params)) {
    $stmt_stats->bind_param($param_types, ...$params);
}
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

// Calcular tasas de conversión
$tasa_conversion = $stats['total_cotizaciones'] > 0 ? 
    round(($stats['convertidas'] / $stats['total_cotizaciones']) * 100, 2) : 0;

$tasa_aprobacion = $stats['total_cotizaciones'] > 0 ? 
    round(($stats['aprobadas'] / $stats['total_cotizaciones']) * 100, 2) : 0;

// Top 5 clientes por valor
$top_clientes_query = "
    SELECT 
        cliente_nombre,
        COUNT(*) as total_cotizaciones,
        SUM(total) as valor_total,
        AVG(total) as promedio_valor
    FROM v_cotizaciones_complete 
    $where_clause
    GROUP BY cliente_nombre
    ORDER BY valor_total DESC
    LIMIT 5
";

$stmt_clientes = $mysqli->prepare($top_clientes_query);
if (!empty($params)) {
    $stmt_clientes->bind_param($param_types, ...$params);
}
$stmt_clientes->execute();
$top_clientes = $stmt_clientes->get_result();

// Obtener estados disponibles para el filtro
$estados_disponibles = $mysqli->query("SELECT DISTINCT estado FROM v_cotizaciones_complete WHERE estado IS NOT NULL ORDER BY estado");

// --- RESPUESTA AJAX SOLO TABLA ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    include __DIR__ . '/partials/reportes_tabla.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes de Cotizaciones | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-item {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(18,24,102,0.05);
        }
        .stat-item h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #121866;
            margin: 0 0 8px 0;
        }
        .stat-item p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .filtros-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .chart-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .table-responsive {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .estado-borrador { background: #fff3cd; color: #856404; }
        .estado-enviada { background: #d1ecf1; color: #0c5460; }
        .estado-aprobada { background: #d4edda; color: #155724; }
        .estado-rechazada { background: #f8d7da; color: #721c24; }
        .estado-convertida { background: #d1ecf1; color: #0c5460; }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        /* --- SOLO IMPRESIÓN --- */
        @media print {
            .sidebar, .sidebar.collapsed, .main-content ~ .sidebar, .btn, .filtros-container, .chart-container, .stats-card .btn, .navbar, .footer {
                display: none !important;
            }
            .main-content {
                margin: 0 !important;
                width: 100vw !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            body {
                background: #fff !important;
            }
        }
        #loaderReportes { display: none; margin: 0 auto; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-graph-up"></i> Reportes de Cotizaciones</h2>
            <div>
                <button class="btn btn-outline-secondary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Imprimir Reporte
                </button>
                <a href="index.php" class="btn btn-primary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-container">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtros</h5>
            <form method="GET" class="row g-3" id="formFiltrosReportes" autocomplete="off">
                <div class="col-md-3">
                    <label class="form-label">Fecha desde</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha hasta</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= $fecha_fin ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option value="">Todos los estados</option>
                        <?php while ($estado = $estados_disponibles->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($estado['estado']) ?>" 
                                    <?= $estado_filtro === $estado['estado'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($estado['estado']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control" value="<?= htmlspecialchars($cliente_filtro) ?>" placeholder="Buscar por cliente">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filtrar
                    </button>
                    <a href="reportes.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-clockwise"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        <div id="loaderReportes">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div id="reportesContenido">
            <?php include __DIR__ . '/partials/reportes_tabla.php'; ?>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
        const formFiltros = document.getElementById('formFiltrosReportes');
        const loader = document.getElementById('loaderReportes');
        const contenido = document.getElementById('reportesContenido');
        let filtroTimeout = null;

        function buscarReportesAJAX() {
            const formData = new FormData(formFiltros);
            const params = new URLSearchParams(formData).toString();
            loader.style.display = 'block';
            contenido.style.opacity = '0.5';
            fetch('reportes.php?ajax=1&' + params)
                .then(res => res.text())
                .then(html => {
                    contenido.innerHTML = html;
                    loader.style.display = 'none';
                    contenido.style.opacity = '1';
                    renderGraficosReportes(); // Llama a la función para renderizar los gráficos
                });
        }
        // Filtro en tiempo real por cliente
        formFiltros.cliente.addEventListener('input', function() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(buscarReportesAJAX, 400);
        });
        // Filtro por fechas y estado
        formFiltros.fecha_inicio.addEventListener('change', buscarReportesAJAX);
        formFiltros.fecha_fin.addEventListener('change', buscarReportesAJAX);
        formFiltros.estado.addEventListener('change', buscarReportesAJAX);
        // Filtro por submit
        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault();
            buscarReportesAJAX();
        });
    </script>
    <script>
        function renderGraficosReportes() {
            if (typeof window.reportesCharts === 'undefined') {
                window.reportesCharts = {
                    estadosChart: null,
                    clientesChart: null
                };
            }
            // Destruir gráficos previos
            if (window.reportesCharts.estadosChart && typeof window.reportesCharts.estadosChart.destroy === 'function') {
                window.reportesCharts.estadosChart.destroy();
                window.reportesCharts.estadosChart = null;
            }
            if (window.reportesCharts.clientesChart && typeof window.reportesCharts.clientesChart.destroy === 'function') {
                window.reportesCharts.clientesChart.destroy();
                window.reportesCharts.clientesChart = null;
            }
            // Gráfico de estados
            const estadosCanvas = document.getElementById('estadosChart');
            if (estadosCanvas) {
                const estadosData = JSON.parse(estadosCanvas.getAttribute('data-estados'));
                window.reportesCharts.estadosChart = new Chart(estadosCanvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: Object.keys(estadosData),
                        datasets: [{
                            data: Object.values(estadosData),
                            backgroundColor: [
                                '#28a745', // Convertidas
                                '#17a2b8', // Aprobadas
                                '#ffc107', // Enviadas
                                '#dc3545', // Rechazadas
                                '#6c757d'  // Borrador
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }
            // Gráfico de top clientes
            const clientesCanvas = document.getElementById('clientesChart');
            if (clientesCanvas) {
                const clientesDataArr = JSON.parse(clientesCanvas.getAttribute('data-clientes'));
                const clientesLabels = clientesDataArr.map(c => c.nombre);
                const clientesData = clientesDataArr.map(c => c.valor);
                window.reportesCharts.clientesChart = new Chart(clientesCanvas.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: clientesLabels,
                        datasets: [{
                            label: 'Valor Total ($)',
                            data: clientesData,
                            backgroundColor: '#121866',
                            borderColor: '#232a7c',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
        // Renderizar gráficos en la primera carga
        renderGraficosReportes();
    </script>
</body>
</html> 