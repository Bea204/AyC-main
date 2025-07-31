<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Filtros
$filtro_cliente = isset($_GET['cliente']) ? trim($_GET['cliente']) : '';
$filtro_letra = isset($_GET['letra']) ? strtoupper($_GET['letra']) : '';
$filtro_fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$filtro_fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';
$filtro_usuario = isset($_GET['usuario']) ? trim($_GET['usuario']) : '';
$filtro_estado = isset($_GET['estado_id']) ? $_GET['estado_id'] : '';

// Si no hay filtro de usuario, mostrar solo las del usuario actual
if (!$filtro_usuario) {
    $filtro_usuario = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? '';
}

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if ($filtro_usuario) {
    $where_conditions[] = "c.user_id = ?";
    $params[] = $filtro_usuario;
    $param_types .= 'i';
}
if ($filtro_letra && $filtro_letra !== 'TODOS') {
    $where_conditions[] = "LEFT(cl.nombre, 1) = ?";
    $params[] = $filtro_letra;
    $param_types .= 's';
}
if ($filtro_cliente) {
    $where_conditions[] = "cl.nombre LIKE ?";
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
if ($filtro_estado) {
    $where_conditions[] = "c.estado_id = ?";
    $params[] = $filtro_estado;
    $param_types .= 'i';
}
$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener lista de usuarios para el filtro
$usuarios = $mysqli->query("SELECT user_id, username FROM users ORDER BY username ASC");
$usuarios_array = $usuarios ? $usuarios->fetch_all(MYSQLI_ASSOC) : [];

// Obtener lista de estados para el filtro
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
$estados_array = $estados ? $estados->fetch_all(MYSQLI_ASSOC) : [];

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
        SUM(CASE WHEN estado_id != 4 THEN total ELSE 0 END) as total_valor,
        AVG(CASE WHEN estado_id != 4 THEN total ELSE NULL END) as promedio_valor
    FROM cotizaciones
    WHERE estado_id != 4
";
$stats_result = $mysqli->query($stats_query);
$stats = $stats_result->fetch_assoc();

// --- RESPUESTA AJAX SOLO LISTA ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    include __DIR__ . '/partials/lista.php';
    exit;
}
?>
<?php
// Leer imágenes de /assets/img/ para el modal de configuración
$img_dir = __DIR__ . '/../assets/img/';
$img_files = array_filter(scandir($img_dir), function($f) {
    return preg_match('/\.(png|jpg|jpeg|gif)$/i', $f) && $f !== 'LogoWeb.png';
});
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
            padding: 20px 24px 10px 24px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            align-items: end;
        }
        .filtros-container .form-label { font-weight: 600; color: #232a7c; }
        .filtros-container .form-select, .filtros-container .form-control { min-width: 120px; }
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
        .abc-bar {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 4px;
            margin-bottom: 10px;
            scrollbar-width: thin;
        }
        .abc-btn {
            border: none;
            background: #f4f6fb;
            color: #232a7c;
            font-weight: 600;
            border-radius: 20px;
            padding: 6px 14px;
            cursor: pointer;
            transition: background 0.15s, color 0.15s;
            font-size: 1rem;
            outline: none;
        }
        .abc-btn.active, .abc-btn:focus {
            background: #232a7c;
            color: #fff;
        }
        .abc-btn:hover {
            background: #121866;
            color: #fff;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            .acciones-cotizacion { flex-wrap: wrap; }
            .filtros-container { flex-direction: column; gap: 10px; padding: 12px 6px 6px 6px; }
            .abc-bar { gap: 2px; padding-bottom: 2px; }
            .abc-btn { font-size: 0.95rem; padding: 5px 10px; }
        }
        #loaderCotizaciones { display: none; margin: 0 auto; }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-file-earmark-text"></i> Gestión de Cotizaciones</h2>
            <div>
                <a href="reportes.php" class="btn btn-info me-2">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
                <a href="crear.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nueva Cotización
                </a>
            </div>
        </div>

        <!-- Botón de configuración -->
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-outline-secondary" id="btnConfigEncabezado"><i class="bi bi-gear"></i> Configurar encabezado</button>
        </div>
        <!-- Modal de configuración -->
        <div class="modal fade" id="modalConfigEncabezado" tabindex="-1" aria-labelledby="modalConfigEncabezadoLabel" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalConfigEncabezadoLabel">Configurar encabezado de cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                <form id="formConfigEncabezado">
                  <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="configTelefono">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Ubicación</label>
                    <input type="text" class="form-control" id="configUbicacion">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Tamaño del logo de la empresa (px)</label>
                    <input type="number" class="form-control" id="configLogoSize" min="40" max="200">
                  </div>
                  <div class="mb-2">Logos a mostrar:</div>
                  <div id="logosOpciones" class="d-flex flex-wrap gap-3">
                    <?php foreach ($img_files as $img): ?>
                      <div class="form-check text-center">
                        <input class="form-check-input" type="checkbox" value="<?= htmlspecialchars($img) ?>" id="logo_<?= md5($img) ?>">
                        <label class="form-check-label" for="logo_<?= md5($img) ?>">
                          <img src="../assets/img/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($img) ?>" style="height:32px;display:block;margin:0 auto 2px auto;">
                          <small><?= htmlspecialchars(pathinfo($img, PATHINFO_FILENAME)) ?></small>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="configMostrarSinStock">
                    <label class="form-check-label" for="configMostrarSinStock">Mostrar badge "Sin stock" en productos sin inventario</label>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarConfigEncabezado">Guardar</button>
              </div>
            </div>
          </div>
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
            <form method="GET" class="row g-3 flex-grow-1" id="formFiltrosCotizaciones" autocomplete="off" style="width:100%;">
                <div class="col-12">
                    <div class="abc-bar" id="abcBar">
                        <button type="button" class="abc-btn" data-letra="" id="btnLetraTodos">Todos</button>
                        <?php foreach (range('A', 'Z') as $letra): ?>
                            <button type="button" class="abc-btn" data-letra="<?= $letra ?>" id="btnLetra<?= $letra ?>"><?= $letra ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="letra" id="inputLetra" value="<?= htmlspecialchars($filtro_letra) ?>">
                </div>
                <div class="col-md-3 col-12">
                    <label class="form-label">Cliente</label>
                    <input type="text" name="cliente" id="filtroCliente" class="form-control" value="<?= htmlspecialchars($filtro_cliente) ?>" placeholder="Buscar por cliente">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Creada por</label>
                    <select name="usuario" id="filtroUsuario" class="form-select">
                        <option value="">Todos los usuarios</option>
                        <?php foreach ($usuarios_array as $usuario): ?>
                            <option value="<?= $usuario['user_id'] ?>" <?= $filtro_usuario == $usuario['user_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Estado</label>
                    <div class="input-group">
                        <span class="input-group-text" style="background:#f4f6fb;color:#232a7c;"><i class="bi bi-flag"></i></span>
                        <select name="estado_id" id="filtroEstado" class="form-select" style="border-color:#232a7c;">
                            <option value="">Todos los estados</option>
                            <?php foreach ($estados_array as $est): ?>
                                <option value="<?= $est['est_cot_id'] ?>" <?= $filtro_estado == $est['est_cot_id'] ? 'selected' : '' ?>><?= htmlspecialchars($est['nombre_estado']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Desde</label>
                    <input type="date" name="fecha_desde" id="filtroFechaDesde" class="form-control" value="<?= $filtro_fecha_desde ?>">
                </div>
                <div class="col-md-2 col-6">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="fecha_hasta" id="filtroFechaHasta" class="form-control" value="<?= $filtro_fecha_hasta ?>">
                </div>
                <div class="col-md-3 col-12 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </form>
        </div>
        <div id="loaderCotizaciones">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div>
        </div>
        <div id="cotizacionesLista">
            <?php include __DIR__ . '/partials/lista.php'; ?>
        </div>
    </main>

    <!-- Toast de guardado de configuración -->
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
      <div id="toastConfigGuardada" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <i class="bi bi-check-circle"></i> Configuración guardada correctamente. Se aplicará al ver las cotizaciones.
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
      </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-cotizaciones').classList.add('active');

        const formFiltros = document.getElementById('formFiltrosCotizaciones');
        const inputCliente = document.getElementById('filtroCliente');
        const selectLetra = document.getElementById('filtroLetra'); // This element is no longer used for the ABC filter
        const inputDesde = document.getElementById('filtroFechaDesde');
        const inputHasta = document.getElementById('filtroFechaHasta');
        const loader = document.getElementById('loaderCotizaciones');
        const lista = document.getElementById('cotizacionesLista');
        let filtroTimeout = null;

        function buscarCotizacionesAJAX() {
            const formData = new FormData(formFiltros);
            const params = new URLSearchParams(formData).toString();
            loader.style.display = 'block';
            lista.style.opacity = '0.5';
            fetch('index.php?' + params + '&ajax=1')
                .then(res => res.text())
                .then(html => {
                    lista.innerHTML = html;
                    loader.style.display = 'none';
                    lista.style.opacity = '1';
                });
        }

        inputCliente.addEventListener('input', function() {
            clearTimeout(filtroTimeout);
            filtroTimeout = setTimeout(buscarCotizacionesAJAX, 400);
        });
        // --- Filtro ABC por botones ---
        const abcBar = document.getElementById('abcBar');
        const inputLetra = document.getElementById('inputLetra');
        function activarBotonLetra(letra) {
            document.querySelectorAll('.abc-btn').forEach(btn => btn.classList.remove('active'));
            if (!letra) {
                document.getElementById('btnLetraTodos').classList.add('active');
            } else {
                const btn = document.getElementById('btnLetra' + letra);
                if (btn) btn.classList.add('active');
            }
        }
        abcBar.addEventListener('click', function(e) {
            if (e.target.classList.contains('abc-btn')) {
                const letra = e.target.dataset.letra;
                inputLetra.value = letra;
                activarBotonLetra(letra);
                buscarCotizacionesAJAX();
            }
        });
        // Activar el botón correcto al cargar
        activarBotonLetra(inputLetra.value);

        formFiltros.addEventListener('submit', function(e) {
            e.preventDefault();
            buscarCotizacionesAJAX();
        });

        document.getElementById('filtroEstado').addEventListener('change', buscarCotizacionesAJAX);

        // Configuración de encabezado (localStorage) - ahora con logos dinámicos
        const defaultConfig = {
          telefono: '999 134 3979',
          ubicacion: 'Mérida, Yucatán',
          logoSize: 80,
          logos: [<?php foreach ($img_files as $img) { echo "'".addslashes($img)."',"; } ?>],
          mostrarSinStock: true
        };
        function getConfigEncabezado() {
          const data = localStorage.getItem('cotiz_config_encabezado');
          return data ? JSON.parse(data) : defaultConfig;
        }
        function setConfigEncabezado(cfg) {
          localStorage.setItem('cotiz_config_encabezado', JSON.stringify(cfg));
        }
        function abrirModalConfig() {
          const cfg = getConfigEncabezado();
          document.getElementById('configTelefono').value = cfg.telefono;
          document.getElementById('configUbicacion').value = cfg.ubicacion;
          document.getElementById('configLogoSize').value = cfg.logoSize;
          document.getElementById('configMostrarSinStock').checked = !!cfg.mostrarSinStock;
          document.querySelectorAll('#logosOpciones input[type=checkbox]').forEach(cb => {
            cb.checked = cfg.logos.includes(cb.value);
          });
          const modal = new bootstrap.Modal(document.getElementById('modalConfigEncabezado'));
          modal.show();
        }
        document.getElementById('btnConfigEncabezado').addEventListener('click', abrirModalConfig);
        document.getElementById('btnGuardarConfigEncabezado').addEventListener('click', function() {
          const cfg = {
            telefono: document.getElementById('configTelefono').value,
            ubicacion: document.getElementById('configUbicacion').value,
            logoSize: parseInt(document.getElementById('configLogoSize').value) || 80,
            logos: Array.from(document.querySelectorAll('#logosOpciones input[type=checkbox]:checked')).map(cb => cb.value),
            mostrarSinStock: document.getElementById('configMostrarSinStock').checked
          };
          setConfigEncabezado(cfg);
          bootstrap.Modal.getInstance(document.getElementById('modalConfigEncabezado')).hide();
          // Mostrar toast profesional
          const toast = new bootstrap.Toast(document.getElementById('toastConfigGuardada'));
          toast.show();
        });
    </script>
</body>
</html> 