<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

// --- Preparar datos para selects ---
$clientes = $mysqli->query("SELECT cliente_id, nombre, telefono, ubicacion, email FROM clientes ORDER BY nombre ASC");
$clientes_array = $clientes ? $clientes->fetch_all(MYSQLI_ASSOC) : [];
$productos = $mysqli->query("SELECT p.product_id, p.product_name, p.sku, p.price, p.quantity, c.name as categoria, s.name as proveedor FROM products p LEFT JOIN categories c ON p.category_id = c.category_id LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id ORDER BY p.product_name ASC");
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
    $cliente_id = $_POST['cliente_id'] ?? '';
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $cliente_telefono = trim($_POST['cliente_telefono'] ?? '');
    $cliente_ubicacion = trim($_POST['cliente_ubicacion'] ?? '');
    $cliente_email = trim($_POST['cliente_email'] ?? '');
    if (!$productos || !is_array($productos) || count($productos) == 0) {
        $error = 'Debes agregar al menos un producto a la cotización.';
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
        // Cotización
        $fecha_cotizacion = $_POST['fecha_cotizacion'];
        $validez_dias = intval($_POST['validez_dias']);
        $condiciones_pago = trim($_POST['condiciones_pago']);
        $observaciones = trim($_POST['observaciones']);
        $descuento_porcentaje = floatval($_POST['descuento_porcentaje']);
        $estado_id = intval($_POST['estado_id']);
        $usuario_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        
        // Calcular totales
        $subtotal = 0;
        foreach ($productos as $prod) {
            $subtotal += floatval($prod['precio']) * intval($prod['cantidad']);
        }
        $descuento_monto = $subtotal * $descuento_porcentaje / 100;
        $total = $subtotal - $descuento_monto;
        
        // Generar número de cotización automáticamente
        $year = date('Y');
        $stmt_count = $mysqli->prepare("SELECT COUNT(*) FROM cotizaciones WHERE numero_cotizacion LIKE ?");
        $pattern = "COT-$year-%";
        $stmt_count->bind_param('s', $pattern);
        $stmt_count->execute();
        $stmt_count->bind_result($count);
        $stmt_count->fetch();
        $stmt_count->close();
        
        $next_number = $count + 1;
        $numero_cotizacion = sprintf("COT-%s-%04d", $year, $next_number);
        
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, fecha_cotizacion, validez_dias, subtotal, descuento_porcentaje, descuento_monto, total, condiciones_pago, observaciones, estado_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sisidddssiii', $numero_cotizacion, $cliente_id, $fecha_cotizacion, $validez_dias, $subtotal, $descuento_porcentaje, $descuento_monto, $total, $condiciones_pago, $observaciones, $estado_id, $usuario_id);
        if ($stmt->execute()) {
            $cotizacion_id = $stmt->insert_id;
            $stmt->close();
            // Productos
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
                // Descontar stock si estado es aprobada (ID 1)
                if ($estado_id == 1) {
                    $mysqli->query("UPDATE products SET quantity = quantity - " . intval($prod['cantidad']) . " WHERE product_id = " . intval($product_id));
                }
            }
            header("Location: ver.php?id=$cotizacion_id");
            exit;
        } else {
            $error = 'Error al guardar la cotización: ' . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cotización</title>
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
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <form method="POST" id="formCrearCotizacion" autocomplete="off">
        <!-- Sección Cliente -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-person"></i> Cliente</div>
            <div class="mb-3">
                <label for="cliente_select" class="form-label">Seleccionar cliente</label>
                <select class="form-select" id="cliente_select" name="cliente_id">
                    <option value="">-- Nuevo cliente --</option>
                    <?php foreach ($clientes_array as $cl): ?>
                        <option value="<?= $cl['cliente_id'] ?>" data-nombre="<?= htmlspecialchars($cl['nombre']) ?>" data-telefono="<?= htmlspecialchars($cl['telefono']) ?>" data-ubicacion="<?= htmlspecialchars($cl['ubicacion']) ?>" data-email="<?= htmlspecialchars($cl['email']) ?>">
                            <?= htmlspecialchars($cl['nombre']) ?><?= $cl['telefono'] ? ' (' . htmlspecialchars($cl['telefono']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="camposNuevoCliente">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="cliente_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="cliente_nombre" id="cliente_nombre">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_telefono" class="form-label">Teléfono</label>
                        <input type="text" class="form-control" name="cliente_telefono" id="cliente_telefono">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_ubicacion" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" name="cliente_ubicacion" id="cliente_ubicacion">
                    </div>
                    <div class="col-md-3">
                        <label for="cliente_email" class="form-label">Email</label>
                        <input type="email" class="form-control" name="cliente_email" id="cliente_email">
                    </div>
                </div>
            </div>
        </div>
        <!-- Sección Productos -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-box"></i> Productos</div>
            <div class="mb-3">
                <label for="buscador_producto" class="form-label">Buscar producto en inventario</label>
                <input type="text" class="form-control" id="buscador_producto" placeholder="Nombre, SKU o descripción...">
                <div id="sugerencias_productos" class="list-group mt-1"></div>
            </div>
            <div class="mb-3">
                <button type="button" class="btn btn-outline-primary" id="btnAltaRapidaProducto"><i class="bi bi-plus-circle"></i> Alta rápida de producto</button>
            </div>
            <div id="altaRapidaProductoForm" style="display:none;">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nuevo_nombre_producto">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" id="nuevo_sku_producto">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Precio</label>
                        <input type="number" class="form-control" id="nuevo_precio_producto" min="0" step="0.01">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cantidad</label>
                        <input type="number" class="form-control" id="nuevo_cantidad_producto" min="1" value="1">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Categoría</label>
                        <select class="form-select" id="nuevo_categoria_producto">
                            <option value="">-</option>
                            <?php if ($categorias) while ($cat = $categorias->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Proveedor</label>
                        <select class="form-select" id="nuevo_proveedor_producto">
                            <option value="">-</option>
                            <?php if ($proveedores) while ($prov = $proveedores->fetch_assoc()): ?>
                                <option value="<?= $prov['supplier_id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-success" id="btnAgregarProductoRapido"><i class="bi bi-check-circle"></i> Agregar producto</button>
                </div>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-striped align-middle" id="tablaProductosCotizacion">
                    <thead class="table-dark">
                        <tr>
                            <th>Nombre</th>
                            <th>SKU</th>
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
        <!-- Sección Estado y Condiciones -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-info-circle"></i> Estado y Condiciones</div>
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Fecha de cotización</label>
                    <input type="date" name="fecha_cotizacion" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Validez (días)</label>
                    <input type="number" name="validez_dias" class="form-control" value="30" min="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado de la cotización <i class="bi bi-exclamation-triangle text-warning" title="Solo al aprobar se descuenta stock"></i></label>
                    <select class="form-select" name="estado_id" id="estado_id" required>
                        <?php foreach ($estados_array as $e): ?>
                            <option value="<?= $e['est_cot_id'] ?>"<?= $e['est_cot_id'] == 2 ? ' selected' : '' ?>><?= htmlspecialchars($e['nombre_estado']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Solo cuando el estado sea <b>Aprobada</b> se descontarán los productos del stock.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Condiciones de pago</label>
                    <input type="text" name="condiciones_pago" class="form-control">
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-12">
                    <label class="form-label">Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>
        <!-- Sección Resumen -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-receipt"></i> Resumen</div>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Subtotal</label>
                    <input type="text" class="form-control" id="subtotal" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Descuento (%)</label>
                    <input type="number" name="descuento_porcentaje" class="form-control" id="descuento_porcentaje" min="0" max="100" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Descuento ($)</label>
                    <input type="text" class="form-control" id="descuento_monto" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total</label>
                    <input type="text" class="form-control" id="total" readonly>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mb-5">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle"></i> Guardar Cotización</button>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger mt-3"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
        <?php endif; ?>
    </form>
</main>
<script src="../assets/js/script.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
// --- CLIENTES ---
const clientesArray = <?= json_encode($clientes_array) ?>;
$(document).ready(function() {
    $('#cliente_select').select2({
        placeholder: 'Selecciona un cliente o deja vacío para nuevo',
        allowClear: true,
        width: '100%'
    });
    function toggleCamposCliente() {
        var clienteId = $('#cliente_select').val();
        if (clienteId) {
            var cliente = clientesArray.find(c => c.cliente_id == clienteId);
            if (cliente) {
                $('#cliente_nombre').val(cliente.nombre).prop('readonly', true);
                $('#cliente_telefono').val(cliente.telefono).prop('readonly', true);
                $('#cliente_ubicacion').val(cliente.ubicacion).prop('readonly', true);
                $('#cliente_email').val(cliente.email).prop('readonly', true);
            }
        } else {
            $('#cliente_nombre, #cliente_telefono, #cliente_ubicacion, #cliente_email').val('').prop('readonly', false);
        }
    }
    $('#cliente_select').on('change', toggleCamposCliente);
    toggleCamposCliente();
});

// --- PRODUCTOS ---
const productosArray = <?= json_encode($productos_array) ?>;
let productosCotizacion = [];

// Función para normalizar texto (quitar acentos y convertir a minúsculas)
function normalizarTexto(texto) {
    return texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

$('#buscador_producto').on('input', function() {
    const query = normalizarTexto($(this).val());
    let sugerencias = '';
    if (query.length > 0) {
        const filtrados = productosArray.filter(p => {
            const nombreNormalizado = normalizarTexto(p.product_name || '');
            const skuNormalizado = normalizarTexto(p.sku || '');
            const categoriaNormalizada = normalizarTexto(p.categoria || '');
            
            return nombreNormalizado.includes(query) || 
                   skuNormalizado.includes(query) || 
                   categoriaNormalizada.includes(query);
        });
        
        filtrados.forEach(p => {
            sugerencias += `<button type='button' class='list-group-item list-group-item-action' data-id='${p.product_id}' data-nombre='${p.product_name}' data-sku='${p.sku}' data-categoria='${p.categoria||''}' data-proveedor='${p.proveedor||''}' data-stock='${p.quantity}' data-precio='${p.price}'>
                <b>${p.product_name}</b> <span class='badge bg-${p.quantity > 0 ? 'success' : 'danger'} ms-2'>Stock: ${p.quantity}</span><br>
                <small>SKU: ${p.sku || '-'} | $${parseFloat(p.price).toFixed(2)}</small>
            </button>`;
        });
    }
    $('#sugerencias_productos').html(sugerencias).show();
});
$('#sugerencias_productos').on('click', 'button', function() {
    agregarProductoATabla({
        product_id: $(this).data('id'),
        nombre: $(this).data('nombre'),
        sku: $(this).data('sku'),
        categoria: $(this).data('categoria'),
        proveedor: $(this).data('proveedor'),
        stock: $(this).data('stock'),
        cantidad: 1,
        precio: $(this).data('precio')
    });
    $('#buscador_producto').val('');
    $('#sugerencias_productos').hide();
});
$('#btnAltaRapidaProducto').on('click', function() {
    $('#altaRapidaProductoForm').toggle();
});
$('#btnAgregarProductoRapido').on('click', function() {
    const nombre = $('#nuevo_nombre_producto').val();
    const precio = parseFloat($('#nuevo_precio_producto').val()) || 0;
    const cantidad = parseInt($('#nuevo_cantidad_producto').val()) || 1;
    if (!nombre || !precio || !cantidad) {
        alert('Completa nombre, precio y cantidad para el producto.');
        return;
    }
    agregarProductoATabla({
        product_id: null,
        nombre: nombre,
        sku: $('#nuevo_sku_producto').val(),
        categoria: $('#nuevo_categoria_producto option:selected').text(),
        category_id: $('#nuevo_categoria_producto').val(),
        proveedor: $('#nuevo_proveedor_producto option:selected').text(),
        supplier_id: $('#nuevo_proveedor_producto').val(),
        stock: '',
        cantidad: cantidad,
        precio: precio
    });
    $('#nuevo_nombre_producto, #nuevo_sku_producto, #nuevo_precio_producto, #nuevo_cantidad_producto').val('');
    $('#nuevo_categoria_producto, #nuevo_proveedor_producto').val('');
    $('#altaRapidaProductoForm').hide();
});
function agregarProductoATabla(prod) {
    productosCotizacion.push(prod);
    renderTablaProductos();
}
$(document).on('click', '.btn-eliminar-producto', function() {
    const idx = $(this).data('idx');
    productosCotizacion.splice(idx, 1);
    renderTablaProductos();
});
function renderTablaProductos() {
    let html = '';
    let subtotal = 0;
    
    productosCotizacion.forEach((p, i) => {
        const sub = (parseFloat(p.precio) || 0) * (parseInt(p.cantidad) || 1);
        subtotal += sub;
        
        html += `
            <tr>
                <td>${p.nombre}</td>
                <td>${p.sku || ''}</td>
                <td>${p.categoria || ''}</td>
                <td>${p.proveedor || ''}</td>
                <td>${p.stock}</td>
                <td>
                    <input type="number" 
                           min="1" 
                           step="1" 
                           value="${p.cantidad}" 
                           class="form-control form-control-sm cantidad-input" 
                           data-index="${i}" 
                           style="width: 80px;">
                </td>
                <td>
                    <input type="number" 
                           min="0" 
                           step="0.01" 
                           value="${p.precio}" 
                           class="form-control form-control-sm precio-input" 
                           data-index="${i}" 
                           style="width: 110px;">
                </td>
                <td>$${sub.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm btn-eliminar-producto" data-idx="${i}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    $('#tablaProductosCotizacion tbody').html(html);
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    recalcularTotales();
}

// Eventos para cantidad
$(document).on('input', '.cantidad-input', function() {
    const index = parseInt($(this).data('index'));
    const value = parseInt($(this).val()) || 1;
    
    if (productosCotizacion[index]) {
        productosCotizacion[index].cantidad = value;
        renderTablaProductos();
    }
});

$(document).on('focus', '.cantidad-input', function() {
    $(this).select();
});

// Eventos para precio
$(document).on('input', '.precio-input', function() {
    const index = parseInt($(this).data('index'));
    const value = parseFloat($(this).val()) || 0;
    
    if (productosCotizacion[index]) {
        productosCotizacion[index].precio = value;
        renderTablaProductos();
    }
});

$(document).on('focus', '.precio-input', function() {
    $(this).select();
});
function recalcularTotales() {
    const subtotal = productosCotizacion.reduce((sum, p) => sum + ((parseFloat(p.precio)||0)*(parseInt(p.cantidad)||1)), 0);
    const descuentoPorcentaje = parseFloat($('#descuento_porcentaje').val()) || 0;
    const descuentoMonto = subtotal * descuentoPorcentaje / 100;
    const total = subtotal - descuentoMonto;
    $('#subtotal').val(`$${subtotal.toFixed(2)}`);
    $('#descuento_monto').val(`$${descuentoMonto.toFixed(2)}`);
    $('#total').val(`$${total.toFixed(2)}`);
}
$('#descuento_porcentaje').on('input', recalcularTotales);
$('#formCrearCotizacion').on('submit', function(e) {
    let error = '';
    const clienteId = $('#cliente_select').val();
    const nombre = $('#cliente_nombre').val();
    if (!clienteId && !nombre) {
        error = 'Debes seleccionar o registrar un cliente.';
    }
    if (productosCotizacion.length === 0) {
        error = 'Debes agregar al menos un producto a la cotización.';
    }
    if (error) {
        e.preventDefault();
        alert(error);
        return false;
    }
    $('<input>').attr({type:'hidden', name:'productos_json', value: JSON.stringify(productosCotizacion)}).appendTo(this);
    $(this).find('button[type=submit]').prop('disabled', true).text('Guardando...');
});
</script>
</body>
</html> 