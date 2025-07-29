<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// Filtros
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_accion = isset($_GET['accion']) ? trim($_GET['accion']) : '';
$filtro_numero = isset($_GET['numero']) ? trim($_GET['numero']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

$where = [];
$params = [];
$types = '';

if ($filtro_usuario) {
    $where[] = 'realizado_por LIKE ?';
    $params[] = "%$filtro_usuario%";
    $types .= 's';
}
if ($filtro_accion) {
    $where[] = 'nombre_accion = ?';
    $params[] = $filtro_accion;
    $types .= 's';
}
if ($filtro_numero) {
    $where[] = 'numero_cotizacion LIKE ?';
    $params[] = "%$filtro_numero%";
    $types .= 's';
}
if ($filtro_fecha_desde) {
    $where[] = 'DATE(fecha_accion) >= ?';
    $params[] = $filtro_fecha_desde;
    $types .= 's';
}
if ($filtro_fecha_hasta) {
    $where[] = 'DATE(fecha_accion) <= ?';
    $params[] = $filtro_fecha_hasta;
    $types .= 's';
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "SELECT * FROM v_cotizaciones_historial $where_sql ORDER BY fecha_accion DESC LIMIT 500";
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$historial = $stmt->get_result();

// Resumen por usuario
$resumen_usuarios = $mysqli->query("SELECT realizado_por, COUNT(*) as total FROM v_cotizaciones_historial $where_sql GROUP BY realizado_por ORDER BY total DESC LIMIT 10");
// Resumen por acción
$resumen_acciones = $mysqli->query("SELECT nombre_accion, COUNT(*) as total FROM v_cotizaciones_historial $where_sql GROUP BY nombre_accion ORDER BY total DESC");

// Para los selects de acciones y usuarios
$acciones = $mysqli->query("SELECT DISTINCT nombre_accion FROM v_cotizaciones_historial ORDER BY nombre_accion");
$usuarios = $mysqli->query("SELECT DISTINCT realizado_por FROM v_cotizaciones_historial WHERE realizado_por IS NOT NULL AND realizado_por <> '' ORDER BY realizado_por");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Auditoría de Cotizaciones | Gestor de inventarios</title>
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
        .filtros-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .table-responsive {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .badge-accion {
            font-size: 0.95rem;
            border-radius: 8px;
            padding: 6px 14px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .badge-Creada { background: #43a047; color: #fff; }
        .badge-Enviada { background: #17a2b8; color: #fff; }
        .badge-Aprobada { background: #ffc107; color: #232a7c; }
        .badge-Rechazada { background: #e53935; color: #fff; }
        .badge-Convertida { background: #121866; color: #fff; }
        .badge-Modificada { background: #6c757d; color: #fff; }
        .resumen-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 24px;
        }
        .resumen-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
            padding: 16px 18px;
            min-width: 180px;
            flex: 1 1 180px;
        }
        .resumen-card h6 { font-size: 1rem; color: #121866; margin-bottom: 8px; font-weight: 700; }
        .resumen-card ul { list-style: none; padding: 0; margin: 0; }
        .resumen-card li { font-size: 0.98rem; color: #232a7c; margin-bottom: 4px; }
        @media (max-width: 900px) {
            .main-content { width: calc(100vw - 70px); margin-left: 70px; padding: 16px; }
            .resumen-cards { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-check"></i> Auditoría de Cotizaciones</h2>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
        </div>
        <div class="filtros-container mb-4" style="background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(18,24,102,0.07); padding:20px;">
            <form method="GET" class="row g-3 align-items-end" autocomplete="off">
                <div class="col-md-3 col-12">
                    <label class="form-label">Cotización</label>
                    <input type="text" name="numero" class="form-control" value="<?= htmlspecialchars($filtro_numero) ?>" placeholder="Número">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" class="form-select">
                        <option value="">Todos</option>
                        <?php while ($u = $usuarios->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($u['realizado_por']) ?>" <?= $filtro_usuario === $u['realizado_por'] ? 'selected' : '' ?>><?= htmlspecialchars($u['realizado_por']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Acción</label>
                    <select name="accion" class="form-select">
                        <option value="">Todas</option>
                        <?php while ($a = $acciones->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($a['nombre_accion']) ?>" <?= $filtro_accion === $a['nombre_accion'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nombre_accion']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                </div>
                <div class="col-md-3 col-12">
                    <div class="d-flex gap-2 align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
                        <a href="reporte_auditoria.php" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Limpiar</a>
                    </div>
                </div>
            </form>
        </div>
        <div class="resumen-cards">
            <div class="resumen-card">
                <h6><i class="bi bi-person"></i> Acciones por usuario</h6>
                <ul>
                    <?php if ($resumen_usuarios && $resumen_usuarios->num_rows > 0):
                        while ($ru = $resumen_usuarios->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($ru['realizado_por']) ?>:</strong> <?= $ru['total'] ?></li>
                        <?php endwhile;
                    else: ?>
                        <li class="text-muted">Sin datos</li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="resumen-card">
                <h6><i class="bi bi-list-check"></i> Acciones por tipo</h6>
                <ul>
                    <?php if ($resumen_acciones && $resumen_acciones->num_rows > 0):
                        while ($ra = $resumen_acciones->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($ra['nombre_accion']) ?>:</strong> <?= $ra['total'] ?></li>
                        <?php endwhile;
                    else: ?>
                        <li class="text-muted">Sin datos</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Número Cotización</th>
                        <th>Acción</th>
                        <th>Comentario</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i=1;
                    if ($historial->num_rows > 0):
                        while ($h = $historial->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><a href="ver.php?id=<?= urlencode(explode('-', $h['numero_cotizacion'])[2] ?? '') ?>" class="text-decoration-none"><?= htmlspecialchars($h['numero_cotizacion']) ?></a></td>
                            <td><span class="badge badge-accion badge-<?= htmlspecialchars($h['nombre_accion']) ?>"><?= htmlspecialchars($h['nombre_accion']) ?></span></td>
                            <td><?= htmlspecialchars($h['comentario']) ?></td>
                            <td><?= htmlspecialchars($h['realizado_por']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($h['fecha_accion'])) ?></td>
                        </tr>
                        <?php endwhile;
                    else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-info-circle" style="font-size:2rem;"></i><br>
                                No hay acciones registradas para los filtros seleccionados.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>document.querySelector('.sidebar-auditoria-cotizaciones').classList.add('active');</script>
</body>
</html> 