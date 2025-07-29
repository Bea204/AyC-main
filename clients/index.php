<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// --- FILTROS BACKEND ---
$filtro_nombre = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$where = [];
$having = [];
$params = [];
$types = '';
if ($filtro_nombre) {
    $where[] = '(cl.nombre LIKE ? OR cl.telefono LIKE ? OR cl.ubicacion LIKE ? OR cl.email LIKE ?)';
    $like = "%$filtro_nombre%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'ssss';
}
if ($filtro_estado) {
    if ($filtro_estado === 'reciente') {
        $having[] = 'MAX(c.fecha_cotizacion) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
    } elseif ($filtro_estado === 'activo') {
        $having[] = 'COUNT(c.cotizacion_id) > 1';
    } elseif ($filtro_estado === 'inactivo') {
        $having[] = '(MAX(c.fecha_cotizacion) < DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR MAX(c.fecha_cotizacion) IS NULL)';
    }
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$having_sql = $having ? 'HAVING ' . implode(' AND ', $having) : '';
$query = "
    SELECT 
        cl.cliente_id,
        cl.nombre AS cliente_nombre,
        cl.telefono AS cliente_telefono,
        cl.ubicacion AS cliente_ubicacion,
        cl.email AS cliente_email,
        COUNT(c.cotizacion_id) as total_cotizaciones,
        SUM(c.total) as total_ventas,
        MAX(c.fecha_cotizacion) as ultima_cotizacion,
        MIN(c.fecha_cotizacion) as primera_cotizacion
    FROM clientes cl
    LEFT JOIN cotizaciones c ON cl.cliente_id = c.cliente_id
    $where_sql
    GROUP BY cl.cliente_id, cl.nombre, cl.telefono, cl.ubicacion, cl.email
    $having_sql
    ORDER BY ultima_cotizacion DESC
";
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$clientes = $stmt->get_result();

// Estadísticas generales
$stats_query = "
    SELECT 
        COUNT(*) as total_clientes,
        (SELECT COUNT(*) FROM cotizaciones) as total_cotizaciones,
        (SELECT SUM(total) FROM cotizaciones) as total_ventas,
        (SELECT AVG(total) FROM cotizaciones) as promedio_venta
    FROM clientes
";
$stats = $mysqli->query($stats_query)->fetch_assoc();

// --- PROCESAMIENTO DE FORMULARIO SOLO PARA ALTA DE CLIENTES (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $nombre = trim($_POST['nombre']);
    $telefono = trim($_POST['telefono']);
    $ubicacion = trim($_POST['ubicacion']);
    $email = trim($_POST['email']);
    $success = $error = '';
    if ($nombre) {
        // Evitar duplicados inmediatos por nombre y teléfono/email
        $stmt = $mysqli->prepare("SELECT COUNT(*) FROM clientes WHERE nombre = ? AND (telefono = ? OR email = ?)");
        $stmt->bind_param('sss', $nombre, $telefono, $email);
        $stmt->execute();
        $stmt->bind_result($existe);
        $stmt->fetch();
        $stmt->close();
        if ($existe > 0) {
            $error = 'Ya existe un cliente con ese nombre y teléfono/email.';
        } else {
            $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $nombre, $telefono, $ubicacion, $email);
            if ($stmt->execute()) {
                $success = 'Cliente agregado correctamente.';
            } else {
                $error = 'Error al agregar cliente: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = 'El nombre es obligatorio.';
    }
    // Respuesta AJAX para alta
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        // Reconsultar clientes tras la acción
        $query = "
            SELECT 
                cl.cliente_id,
                cl.nombre AS cliente_nombre,
                cl.telefono AS cliente_telefono,
                cl.ubicacion AS cliente_ubicacion,
                cl.email AS cliente_email,
                COUNT(c.cotizacion_id) as total_cotizaciones,
                SUM(c.total) as total_ventas,
                MAX(c.fecha_cotizacion) as ultima_cotizacion,
                MIN(c.fecha_cotizacion) as primera_cotizacion
            FROM clientes cl
            LEFT JOIN cotizaciones c ON cl.cliente_id = c.cliente_id
            GROUP BY cl.cliente_id, cl.nombre, cl.telefono, cl.ubicacion, cl.email
            ORDER BY ultima_cotizacion DESC
        ";
        $clientes = $mysqli->query($query);
        ob_start();
        include __DIR__ . '/partials/clientes_listado.php';
        $clientes_html = ob_get_clean();
        echo json_encode([
            'success' => $success,
            'error' => $error,
            'clientes_html' => $clientes_html
        ]);
        exit;
    }
}

