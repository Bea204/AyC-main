<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);

// Obtener información de la cotización
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, cl.nombre as cliente_nombre_real, ec.nombre_estado
    FROM cotizaciones c
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    WHERE c.cotizacion_id = ?
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

if (!$cotizacion) {
    header('Location: index.php');
    exit;
}

require_once 'helpers.php';

// Obtener historial de la cotización
$historial = obtenerHistorialCotizacion($cotizacion_id, $mysqli);

// Obtener estadísticas del historial
$stats_historial = obtenerStatsHistorialCotizacion($cotizacion_id, $mysqli);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial - Cotización <?= $cotizacion['numero_cotizacion'] ?> | Gestor de inventarios</title>
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
        .historial-card {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e0e0e0;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #121866;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #121866;
            border: 3px solid #fff;
        }
        .accion-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 8px;
        }
        .accion-creada { background: #d4edda; color: #155724; }
        .accion-enviada { background: #d1ecf1; color: #0c5460; }
        .accion-aprobada { background: #d4edda; color: #155724; }
        .accion-rechazada { background: #f8d7da; color: #721c24; }
        .accion-convertida { background: #d1ecf1; color: #0c5460; }
        .accion-modificada { background: #fff3cd; color: #856404; }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(18,24,102,0.05);
        }
        .stat-card h4 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #121866;
            margin: 0 0 4px 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clock-history"></i> Historial de Cotización</h2>
            <a href="ver.php?id=<?= $cotizacion_id ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Cotización
            </a>
        </div>

        <!-- Información de la cotización -->
        <div class="historial-card">
            <h5 class="mb-3"><i class="bi bi-info-circle"></i> Información de la Cotización</h5>
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Número:</strong> <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></p>
                    <p><strong>Cliente:</strong> <?= htmlspecialchars($cotizacion['cliente_nombre_real']) ?></p>
                    <p><strong>Total:</strong> $<?= number_format($cotizacion['total'], 2) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Estado actual:</strong> <span class="badge bg-primary"><?= htmlspecialchars($cotizacion['nombre_estado']) ?></span></p>
                    <p><strong>Fecha de creación:</strong> <?= date('d/m/Y H:i', strtotime($cotizacion['created_at'])) ?></p>
                    <p><strong>Creada por:</strong> <?= htmlspecialchars($cotizacion['usuario_nombre'] ?? 'Sistema') ?></p>
                </div>
            </div>
        </div>

        <!-- Estadísticas del historial -->
        <div class="stats-cards">
            <div class="stat-card">
                <h4><?= $stats_historial['total_acciones'] ?></h4>
                <p>Total de Acciones</p>
            </div>
            <div class="stat-card">
                <h4><?= $stats_historial['usuarios_involucrados'] ?></h4>
                <p>Usuarios Involucrados</p>
            </div>
            <div class="stat-card">
                <h4><?= $stats_historial['primera_accion'] ? date('d/m/Y', strtotime($stats_historial['primera_accion'])) : 'N/A' ?></h4>
                <p>Primera Acción</p>
            </div>
            <div class="stat-card">
                <h4><?= $stats_historial['ultima_accion'] ? date('d/m/Y', strtotime($stats_historial['ultima_accion'])) : 'N/A' ?></h4>
                <p>Última Acción</p>
            </div>
        </div>

        <!-- Timeline del historial -->
        <div class="historial-card">
            <h5 class="mb-3"><i class="bi bi-list-ul"></i> Cronología de Acciones</h5>
            
            <?php if ($historial && $historial->num_rows > 0): ?>
                <div class="timeline">
                    <?php while ($accion = $historial->fetch_assoc()): ?>
                        <div class="timeline-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <span class="accion-badge accion-<?= strtolower($accion['nombre_accion']) ?>">
                                        <?= htmlspecialchars($accion['nombre_accion']) ?>
                                    </span>
                                    <p class="mb-2"><strong><?= htmlspecialchars($accion['comentario']) ?></strong></p>
                                    <small class="text-muted">
                                        <i class="bi bi-person"></i> 
                                        <?= htmlspecialchars($accion['usuario_nombre'] ?? 'Sistema') ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i', strtotime($accion['fecha_accion'])) ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-clock-history" style="font-size: 3rem; color: #ccc;"></i>
                    <h5 class="mt-3">No hay historial disponible</h5>
                    <p class="text-muted">Esta cotización aún no tiene acciones registradas en el historial.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');
    </script>
</body>
</html>