<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);

// Obtener cotización existente
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, cl.nombre as cliente_nombre_real, cl.telefono as cliente_telefono_real, cl.ubicacion as cliente_direccion_real, cl.email as cliente_email_real, ec.nombre_estado
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

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.*, p.product_name, p.sku, p.image as product_image, c.name as categoria, s.name as proveedor, p.tipo_gestion
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    WHERE cp.cotizacion_id = ?
    ORDER BY cp.cotizacion_producto_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos_cotizacion = $stmt->get_result();

// Preparar productos existentes para JavaScript
$productos_existentes = [];
while ($prod = $productos_cotizacion->fetch_assoc()) {
    $img = $prod['product_image'] ?? '';
    if ($img && strpos($img, 'uploads/products/') === false) {
        $img = 'uploads/products/' . $img;
    }
    $productos_existentes[] = [
        'product_id' => $prod['product_id'],
        'nombre' => $prod['product_name'],
        'sku' => $prod['sku'],
        'cantidad' => $prod['cantidad'],
        'precio' => $prod['precio_unitario'],
        'imagen' => $img,
        'tipo_gestion' => $prod['tipo_gestion']
    ];
}

// Obtener servicios de la cotización
$stmt = $mysqli->prepare("
    SELECT cs.*, s.nombre as servicio_nombre, s.descripcion as servicio_descripcion, s.categoria as servicio_categoria, s.imagen as servicio_imagen
    FROM cotizaciones_servicios cs
    LEFT JOIN servicios s ON cs.servicio_id = s.servicio_id
    WHERE cs.cotizacion_id = ?
    ORDER BY cs.cotizacion_servicio_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$servicios_cotizacion = $stmt->get_result();

// Preparar servicios existentes para JS
$servicios_existentes = [];
while ($serv = $servicios_cotizacion->fetch_assoc()) {
    $img = $serv['imagen'] ?? $serv['servicio_imagen'] ?? '';
    if ($img && strpos($img, 'uploads/services/') === false) {
        $img = 'uploads/services/' . $img;
    }
    $servicios_existentes[] = [
        'servicio_id' => $serv['servicio_id'],
        'nombre' => $serv['nombre_servicio'],
        'categoria' => $serv['servicio_categoria'],
        'descripcion' => $serv['descripcion'],
        'cantidad' => $serv['cantidad'],
        'precio' => $serv['precio_unitario'],
        'imagen' => $img
    ];
}
// Servicios disponibles para agregar
$servicios = $mysqli->query("SELECT servicio_id, nombre, categoria, descripcion, precio, imagen FROM servicios WHERE is_active = 1 ORDER BY categoria, nombre ASC");
$servicios_array = $servicios ? $servicios->fetch_all(MYSQLI_ASSOC) : [];

// Obtener insumos de la cotización
$stmt = $mysqli->prepare("
    SELECT ci.*, i.nombre as insumo_nombre, i.categoria as insumo_categoria, i.cantidad as insumo_stock, i.precio_unitario as insumo_precio, c.name as categoria_nombre, s.name as proveedor_nombre
    FROM cotizaciones_insumos ci
    LEFT JOIN insumos i ON ci.insumo_id = i.insumo_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE ci.cotizacion_id = ?
    ORDER BY ci.cotizacion_insumo_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$insumos_cotizacion = $stmt->get_result();
$insumos_existentes = [];
while ($ins = $insumos_cotizacion->fetch_assoc()) {
    $insumos_existentes[] = [
        'insumo_id' => $ins['insumo_id'],
        'nombre' => $ins['nombre_insumo'] ?? $ins['insumo_nombre'],
        'categoria' => $ins['categoria'] ?? $ins['categoria_nombre'],
        'proveedor' => $ins['proveedor'] ?? $ins['proveedor_nombre'],
        'stock' => $ins['stock_disponible'] ?? $ins['insumo_stock'],
        'cantidad' => $ins['cantidad'],
        'precio' => $ins['precio_unitario'],
    ];
}

// --- Preparar datos para selects ---
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono, ubicacion, email FROM clientes ORDER BY nombre ASC");
$clientes_array = $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [];
$productos = $mysqli->query("
    SELECT 
        p.product_id, 
        p.product_name, 
        p.sku, 
        p.price, 
        p.tipo_gestion,
        c.name as categoria, 
        s.name as proveedor,
        CASE 
            WHEN p.tipo_gestion = 'bobina' THEN 
                COALESCE(SUM(b.metros_actuales), 0)
            ELSE 
                p.quantity
        END as stock_disponible
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    GROUP BY p.product_id, p.product_name, p.sku, p.price, p.tipo_gestion, c.name, s.name, p.quantity
    ORDER BY p.product_name ASC
");
$productos_array = $productos ? $productos->fetch_all(MYSQLI_ASSOC) : [];
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name ASC");
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name ASC");

// Verificar si existen estados de cotización, si no, crearlos
$estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
if ($estados && $estados->num_rows == 0) {
    // Crear estados básicos si no existen
    $estados_basicos = [
        ['nombre_estado' => 'Borrador'],
        ['nombre_estado' => 'Enviada'],
        ['nombre_estado' => 'Aprobada'],
        ['nombre_estado' => 'Rechazada'],
        ['nombre_estado' => 'Convertida']
    ];
    
    foreach ($estados_basicos as $estado) {
        $stmt = $mysqli->prepare("INSERT INTO est_cotizacion (nombre_estado) VALUES (?)");
        $stmt->bind_param('s', $estado['nombre_estado']);
        $stmt->execute();
        $stmt->close();
    }
    
    // Recargar estados
    $estados = $mysqli->query("SELECT est_cot_id, nombre_estado FROM est_cotizacion ORDER BY est_cot_id ASC");
}

$estados_array = $estados ? $estados->fetch_all(MYSQLI_ASSOC) : [];

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productos_json = $_POST['productos_json'] ?? '';
    $productos = json_decode($productos_json, true);
    $servicios_json = $_POST['servicios_json'] ?? '';
    $servicios = json_decode($servicios_json, true);
    $insumos_json = $_POST['insumos_json'] ?? '';
    $insumos = json_decode($insumos_json, true);
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    
    if ((!$productos || !is_array($productos) || count($productos) == 0) && (!$servicios || !is_array($servicios) || count($servicios) == 0) && (!$insumos || !is_array($insumos) || count($insumos) == 0)) {
        $error = 'Debes agregar al menos un producto, servicio o insumo a la cotización.';
    }
    if (!$cliente_id && !$cliente_nombre) {
        $error = 'Debes seleccionar o registrar un cliente.';
    }
    
    if (!$error) {
        // Cliente: alta si es nuevo
        if (!$cliente_id) {
            $stmt = $mysqli->prepare("SELECT cliente_id FROM clientes WHERE nombre = ? OR telefono = ? OR email = ? LIMIT 1");
            $stmt->bind_param('sss', $cliente_nombre, $cliente_telefono, $cliente_email);
            $stmt->execute();
            $stmt->bind_result($cliente_id_encontrado);
            if ($stmt->fetch()) {
                $cliente_id = $cliente_id_encontrado;
            }
            $stmt->close();
            if (!$cliente_id) {
                $stmt = $mysqli->prepare("INSERT INTO clientes (nombre, telefono, ubicacion, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('ssss', $cliente_nombre, $cliente_telefono, $cliente_ubicacion, $cliente_email);
                $stmt->execute();
                $cliente_id = $stmt->insert_id;
                $stmt->close();
            }
        }
        
        // Datos de la cotización
        $fecha_cotizacion = $_POST['fecha_cotizacion'];
        $validez_dias = intval($_POST['validez_dias']);
        $condiciones_pago = trim($_POST['condiciones_pago']);
        $observaciones = trim($_POST['observaciones']);
        $descuento_porcentaje = floatval($_POST['descuento_porcentaje']);
        $estado_id = intval($_POST['estado_id']);
        
        // Calcular totales
        $subtotal = 0;
        foreach ($productos as $prod) {
            $subtotal += floatval($prod['precio']) * intval($prod['cantidad']);
        }
        $descuento_monto = $subtotal * $descuento_porcentaje / 100;
        $total = $subtotal - $descuento_monto;
        
        // Actualizar cotización
        $stmt = $mysqli->prepare("UPDATE cotizaciones SET cliente_id = ?, fecha_cotizacion = ?, validez_dias = ?, subtotal = ?, descuento_porcentaje = ?, descuento_monto = ?, total = ?, condiciones_pago = ?, observaciones = ?, estado_id = ? WHERE cotizacion_id = ?");
        $stmt->bind_param('isidddssiii', $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $condiciones_pago, $observaciones, $estado_id, $cotizacion_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Registrar acción en el historial
            require_once 'helpers.php';
            inicializarAccionesCotizacion($mysqli);
            registrarAccionCotizacion(
                $cotizacion_id, 
                'Modificada', 
                "Cotización modificada con " . count($productos) . " productos por un total de $" . number_format($total, 2),
                $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
                $mysqli
            );
            
            // Eliminar productos anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_productos WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            
            // Insertar nuevos productos
            foreach ($productos as $prod) {
                $product_id = $prod['product_id'] ?? null;
                if (!$product_id) {
                    $stmt_prod = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier_id) VALUES (?, ?, ?, ?, ?, ?)");
                    $cat_id = $prod['category_id'] ?? null;
                    $prov_id = $prod['supplier_id'] ?? null;
                    $stmt_prod->bind_param('ssdiis', $prod['nombre'], $prod['sku'], $prod['precio'], $prod['cantidad'], $cat_id, $prov_id);
                    $stmt_prod->execute();
                    $product_id = $stmt_prod->insert_id;
                    $stmt_prod->close();
                }
                
                $stmt_cp = $mysqli->prepare("INSERT INTO cotizaciones_productos (cotizacion_id, product_id, cantidad, precio_unitario, precio_total) VALUES (?, ?, ?, ?, ?)");
                $precio_total = floatval($prod['precio']) * intval($prod['cantidad']);
                $stmt_cp->bind_param('iiddd', $cotizacion_id, $product_id, $prod['cantidad'], $prod['precio'], $precio_total);
                $stmt_cp->execute();
                $stmt_cp->close();
            }

            // Eliminar servicios anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_servicios WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            // Insertar nuevos servicios
            foreach ($servicios as $serv) {
                $servicio_id = $serv['servicio_id'] ?? null;
                $nombre_servicio = $serv['nombre'];
                $descripcion = $serv['descripcion'] ?? '';
                $cantidad = intval($serv['cantidad']);
                $precio_unitario = floatval($serv['precio']);
                $precio_total = $cantidad * $precio_unitario;
                $imagen = $serv['imagen'] ?? null;
                $categoria = $serv['categoria'] ?? '';
                $stmt_cs = $mysqli->prepare("INSERT INTO cotizaciones_servicios (cotizacion_id, servicio_id, nombre_servicio, descripcion, cantidad, precio_unitario, precio_total, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_cs->bind_param('iissddds', $cotizacion_id, $servicio_id, $nombre_servicio, $descripcion, $cantidad, $precio_unitario, $precio_total, $imagen);
                $stmt_cs->execute();
                $stmt_cs->close();
            }

            // Eliminar insumos anteriores
            $stmt = $mysqli->prepare("DELETE FROM cotizaciones_insumos WHERE cotizacion_id = ?");
            $stmt->bind_param('i', $cotizacion_id);
            $stmt->execute();
            $stmt->close();
            // Insertar nuevos insumos
            foreach ($insumos as $ins) {
                $insumo_id = $ins['insumo_id'] ?? null;
                $nombre_insumo = $ins['nombre'];
                $categoria = $ins['categoria'] ?? '';
                $proveedor = $ins['proveedor'] ?? '';
                $cantidad = intval($ins['cantidad']);
                $precio_unitario = floatval($ins['precio']);
                $precio_total = $cantidad * $precio_unitario;
                $imagen = $ins['imagen'] ?? null;
                $stock_disponible = $ins['stock'] ?? 0; // Assuming 'stock' is the correct key for stock_disponible
                $stmt_ci = $mysqli->prepare("INSERT INTO cotizaciones_insumos (cotizacion_id, insumo_id, nombre_insumo, categoria, proveedor, cantidad, precio_unitario, precio_total, stock_disponible, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt_ci->bind_param('iissddddds', $cotizacion_id, $insumo_id, $nombre_insumo, $categoria, $proveedor, $cantidad, $precio_unitario, $precio_total, $stock_disponible, $imagen);
                $stmt_ci->execute();
                $stmt_ci->close();
            }
            
            header("Location: ver.php?id=$cotizacion_id");
            exit;
        } else {
            $error = 'Error al actualizar la cotización: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cotización <?= $cotizacion['numero_cotizacion'] ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.07);
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
        .form-section { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(18,24,102,0.07); }
        .section-title { font-size: 1.3rem; font-weight: 700; color: #121866; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .select2-container--default .select2-selection--single { height: 38px; }
        .select2-selection__rendered { line-height: 38px !important; }
        .select2-selection__arrow { height: 38px !important; }
        .table thead th { background: #121866; color: #fff; }
        .badge-stock { font-size: 0.85rem; }
        .btn-remove-product { color: #dc3545; cursor: pointer; }
        .btn-remove-product:hover { color: #c82333; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Editar Cotización <?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></h2>
        <a href="ver.php?id=<?= $cotizacion_id ?>" class="btn btn-secondary">
            <i class="bi bi-eye"></i> Ver Cotización
        </a>
    </div>

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

    <form method="POST" id="formEditarCotizacion" autocomplete="off">
        <!-- Sección Cliente -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-person"></i> Cliente</div>
            <div class="mb-3">
                <label for="cliente_select" class="form-label">Seleccionar cliente</label>
                <select class="form-select" id="cliente_select" name="cliente_id">
                    <option value="">-- Nuevo cliente --</option>
                    <?php foreach ($clientes_array as $cl): ?>
                        <option value="<?= $cl['cliente_id'] ?>" 
                                data-nombre="<?= htmlspecialchars($cl['nombre']) ?>" 
                                data-telefono="<?= htmlspecialchars($cl['telefono']) ?>" 
                                data-ubicacion="<?= htmlspecialchars($cl['ubicacion']) ?>" 
                                data-email="<?= htmlspecialchars($cl['email']) ?>"
                                <?= ($cl['cliente_id'] == $cotizacion['cliente_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cl['nombre']) ?><?= $cl['telefono'] ? ' (' . htmlspecialchars($cl['telefono']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="camposNuevoCliente">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="cliente_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="cliente_nombre" id="cliente_nombre" 
                               value="<?= htmlspecialchars($cotizacion['cliente_nombre_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono" 
                               value="<?= htmlspecialchars($cotizacion['cliente_telefono_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="cliente_ubicacion" id="cliente_ubicacion" 
                               value="<?= htmlspecialchars($cotizacion['cliente_direccion_real'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="cliente_email" id="cliente_email" 
                               value="<?= htmlspecialchars($cotizacion['cliente_email_real'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección Productos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-box-seam"></i> Productos</div>
            <div class="mb-3">
                <label for="producto_select" class="form-label">Agregar producto</label>
                <select class="form-select" id="producto_select">
                    <option value="">Seleccionar producto...</option>
                    <?php foreach ($productos_array as $prod): ?>
                        <option value="<?= $prod['product_id'] ?>" 
                                data-nombre="<?= htmlspecialchars($prod['product_name']) ?>" 
                                data-sku="<?= htmlspecialchars($prod['sku']) ?>" 
                                data-precio="<?= $prod['price'] ?>" 
                                data-stock="<?= $prod['stock_disponible'] ?>"
                                data-categoria="<?= htmlspecialchars($prod['categoria'] ?? '') ?>"
                                data-proveedor="<?= htmlspecialchars($prod['proveedor'] ?? '') ?>">
                            <?= htmlspecialchars($prod['product_name']) ?> - <?= htmlspecialchars($prod['sku']) ?> ($<?= number_format($prod['price'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped" id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>SKU</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los productos se cargarán dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección Insumos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-tools"></i> Insumos</div>
            <div class="mb-3">
                <label for="buscador_insumo" class="form-label">Buscar insumo</label>
                <input type="text" class="form-control" id="buscador_insumo" placeholder="Nombre, categoría o proveedor...">
                <div id="sugerencias_insumos" class="list-group mt-1"></div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaInsumosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Stock</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <!-- Sección Servicios -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-tools"></i> Servicios</div>
            <div class="mb-3">
                <label for="servicio_select" class="form-label">Agregar servicio</label>
                <select class="form-select" id="servicio_select">
                    <option value="">Seleccionar servicio...</option>
                    <?php foreach ($servicios_array as $serv): ?>
                        <option value="<?= $serv['servicio_id'] ?>"
                                data-nombre="<?= htmlspecialchars($serv['nombre']) ?>"
                                data-categoria="<?= htmlspecialchars($serv['categoria']) ?>"
                                data-descripcion="<?= htmlspecialchars($serv['descripcion']) ?>"
                                data-precio="<?= $serv['precio'] ?>"
                                data-imagen="<?= htmlspecialchars($serv['imagen']) ?>">
                            <?= htmlspecialchars($serv['nombre']) ?> (<?= htmlspecialchars($serv['categoria']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="tablaServicios">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Los servicios se cargarán dinámicamente con JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección Detalles -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-gear"></i> Detalles de la Cotización</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_cotizacion" class="form-label">Fecha de cotización</label>
                    <input type="date" class="form-control" name="fecha_cotizacion" id="fecha_cotizacion" 
                           value="<?= $cotizacion['fecha_cotizacion'] ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="validez_dias" class="form-label">Validez (días)</label>
                    <input type="number" class="form-control" name="validez_dias" id="validez_dias" 
                           value="<?= $cotizacion['validez_dias'] ?>" min="1" required>
                </div>
                <div class="col-md-3">
                    <label for="estado_id" class="form-label">Estado</label>
                    <select class="form-select" name="estado_id" id="estado_id" required>
                        <?php foreach ($estados_array as $est): ?>
                            <option value="<?= $est['est_cot_id'] ?>" <?= ($est['est_cot_id'] == $cotizacion['estado_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($est['nombre_estado']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="descuento_porcentaje" class="form-label">Descuento (%)</label>
                    <input type="number" class="form-control" name="descuento_porcentaje" id="descuento_porcentaje" 
                           value="<?= $cotizacion['descuento_porcentaje'] ?>" min="0" max="100" step="0.01">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="condiciones_pago" class="form-label">Condiciones de pago</label>
                    <textarea class="form-control" name="condiciones_pago" id="condiciones_pago" rows="3"><?= htmlspecialchars($cotizacion['condiciones_pago'] ?? '') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" id="observaciones" rows="3"><?= htmlspecialchars($cotizacion['observaciones'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-calculator"></i> Resumen</div>
            <div class="row">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Subtotal:</strong></td>
                            <td class="text-end" id="subtotal">$<?= number_format($cotizacion['subtotal'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Descuento:</strong></td>
                            <td class="text-end" id="descuento">$<?= number_format($cotizacion['descuento_monto'], 2) ?></td>
                        </tr>
                        <tr class="table-active">
                            <td><strong>Total:</strong></td>
                            <td class="text-end" id="total">$<?= number_format($cotizacion['total'], 2) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="productos_json" id="productos_json" value="">
        <input type="hidden" name="servicios_json" id="servicios_json" value="">
        <input type="hidden" name="insumos_json" id="insumos_json" value="">
        
        <div class="d-flex justify-content-between">
            <a href="index.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Guardar Cambios
            </button>
        </div>
    </form>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../assets/js/script.js"></script>
<script>
    document.querySelector('.sidebar-cotizaciones').classList.add('active');
    
    // Productos existentes de PHP
    const productosExistentes = <?= json_encode($productos_existentes) ?>;
    
    // Inicializar Select2
    $(document).ready(function() {
        $('#cliente_select, #producto_select').select2();
        
        // Cargar productos existentes
        productosExistentes.forEach(producto => {
            agregarProducto(producto);
        });
        
        // Actualizar totales iniciales
        actualizarTotales();
    });

    // Manejo del cliente
    document.getElementById('cliente_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            document.getElementById('cliente_nombre').value = selectedOption.dataset.nombre || '';
            document.getElementById('cliente_telefono').value = selectedOption.dataset.telefono || '';
            document.getElementById('cliente_ubicacion').value = selectedOption.dataset.ubicacion || '';
            document.getElementById('cliente_email').value = selectedOption.dataset.email || '';
        }
    });

    // Manejo de productos
    document.getElementById('producto_select').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            agregarProducto({
                product_id: this.value,
                nombre: selectedOption.dataset.nombre,
                sku: selectedOption.dataset.sku,
                precio: selectedOption.dataset.precio,
                cantidad: 1
            });
            this.value = '';
            $('#producto_select').val('').trigger('change');
        }
    });

    // Funciones para manejar productos
    function agregarProducto(producto) {
        const tbody = document.querySelector('#tablaProductos tbody');
        const esBobina = producto.tipo_gestion === 'bobina';
        const step = esBobina ? '0.01' : '1';
        const min = esBobina ? '0.01' : '1';
        const unidad = esBobina ? ' m' : '';
        const cantidad = esBobina ? parseFloat(producto.cantidad) : Math.max(1, Math.round(parseFloat(producto.cantidad) || 1));
        const row = document.createElement('tr');
        row.dataset.productId = producto.product_id;
        row.dataset.tipoGestion = producto.tipo_gestion || '';
        row.innerHTML = `
            <td>
                ${producto.imagen ? `<img src="../${producto.imagen}" alt="Imagen" style="height:32px;max-width:40px;margin-right:6px;vertical-align:middle;">` : ''}
                ${producto.nombre}
            </td>
            <td>${producto.sku || ''}</td>
            <td><input type="number" class="form-control form-control-sm cantidad-input" value="${cantidad}" min="${min}" step="${step}" style="width: 80px;">${unidad}</td>
            <td><input type="number" class="form-control form-control-sm precio-input" value="${producto.precio}" min="0" step="0.01" style="width: 100px;"></td>
            <td class="total-fila">$${(producto.precio * cantidad).toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-product"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        actualizarTotales();
    }

    // Eventos para cantidad y precio
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('cantidad-input') || e.target.classList.contains('precio-input')) {
            const row = e.target.closest('tr');
            let cantidad = row.querySelector('.cantidad-input').value;
            const tipoGestion = row.dataset.tipogestion;
            if (tipoGestion === 'bobina') {
                cantidad = parseFloat(cantidad) || 0.01;
            } else {
                cantidad = Math.max(1, Math.round(parseFloat(cantidad) || 1));
            }
            row.querySelector('.cantidad-input').value = cantidad;
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            const total = cantidad * precio;
            row.querySelector('.total-fila').textContent = '$' + total.toFixed(2);
            actualizarTotales();
        }
    });

    // Eliminar producto
    document.addEventListener('click', function(e) {
        if (e.target.closest('.btn-remove-product')) {
            e.target.closest('tr').remove();
            actualizarTotales();
        }
    });

    // Actualizar totales
    function actualizarTotales() {
        let subtotal = 0;
        document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
            const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            subtotal += cantidad * precio;
        });
        
        const descuentoPorcentaje = parseFloat(document.getElementById('descuento_porcentaje').value) || 0;
        const descuento = subtotal * descuentoPorcentaje / 100;
        const total = subtotal - descuento;
        
        document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('descuento').textContent = '$' + descuento.toFixed(2);
        document.getElementById('total').textContent = '$' + total.toFixed(2);
    }

    // Actualizar descuento
    document.getElementById('descuento_porcentaje').addEventListener('input', actualizarTotales);

    // Preparar datos para envío
    document.getElementById('formEditarCotizacion').addEventListener('submit', function(e) {
        const productos = [];
        let error = '';
        document.querySelectorAll('#tablaProductos tbody tr').forEach(row => {
            const productId = row.dataset.productId;
            let cantidad = row.querySelector('.cantidad-input').value;
            const tipoGestion = row.dataset.tipogestion;
            if (tipoGestion === 'bobina') {
                cantidad = parseFloat(cantidad) || 0.01;
            } else {
                cantidad = Math.max(1, Math.round(parseFloat(cantidad) || 1));
            }
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            if (productId && cantidad > 0 && precio > 0) {
                productos.push({
                    product_id: productId,
                    cantidad: cantidad,
                    precio: precio
                });
            }
        });
        
        if (productos.length === 0) {
            e.preventDefault();
            alert('Debes agregar al menos un producto a la cotización.');
            return false;
        }
        
        document.getElementById('productos_json').value = JSON.stringify(productos);
    });

    // Servicios existentes de PHP
    const serviciosExistentes = <?= json_encode($servicios_existentes) ?>;
    const serviciosArray = <?= json_encode($servicios_array) ?>;
    // Inicializar servicios existentes
    $(document).ready(function() {
        serviciosExistentes.forEach(servicio => {
            agregarServicio(servicio);
        });
    });
    // Manejo de servicios
    $('#servicio_select').on('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (this.value) {
            agregarServicio({
                servicio_id: this.value,
                nombre: selectedOption.dataset.nombre,
                categoria: selectedOption.dataset.categoria,
                descripcion: selectedOption.dataset.descripcion,
                precio: selectedOption.dataset.precio,
                cantidad: 1,
                imagen: selectedOption.dataset.imagen
            });
            this.value = '';
            $('#servicio_select').val('').trigger('change');
        }
    });
    function agregarServicio(servicio) {
        const tbody = document.querySelector('#tablaServicios tbody');
        const cantidad = Math.max(1, Math.round(parseFloat(servicio.cantidad) || 1));
        const row = document.createElement('tr');
        row.dataset.servicioId = servicio.servicio_id;
        row.innerHTML = `
            <td>${servicio.imagen ? `<img src="../uploads/services/${servicio.imagen}" alt="Imagen" style="height:32px;max-width:40px;margin-right:6px;vertical-align:middle;">` : ''}${servicio.nombre}</td>
            <td>${servicio.categoria || ''}</td>
            <td>${servicio.descripcion || ''}</td>
            <td><input type="number" class="form-control form-control-sm cantidad-servicio-input" value="${cantidad}" min="1" step="1" style="width: 80px;"></td>
            <td><input type="number" class="form-control form-control-sm precio-servicio-input" value="${servicio.precio}" min="0" step="0.01" style="width: 100px;"></td>
            <td class="total-fila-servicio">$${(servicio.precio * cantidad).toFixed(2)}</td>
            <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-servicio"><i class="bi bi-trash"></i></button></td>
        `;
        tbody.appendChild(row);
        actualizarTotalesServicios();
    }
    // Eventos para cantidad y precio de servicios
    $(document).on('input', '.cantidad-servicio-input, .precio-servicio-input', function() {
        const row = $(this).closest('tr');
        let cantidad = Math.max(1, Math.round(parseFloat(row.find('.cantidad-servicio-input').val()) || 1));
        row.find('.cantidad-servicio-input').val(cantidad);
        const precio = parseFloat(row.find('.precio-servicio-input').val()) || 0;
        row.find('.total-fila-servicio').text('$' + (precio * cantidad).toFixed(2));
        actualizarTotalesServicios();
    });
    // Eliminar servicio
    $(document).on('click', '.btn-remove-servicio', function() {
        $(this).closest('tr').remove();
        actualizarTotalesServicios();
    });
    // Actualizar totales de servicios
    function actualizarTotalesServicios() {
        let subtotal = 0;
        $('#tablaServicios tbody tr').each(function() {
            const cantidad = parseFloat($(this).find('.cantidad-servicio-input').val()) || 0;
            const precio = parseFloat($(this).find('.precio-servicio-input').val()) || 0;
            subtotal += cantidad * precio;
        });
        // Suma al subtotal de productos
        let subtotalProductos = 0;
        $('#tablaProductos tbody tr').each(function() {
            const cantidad = parseFloat($(this).find('.cantidad-input').val()) || 0;
            const precio = parseFloat($(this).find('.precio-input').val()) || 0;
            subtotalProductos += cantidad * precio;
        });
        const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
        const descuento = (subtotal + subtotalProductos) * descuentoPorcentaje / 100;
        const total = subtotal + subtotalProductos - descuento;
        $('#subtotal').text('$' + (subtotal + subtotalProductos).toFixed(2));
        $('#descuento').text('$' + descuento.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }
    // Guardar servicios al enviar
    $('#formEditarCotizacion').on('submit', function(e) {
        const servicios = [];
        $('#tablaServicios tbody tr').each(function() {
            const servicioId = $(this).data('servicioid');
            const nombre = $(this).find('td').eq(0).text().trim();
            const categoria = $(this).find('td').eq(1).text().trim();
            const descripcion = $(this).find('td').eq(2).text().trim();
            const cantidad = Math.max(1, Math.round(parseFloat($(this).find('.cantidad-servicio-input').val()) || 1));
            const precio = parseFloat($(this).find('.precio-servicio-input').val()) || 0;
            let imagen = '';
            const imgTag = $(this).find('img');
            if (imgTag.length) {
                imagen = imgTag.attr('src').replace('../uploads/services/', '');
            }
            if (nombre && cantidad > 0 && precio > 0) {
                servicios.push({
                    servicio_id: servicioId,
                    nombre: nombre,
                    categoria: categoria,
                    descripcion: descripcion,
                    cantidad: cantidad,
                    precio: precio,
                    imagen: imagen
                });
            }
        });
        $('#servicios_json').val(JSON.stringify(servicios));
    });

    // INSUMOS
    const insumosExistentes = <?= json_encode($insumos_existentes) ?>;
    let insumosCotizacion = [...insumosExistentes];
    function renderTablaInsumos() {
        let html = '';
        insumosCotizacion.forEach((ins, i) => {
            const sub = (parseFloat(ins.precio) || 0) * (parseFloat(ins.cantidad) || 1);
            html += `
                <tr>
                    <td>${ins.nombre}</td>
                    <td>${ins.categoria || ''}</td>
                    <td>${ins.proveedor || ''}</td>
                    <td>${ins.stock || ''}</td>
                    <td><input type="number" min="1" step="1" value="${ins.cantidad}" class="form-control form-control-sm cantidad-insumo-input" data-index="${i}" style="width: 80px;"></td>
                    <td><input type="number" min="0" step="0.0001" value="${ins.precio || ''}" class="form-control form-control-sm precio-insumo-input" data-index="${i}" style="width: 110px;"></td>
                    <td>$${sub.toFixed(2)}</td>
                    <td><button type="button" class="btn btn-danger btn-sm btn-eliminar-insumo" data-idx="${i}"><i class="bi bi-trash"></i></button></td>
                </tr>
            `;
        });
        $('#tablaInsumosCotizacion tbody').html(html);
        actualizarTotalesGenerales();
    }
    $(document).on('input', '.cantidad-insumo-input', function() {
        const index = parseInt($(this).data('index'));
        let value = Math.max(1, Math.round($(this).val()));
        insumosCotizacion[index].cantidad = value;
        $(this).val(value);
        renderTablaInsumos();
    });
    $(document).on('input', '.precio-insumo-input', function() {
        const index = parseInt($(this).data('index'));
        let value = parseFloat($(this).val()) || 0;
        insumosCotizacion[index].precio = value;
        $(this).val(value);
        renderTablaInsumos();
    });
    $(document).on('click', '.btn-eliminar-insumo', function() {
        const idx = $(this).data('idx');
        insumosCotizacion.splice(idx, 1);
        renderTablaInsumos();
    });
    $('#buscador_insumo').on('input', function() {
        const query = $(this).val().trim();
        if (query.length === 0) {
            $('#sugerencias_insumos').hide();
            return;
        }
        $.getJSON('../insumos/ajax_list.php', { busqueda: query }, function(resp) {
            let sugerencias = '';
            if (resp.success && resp.data.length > 0) {
                resp.data.forEach(ins => {
                    sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${ins.insumo_id}' data-nombre='${ins.nombre}' data-categoria='${ins.categoria_nombre||''}' data-proveedor='${ins.proveedor||''}' data-stock='${ins.cantidad}' data-precio='${ins.precio_unitario}'>
                        <b>${ins.nombre}</b> <span class='badge bg-${ins.cantidad > 0 ? 'success' : 'danger'} ms-2'>Stock: ${ins.cantidad}</span><br>
                        <small>${ins.categoria_nombre || '-'} | ${ins.proveedor || '-'}</small>
                    </button>`;
                });
            } else {
                sugerencias = '<div class="list-group-item">Sin resultados</div>';
            }
            $('#sugerencias_insumos').html(sugerencias).show();
        });
    });
    $('#sugerencias_insumos').on('click', 'button', function() {
        const insumo = {
            insumo_id: $(this).data('id'),
            nombre: $(this).data('nombre'),
            categoria: $(this).data('categoria'),
            proveedor: $(this).data('proveedor'),
            stock: $(this).data('stock'),
            cantidad: 1,
            precio: $(this).data('precio')
        };
        // Evitar duplicados
        if (insumosCotizacion.some(i => i.insumo_id == insumo.insumo_id)) {
            return;
        }
        insumosCotizacion.push(insumo);
        renderTablaInsumos();
        $('#buscador_insumo').val('');
        $('#sugerencias_insumos').hide();
    });
    // Actualizar totales generales (productos + servicios + insumos)
    function actualizarTotalesGenerales() {
        let subtotalProductos = 0;
        $('#tablaProductos tbody tr').each(function() {
            const cantidad = parseFloat($(this).find('.cantidad-input').val()) || 0;
            const precio = parseFloat($(this).find('.precio-input').val()) || 0;
            subtotalProductos += cantidad * precio;
        });
        let subtotalServicios = 0;
        $('#tablaServicios tbody tr').each(function() {
            const cantidad = parseFloat($(this).find('.cantidad-servicio-input').val()) || 0;
            const precio = parseFloat($(this).find('.precio-servicio-input').val()) || 0;
            subtotalServicios += cantidad * precio;
        });
        let subtotalInsumos = 0;
        insumosCotizacion.forEach(ins => {
            subtotalInsumos += (parseFloat(ins.precio) || 0) * (parseFloat(ins.cantidad) || 1);
        });
        const subtotal = subtotalProductos + subtotalServicios + subtotalInsumos;
        const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
        const descuento = subtotal * descuentoPorcentaje / 100;
        const total = subtotal - descuento;
        $('#subtotal').text('$' + subtotal.toFixed(2));
        $('#descuento').text('$' + descuento.toFixed(2));
        $('#total').text('$' + total.toFixed(2));
    }
    // Llamar al render al cargar
    $(document).ready(function() {
        renderTablaInsumos();
    });
    // Guardar insumos al enviar
    $('#formEditarCotizacion').on('submit', function(e) {
        $('#insumos_json').val(JSON.stringify(insumosCotizacion));
    });
</script>
</body>
</html> 