// --- PROCESAMIENTO DE FORMULARIOS ABC CLIENTES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        if ($_POST['accion'] === 'editar' && isset($_POST['cliente_id'])) {
            $cliente_id = intval($_POST['cliente_id']);
            $nombre = trim($_POST['nombre']);
            $telefono = trim($_POST['telefono']);
            $ubicacion = trim($_POST['ubicacion']);
            $email = trim($_POST['email']);
            if ($cliente_id && $nombre) {
                $stmt = $mysqli->prepare("UPDATE clientes SET nombre=?, telefono=?, ubicacion=?, email=? WHERE cliente_id=?");
                $stmt->bind_param('ssssi', $nombre, $telefono, $ubicacion, $email, $cliente_id);
                if ($stmt->execute()) {
                    $success = 'Cliente actualizado correctamente.';
                } else {
                    $error = 'Error al actualizar cliente: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Datos incompletos para editar.';
            }
        } elseif ($_POST['accion'] === 'eliminar' && isset($_POST['cliente_id'])) {
            $cliente_id = intval($_POST['cliente_id']);
            if ($cliente_id) {
                $stmt = $mysqli->prepare("DELETE FROM clientes WHERE cliente_id = ?");
                $stmt->bind_param('i', $cliente_id);
                if ($stmt->execute()) {
                    $success = 'Cliente eliminado correctamente.';
                } else {
                    $error = 'Error al eliminar cliente: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = 'Cliente no válido.';
            }
        }
    }
    // --- RESPUESTA AJAX PARA ALTA/EDICIÓN/ELIMINACIÓN ---
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        // Reconsultar clientes tras la acción
        $filtro_nombre = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
        $filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
        $where = [];
        $having = [];
        $params = [];
        $types = '';
        if ($filtro_nombre) {
            $where[] = '(cl.nombre LIKE ? OR cl.telefono LIKE ? OR cl.ubicacion LIKE ? OR cl.email LIKE ?)';
            $like = "%$filtro_nombre%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            $types .= 'ssss';
        }
        if ($filtro_estado) {
            if ($filtro_estado === 'reciente') {
                $having[] = 'MAX(c.fecha_cotizacion) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            } elseif ($filtro_estado === 'activo') {
                $having[] = 'COUNT(c.cotizacion_id) > 1';
            } elseif ($filtro_estado === 'inactivo') {
                $having[] = '(MAX(c.fecha_cotizacion) < DATE_SUB(CURDATE(), INTERVAL 30 DAY) OR MAX(c.fecha_cotizacion) IS NULL)';
            }
        }
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $having_sql = $having ? 'HAVING ' . implode(' AND ', $having) : '';
        $query = "
            SELECT 
                cl.cliente_id,
                cl.nombre AS cliente_nombre,
                cl.telefono AS cliente_telefono,
                cl.ubicacion AS cliente_ubicacion,
                cl.email AS cliente_email,
                COUNT(c.cotizacion_id) as total_cotizaciones,
                SUM(c.total) as total_ventas,
                MAX(c.fecha_cotizacion) as ultima_cotizacion,
                MIN(c.fecha_cotizacion) as primera_cotizacion
            FROM clientes cl
            LEFT JOIN cotizaciones c ON cl.cliente_id = c.cliente_id
            $where_sql
            GROUP BY cl.cliente_id, cl.nombre, cl.telefono, cl.ubicacion, cl.email
            $having_sql
            ORDER BY ultima_cotizacion DESC
        ";
        $stmt = $mysqli->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $clientes = $stmt->get_result();
        ob_start();
        include __DIR__ . '/partials/clientes_listado.php';
        $clientes_html = ob_get_clean();
        echo json_encode([
            'success' => $success,
            'error' => $error,
            'clientes_html' => $clientes_html
        ]);
        exit;
    }
}

