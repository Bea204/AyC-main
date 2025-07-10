<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Filtros
$filtro_cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if ($filtro_cliente) {
    $where_conditions[] = "c.cliente_nombre LIKE ?";
    $params[] = "%$filtro_cliente%";
    $param_types .= 's';
}

if ($filtro_fecha_desde) {
    $where_conditions[] = "c.fecha_cotizacion >= ?";
    $params[] = $filtro_fecha_desde;
    $param_types .= 's';
}

if ($filtro_fecha_hasta) {
    $where_conditions[] = "c.fecha_cotizacion <= ?";
    $params[] = $filtro_fecha_hasta;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Consulta principal
$query = "
    SELECT 
        c.*, 
        u.username as usuario_nombre,
        cl.nombre as cliente_nombre_real,
        cl.telefono as cliente_telefono_real,
        cl.ubicacion as cliente_direccion_real,
        COUNT(cp.cotizacion_producto_id) as total_productos,
        SUM(cp.precio_total) as subtotal_productos,
        ec.nombre_estado as estado
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    LEFT JOIN cotizaciones_productos cp ON c.cotizacion_id = cp.cotizacion_id
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    $where_clause
    GROUP BY c.cotizacion_id
    ORDER BY c.created_at DESC
";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$cotizaciones = $stmt->get_result();

// Estadísticas
$stats_query = "
    SELECT 
        COUNT(*) as total_cotizaciones,
        SUM(total) as total_valor,
        AVG(total) as promedio_valor
    FROM cotizaciones
";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizaciones | Gestor de inventarios</title>
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
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
        }
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #121866;
            margin: 0 0 8px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        .filtros-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .cotizacion-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            border-left: 4px solid #121866;
            transition: transform 0.2s ease;
        }
        .cotizacion-card:hover {
            transform: translateY(-2px);
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
        .acciones-cotizacion {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }
        .btn-accion {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            .acciones-cotizacion { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text"></i> Gestión de Cotizaciones</h2>
            <a href="crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Cotización
            </a>
        </div>

        <!-- Estadísticas -->
        <div class="stats-cards">
            <div class="stat-card">
                <h3><?= $stats['total_cotizaciones'] ?></h3>
                <p>Total Cotizaciones</p>
            </div>
            <div class="stat-card">
                <h3>$<?= number_format($stats['total_valor'] ?? 0, 2) ?></h3>
                <p>Valor Total</p>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-container">
            <h5 class="mb-3"><i class="bi bi-funnel"></i> Filtros</h5>
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" class="form-control" value="<?= htmlspecialchars($filtro_cliente) ?>" placeholder="Buscar por cliente">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?= $filtro_fecha_desde ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?= $filtro_fecha_hasta ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Lista de cotizaciones -->
        <?php if ($cotizaciones && $cotizaciones->num_rows > 0): ?>
            <?php while ($cot = $cotizaciones->fetch_assoc()): ?>
                <div class="cotizacion-card">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <h5 class="mb-1"><?= htmlspecialchars($cot['numero_cotizacion']) ?></h5>
                            <p class="text-muted mb-0"><?= htmlspecialchars($cot['cliente_nombre_real'] ?? $cot['cliente_nombre']) ?></p>
                            <small class="text-muted"><?= htmlspecialchars($cot['cliente_telefono_real'] ?? $cot['cliente_telefono']) ?></small><br>
                            <small class="text-muted"><?= htmlspecialchars($cot['cliente_direccion_real'] ?? $cot['cliente_ubicacion']) ?></small>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($cot['fecha_cotizacion'])) ?></small>
                        </div>
                        <div class="col-md-2">
                            <strong>$<?= number_format($cot['total'], 2) ?></strong>
                            <br><small class="text-muted"><?= $cot['total_productos'] ?> productos</small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Creada por:</small><br>
                            <strong><?= htmlspecialchars($cot['usuario_nombre'] ?? 'Sistema') ?></strong>
                        </div>
                        <div class="col-md-3">
                            <div class="acciones-cotizacion">
                                <a href="ver.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                                <a href="editar.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-outline-secondary">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <a href="imprimir.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-outline-info" target="_blank">
                                    <i class="bi bi-printer"></i> Imprimir
                                </a>
                                <?php if ($cot['estado'] === 'aprobada'): ?>
                                    <a href="convertir.php?id=<?= $cot['cotizacion_id'] ?>" class="btn-accion btn btn-success">
                                        <i class="bi bi-check-circle"></i> Convertir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay cotizaciones</h4>
                <p class="text-muted">Crea tu primera cotización para comenzar</p>
                <a href="crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Cotización
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
    </script>
</body>
</html> 