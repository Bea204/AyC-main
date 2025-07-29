<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener parámetros de orden
    $orden_col = $_GET['orden'] ?? 'm.movement_date';
    $orden_dir = $_GET['dir'] ?? 'DESC';
    $ordenes_validos = [
        'm.movement_id' => 'ID',
        'p.product_name' => 'Producto',
        'mt.name' => 'Tipo',
        'm.quantity' => 'Cantidad',
        'u.username' => 'Usuario',
        'm.movement_date' => 'Fecha'
    ];
    if (!array_key_exists($orden_col, $ordenes_validos)) $orden_col = 'm.movement_date';
    if (!in_array(strtoupper($orden_dir), ['ASC','DESC'])) $orden_dir = 'DESC';

    // Obtener datos para filtros
    $productos_filtro = $mysqli->query("SELECT DISTINCT product_name FROM products ORDER BY product_name");
    $tipos_filtro = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");
    $usuarios_filtro = $mysqli->query("SELECT user_id, username FROM users ORDER BY username");

    // Leer filtros
    $filtro_producto = $_GET['producto'] ?? '';
    $filtro_tipo = $_GET['tipo'] ?? '';
    $filtro_usuario = $_GET['usuario'] ?? '';
    $filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
    $filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';
    $filtro_bobina = isset($_GET['solo_bobinas']) && $_GET['solo_bobinas'] == '1';

    // Modificar consulta para aplicar filtros
    $where = [];
    if ($filtro_producto) $where[] = "p.product_name LIKE '%" . $mysqli->real_escape_string($filtro_producto) . "%'";
    if ($filtro_tipo) $where[] = "m.movement_type_id = " . intval($filtro_tipo);
    if ($filtro_usuario) $where[] = "m.user_id = " . intval($filtro_usuario);
    if ($filtro_fecha_desde) $where[] = "DATE(m.movement_date) >= '" . $mysqli->real_escape_string($filtro_fecha_desde) . "'";
    if ($filtro_fecha_hasta) $where[] = "DATE(m.movement_date) <= '" . $mysqli->real_escape_string($filtro_fecha_hasta) . "'";
    if ($filtro_bobina) $where[] = "p.tipo_gestion = 'bobina'";
    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Consulta con filtros y orden
    $query = "
        SELECT 
            m.*, 
            p.product_name,
            p.tipo_gestion,
            mt.name AS movement_type_nombre,
            b.identificador AS bobina_identificador,
            b.metros_actuales AS bobina_metros_actuales,
            u.username AS usuario_nombre,
            t.nombre AS tecnico_nombre
        FROM 
            movements m
        JOIN 
            products p ON m.product_id = p.product_id
        LEFT JOIN
            movement_types mt ON m.movement_type_id = mt.movement_type_id
        LEFT JOIN
            bobinas b ON m.bobina_id = b.bobina_id
        LEFT JOIN
            users u ON m.user_id = u.user_id
        LEFT JOIN
            tecnicos t ON m.tecnico_id = t.tecnico_id
        $where_sql
        ORDER BY $orden_col $orden_dir
    ";
    $result = $mysqli->query($query);

    // Estadísticas
    $stats_query = "
        SELECT 
            COUNT(*) as total_movimientos,
            COUNT(DISTINCT m.product_id) as productos_afectados,
            COUNT(DISTINCT m.user_id) as usuarios_activos,
            SUM(CASE WHEN m.quantity > 0 THEN 1 ELSE 0 END) as entradas,
            SUM(CASE WHEN m.quantity < 0 THEN 1 ELSE 0 END) as salidas
        FROM movements m
        JOIN products p ON m.product_id = p.product_id
        $where_sql
    ";
    $stats_result = $mysqli->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos | Gestor de inventarios</title>
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
        .movements-header {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .movements-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-card {
            background: #232a7c;
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
            font-size: 2rem;
            font-weight: 700;
            display: block;
            margin-bottom: 8px;
            color: #fff;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.93;
            color: #e3e6fa;
            font-weight: 500;
        }
        .filters-section {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .filters-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .movements-container {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
        }
        .movement-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(35,42,124,0.07);
            border: 1.5px solid #e3e6f0;
            transition: box-shadow 0.2s, transform 0.2s;
            position: relative;
            overflow: hidden;
        }
        .movement-card:hover {
            box-shadow: 0 8px 32px rgba(35,42,124,0.13);
            transform: translateY(-2px) scale(1.01);
            border-color: #232a7c;
        }
        .movement-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #232a7c;
        }
        .movement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .movement-id {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }
        .movement-date {
            font-size: 0.85rem;
            color: #888;
        }
        .product-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #121866;
            margin: 8px 0;
        }
        .movement-type {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .type-entrada {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .type-salida {
            background: #ffebee;
            color: #c62828;
        }
        .type-ajuste {
            background: #fff3e0;
            color: #f57c00;
        }
        .movement-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 12px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #121866;
        }
        .quantity-entrada {
            color: #2e7d32;
        }
        .quantity-salida {
            color: #c62828;
        }
        .bobina-info {
            background: #e3f2fd;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #1565c0;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
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
        .table-header {
            background: #121866;
            color: #fff;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
            margin-bottom: 0;
        }
        .table-header h5 {
            margin: 0;
            font-weight: 700;
        }
        .table-responsive {
            border-radius: 0 0 12px 12px;
            overflow: hidden;
        }
        .table {
            margin: 0;
            font-size: 0.95rem;
        }
        .table th {
            background: #f8f9fa;
            color: #121866;
            font-weight: 700;
            border: none;
            padding: 16px 12px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .table th:hover {
            background: #e9ecef;
        }
        .table td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #e3e6f0;
        }
        .table tbody tr:hover {
            background: #f8f9fa;
        }
        .sort-icon {
            margin-left: 4px;
            opacity: 0.7;
        }
        .sort-active {
            opacity: 1;
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
            .movement-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <!-- Header con estadísticas -->
        <div class="movements-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="movements-title">
                        <i class="bi bi-arrow-left-right"></i> Movimientos de Inventario
                    </h1>
                    <p class="text-muted mb-0">Registro detallado de entradas, salidas y ajustes de stock</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="new.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar Movimiento
                    </a>
                    <a href="manage_types.php" class="btn btn-outline-info">
                        <i class="bi bi-gear"></i> Gestionar Tipos
                    </a>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['total_movimientos'] ?? 0 ?></span>
                    <span class="stat-label">Total Movimientos</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['productos_afectados'] ?? 0 ?></span>
                    <span class="stat-label">Productos Afectados</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['usuarios_activos'] ?? 0 ?></span>
                    <span class="stat-label">Usuarios Activos</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['entradas'] ?? 0 ?></span>
                    <span class="stat-label">Entradas</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?= $stats['salidas'] ?? 0 ?></span>
                    <span class="stat-label">Salidas</span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters-section">
            <div class="filters-title">
                <i class="bi bi-funnel"></i> Filtros de Búsqueda
            </div>
            <form method="GET" class="row g-3" autocomplete="off">
                <div class="col-md-3">
                    <label class="form-label">Producto</label>
                    <input type="text" name="producto" class="form-control" placeholder="Nombre de producto" value="<?= htmlspecialchars($filtro_producto) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo de movimiento</label>
                    <select name="tipo" class="form-select">
                        <option value="">Todos los tipos</option>
                        <?php if ($tipos_filtro) while ($t = $tipos_filtro->fetch_assoc()): ?>
                            <option value="<?= $t['movement_type_id'] ?>" <?= $filtro_tipo == $t['movement_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Usuario</label>
                    <select name="usuario" class="form-select">
                        <option value="">Todos los usuarios</option>
                        <?php if ($usuarios_filtro) while ($u = $usuarios_filtro->fetch_assoc()): ?>
                            <option value="<?= $u['user_id'] ?>" <?= $filtro_usuario == $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtro_fecha_desde) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtro_fecha_hasta) ?>">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="solo_bobinas" value="1" id="soloBobinas" <?= $filtro_bobina ? 'checked' : '' ?>>
                        <label class="form-check-label" for="soloBobinas">Solo bobinas</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Lista de movimientos -->
        <div class="movements-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <!-- Vista de tabla -->
                <div class="table-header">
                    <h5><i class="bi bi-list-ul"></i> Lista de Movimientos</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <?php
                                $cols = [
                                    'm.movement_id' => 'ID',
                                    'p.product_name' => 'Producto',
                                    'mt.name' => 'Tipo',
                                    'm.quantity' => 'Cantidad',
                                    'u.username' => 'Usuario',
                                    't.nombre' => 'Técnico',
                                    'm.movement_date' => 'Fecha'
                                ];
                                foreach ($cols as $col => $label):
                                    $isActive = $orden_col === $col;
                                    $nextDir = ($isActive && strtoupper($orden_dir) === 'ASC') ? 'DESC' : 'ASC';
                                    $icon = '';
                                    if ($isActive) {
                                        $icon = strtoupper($orden_dir) === 'ASC' ? '<i class="bi bi-caret-up-fill sort-icon sort-active"></i>' : '<i class="bi bi-caret-down-fill sort-icon sort-active"></i>';
                                    }
                                    $params = $_GET;
                                    $params['orden'] = $col;
                                    $params['dir'] = $nextDir;
                                    $url = '?' . http_build_query($params);
                                    echo "<th scope='col' onclick=\"window.location='$url'\">$label $icon</th>";
                                endforeach;
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary">#<?= $row['movement_id'] ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                            <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_identificador']): ?>
                                                <div class="bobina-info">
                                                    <i class="bi bi-receipt"></i>
                                                    <?= htmlspecialchars($row['bobina_identificador']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?= strpos(strtolower($row['movement_type_nombre']), 'entrada') !== false ? 'bg-success' : (strpos(strtolower($row['movement_type_nombre']), 'salida') !== false ? 'bg-danger' : 'bg-warning') ?>">
                                            <?= htmlspecialchars($row['movement_type_nombre']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="<?= $row['quantity'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $row['quantity'] > 0 ? '+' : '' ?><?= $row['quantity'] ?>
                                            <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                                m
                                            <?php endif; ?>
                                        </strong>
                                        <?php if ($row['tipo_gestion'] === 'bobina' && $row['bobina_metros_actuales'] !== null): ?>
                                            <br><small class="text-muted">Restante: <?= $row['bobina_metros_actuales'] ?>m</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-person"></i>
                                            <?= $row['usuario_nombre'] ? htmlspecialchars($row['usuario_nombre']) : 'Desconocido' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['tecnico_nombre']): ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-person-badge"></i> <?= htmlspecialchars($row['tecnico_nombre']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3"></i>
                                            <?= date('d/m/Y H:i', strtotime($row['movement_date'])) ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-arrow-left-right"></i>
                    <h5>No hay movimientos registrados</h5>
                    <p>Los movimientos aparecerán aquí cuando registres entradas, salidas o ajustes de inventario.</p>
                    <a href="new.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar Primer Movimiento
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resalta el menú activo
        document.querySelector('.sidebar-movimientos').classList.add('active');
        
        // Mejorar la experiencia de ordenamiento
        document.querySelectorAll('th[onclick]').forEach(th => {
            th.style.cursor = 'pointer';
            th.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#e9ecef';
            });
            th.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    </script>
</body>
</html>