// --- RESPUESTA AJAX PARA FILTROS ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    ob_start();
    include __DIR__ . '/partials/clientes_listado.php';
    $clientes_html = ob_get_clean();
    echo json_encode([
        'clientes_html' => $clientes_html
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes | Gestor de inventarios</title>
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
        .stats-header {
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
            margin-top: 20px;
        }
        .stat-card {
            background: #232a7c; /* Color sólido, sin degradado */
            color: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(35,42,124,0.07);
            border: 1.5px solid #e3e6f0;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .stat-card:hover {
            box-shadow: 0 6px 24px rgba(35,42,124,0.13);
            transform: translateY(-2px) scale(1.03);
        }
        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
            color: #fff;
            letter-spacing: 1px;
        }
        .stat-label {
            font-size: 1rem;
            opacity: 0.93;
            color: #e3e6fa;
            font-weight: 500;
        }
        .search-section {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .client-card {
            background: #fff;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(35,42,124,0.07);
            border: 1.5px solid #e3e6f0;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .client-card:hover {
            box-shadow: 0 8px 32px rgba(35,42,124,0.13);
            transform: translateY(-2px) scale(1.01);
            border-color: #232a7c;
        }
        .client-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #232a7c;
        }
        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .client-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #232a7c;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .client-contact {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .contact-icon {
            color: #667eea;
            font-size: 1.1rem;
        }
        .contact-text {
            color: #232a7c;
            font-size: 0.98rem;
        }
        .client-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        .stat-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .stat-item .stat-number {
            font-size: 1.2rem;
            font-weight: 700;
            color: #232a7c;
            display: block;
            margin-bottom: 4px;
        }
        .stat-item .stat-label {
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 500;
        }
        .client-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
        }
        .btn-action {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
        }
        .btn-view {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-view:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-cotizar {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .btn-cotizar:hover {
            background: #2e7d32;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .client-contact {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Gestión de Clientes</h2>
            <a href="../cotizaciones/crear.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nueva Cotización
            </a>
        </div>

        <!-- Estadísticas generales -->
        <div class="stats-header">
            <h5><i class="bi bi-graph-up"></i> Resumen de Clientes</h5>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_clientes'] ?? 0 ?></span>
                    <span class="stat-label">Clientes Únicos</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_cotizaciones'] ?? 0 ?></span>
                    <span class="stat-label">Total Cotizaciones</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?= number_format($stats['total_ventas'] ?? 0, 0) ?></span>
                    <span class="stat-label">Total Ventas</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">$<?= number_format($stats['promedio_venta'] ?? 0, 0) ?></span>
                    <span class="stat-label">Promedio por Cotización</span>
                </div>
            </div>
        </div>

        <!-- Búsqueda y filtros -->
        <div class="search-section">
            <form id="formFiltroClientes" class="row g-2" method="GET" autocomplete="off">
                <div class="col-md-8">
                    <input type="text" id="searchClient" name="busqueda" class="form-control" placeholder="Buscar cliente por nombre, teléfono, ubicación o email..." value="<?= htmlspecialchars($filtro_nombre) ?>">
                </div>
                <div class="col-md-4">
                    <select id="filterStatus" name="estado" class="form-select">
                        <option value="">Todos los clientes</option>
                        <option value="reciente" <?= $filtro_estado === 'reciente' ? 'selected' : '' ?>>Clientes recientes (últimos 30 días)</option>
                        <option value="activo" <?= $filtro_estado === 'activo' ? 'selected' : '' ?>>Clientes activos (más de 1 cotización)</option>
                        <option value="inactivo" <?= $filtro_estado === 'inactivo' ? 'selected' : '' ?>>Clientes inactivos (sin cotizaciones recientes)</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- Lista de clientes -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <!-- Reemplazar la barra superior para que solo muestre el botón, sin el texto 'ABC de clientes' -->
        <div class="mb-3 d-flex justify-content-end align-items-center">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente"><i class="bi bi-person-plus"></i> Agregar cliente</button>
        </div>
        <?php if ($clientes && $clientes->num_rows > 0): ?>
            <div id="clientsContainer">
                <?php while ($cliente = $clientes->fetch_assoc()): ?>
                    <div class="client-card" data-client-name="<?= strtolower($cliente['cliente_nombre']) ?>" data-client-phone="<?= strtolower($cliente['cliente_telefono']) ?>" data-client-location="<?= strtolower($cliente['cliente_ubicacion']) ?>">
                        <div class="client-header">
                            <h5 class="client-name">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($cliente['cliente_nombre']) ?>
                            </h5>
                            <span class="badge bg-<?= $cliente['total_cotizaciones'] > 1 ? 'success' : 'info' ?>">
                                <?= $cliente['total_cotizaciones'] ?> cotización<?= $cliente['total_cotizaciones'] != 1 ? 'es' : '' ?>
                            </span>
                        </div>
                        
                        <div class="client-contact">
                            <?php if ($cliente['cliente_telefono']): ?>
                                <div class="contact-item">
                                    <i class="bi bi-telephone contact-icon"></i>
                                    <span class="contact-text"><?= htmlspecialchars($cliente['cliente_telefono']) ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($cliente['cliente_ubicacion']): ?>
                                <div class="contact-item">
                                    <i class="bi bi-geo-alt contact-icon"></i>
                                    <span class="contact-text"><?= htmlspecialchars($cliente['cliente_ubicacion']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="client-stats">
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['total_cotizaciones'] ?></span>
                                <span class="stat-label">Cotizaciones</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number">$<?= number_format($cliente['total_ventas'] ?? 0, 0) ?></span>
                                <span class="stat-label">Total Ventas</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['ultima_cotizacion'] ? date('d/m/Y', strtotime($cliente['ultima_cotizacion'])) : 'N/A' ?></span>
                                <span class="stat-label">Última Cotización</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $cliente['primera_cotizacion'] ? date('d/m/Y', strtotime($cliente['primera_cotizacion'])) : 'N/A' ?></span>
                                <span class="stat-label">Primera Cotización</span>
                            </div>
                        </div>
                        
                        <div class="client-actions">
                            <a href="../cotizaciones/index.php?cliente=<?= urlencode($cliente['cliente_nombre']) ?>" class="btn-action btn-view">
                                <i class="bi bi-eye"></i> Ver Cotizaciones
                            </a>
                            <a href="#" class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditarCliente" data-id="<?= $cliente['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cliente['cliente_nombre']) ?>" data-telefono="<?= htmlspecialchars($cliente['cliente_telefono']) ?>" data-ubicacion="<?= htmlspecialchars($cliente['cliente_ubicacion']) ?>" data-email="<?= htmlspecialchars($cliente['cliente_email'] ?? '') ?>">
                                <i class="bi bi-pencil"></i> Editar
                            </a>
                            <a href="#" class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#modalEliminarCliente" data-id="<?= $cliente['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cliente['cliente_nombre']) ?>">
                                <i class="bi bi-trash"></i> Eliminar
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-people"></i>
                <h5>No hay clientes registrados</h5>
                <p>Los clientes se crean automáticamente cuando realizas cotizaciones.</p>
                <a href="../cotizaciones/crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Crear Primera Cotización
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-clientes').classList.add('active');
        
        // Cambiar a delegación de eventos para formularios AJAX
        function recargarClientesLista(data) {
            if (data.clientes_html) {
                document.getElementById('clientsContainer').innerHTML = data.clientes_html;
            }
            if (data.success) {
                mostrarMensaje('success', data.success);
            } else if (data.error) {
                mostrarMensaje('danger', data.error);
            }
            delegarBotonesClientes(); // Reasignar eventos a los nuevos botones
        }
        function mostrarMensaje(tipo, mensaje) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${tipo} alert-dismissible fade show`;
            alert.role = 'alert';
            alert.innerHTML = `<i class=\"bi bi-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}\"></i> ${mensaje}<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>`;
            document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
            setTimeout(() => { if (alert) alert.remove(); }, 4000);
        }
        // Delegación de eventos para formularios AJAX
        function onAjaxFormSubmit(e) {
            const form = e.target;
            if (form.classList.contains('form-ajax')) {
                e.preventDefault();
                const formData = new FormData(form);
                formData.append('ajax', '1');
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    // Cerrar modal
                    const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    if (modal) modal.hide();
                    recargarClientesLista(data);
                });
            }
        }
        document.addEventListener('submit', onAjaxFormSubmit);
        // Limpiar formularios al cerrar modales
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modalEl => {
            modalEl.addEventListener('hidden.bs.modal', function() {
                const forms = modalEl.querySelectorAll('form');
                forms.forEach(f => f.reset());
            });
        });
        // Delegar eventos para nuevos botones de editar/eliminar tras AJAX
        function delegarBotonesClientes() {
            document.querySelectorAll('.btn-edit').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('edit_cliente_id').value = this.dataset.id;
                    document.getElementById('edit_nombre').value = this.dataset.nombre;
                    document.getElementById('edit_telefono').value = this.dataset.telefono;
                    document.getElementById('edit_ubicacion').value = this.dataset.ubicacion;
                    document.getElementById('edit_email').value = this.dataset.email || '';
                });
            });
            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.getElementById('delete_cliente_id').value = this.dataset.id;
                    document.getElementById('delete_cliente_nombre').textContent = this.dataset.nombre;
                });
            });
        }
        document.addEventListener('DOMContentLoaded', delegarBotonesClientes);
    </script>
    <!-- MODAL AGREGAR CLIENTE -->
    <div class="modal fade" id="modalAgregarCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" autocomplete="off" class="form-ajax">
                    <input type="hidden" name="accion" value="agregar">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-person-plus"></i> Agregar cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="telefono">
                        </div>
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" name="ubicacion" id="ubicacion">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- MODAL EDITAR CLIENTE -->
    <div class="modal fade" id="modalEditarCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" autocomplete="off" class="form-ajax">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="cliente_id" id="edit_cliente_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar cliente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" name="telefono" id="edit_telefono">
                        </div>
                        <div class="mb-3">
                            <label for="edit_ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" name="ubicacion" id="edit_ubicacion">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- MODAL ELIMINAR CLIENTE -->
    <div class="modal fade" id="modalEliminarCliente" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" class="form-ajax">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="cliente_id" id="delete_cliente_id">
                    <div class="modal-header bg-danger">
                        <h5 class="modal-title"><i class="bi bi-trash"></i> Eliminar cliente</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Estás seguro de que quieres eliminar al cliente <strong id="delete_cliente_nombre"></strong>?</p>
                        <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Filtros en tiempo real con AJAX
        const formFiltro = document.getElementById('formFiltroClientes');
        const searchInput = document.getElementById('searchClient');
        const filterSelect = document.getElementById('filterStatus');
        let filtroTimeout = null;
        function actualizarClientesPorFiltro() {
            const params = new URLSearchParams(new FormData(formFiltro));
            params.append('ajax', '1');
            fetch('?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    document.getElementById('clientsContainer').innerHTML = data.clientes_html;
                    delegarBotonesClientes();
                });
        }
        searchInput.addEventListener('input', function() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(actualizarClientesPorFiltro, 300);
        });
        filterSelect.addEventListener('change', actualizarClientesPorFiltro);
        formFiltro.addEventListener('submit', function(e) { e.preventDefault(); actualizarClientesPorFiltro(); });
    </script>
    <!-- Registro de cliente por AJAX robusto -->
    <script>
        const formAgregar = document.querySelector('#modalAgregarCliente form.form-ajax');
        if (formAgregar) {
            formAgregar.addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = formAgregar.querySelector('button[type="submit"]');
                btn.disabled = true;
                const formData = new FormData(formAgregar);
                formData.append('ajax', '1');
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('clientsContainer').innerHTML = data.clientes_html;
                        // Cerrar modal y limpiar
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarCliente'));
                        if (modal) modal.hide();
                        formAgregar.reset();
                        mostrarMensaje('success', data.success);
                    } else if (data.error) {
                        mostrarMensaje('danger', data.error);
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                });
            });
        }
    </script>
</body>
</html> 