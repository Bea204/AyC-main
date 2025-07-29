<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_GET['id']);

// Obtener cotización
$stmt = $mysqli->prepare("
    SELECT c.*, u.username as usuario_nombre, cl.nombre as cliente_nombre_real, cl.telefono as cliente_telefono_real, cl.ubicacion as cliente_direccion_real, ec.nombre_estado
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
    SELECT cp.*, p.product_name, p.sku, p.image as product_image, p.cost_price, p.tipo_gestion,
           CASE 
               WHEN p.tipo_gestion = 'bobina' THEN 
                   COALESCE(SUM(b.metros_actuales), 0)
               ELSE 
                   p.quantity
           END as stock_actual
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
    WHERE cp.cotizacion_id = ?
    GROUP BY cp.cotizacion_producto_id, cp.cotizacion_id, cp.product_id, cp.cantidad, cp.precio_unitario, cp.precio_total, p.product_name, p.sku, p.image, p.cost_price, p.tipo_gestion, p.quantity
    ORDER BY cp.cotizacion_producto_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos = $stmt->get_result();

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
$servicios = $stmt->get_result();

// Obtener insumos de la cotización
$stmt = $mysqli->prepare("
    SELECT ci.*, i.nombre as insumo_nombre, i.categoria as insumo_categoria, i.cantidad as insumo_stock, i.precio_unitario as insumo_precio, i.imagen as insumo_imagen, c.name as categoria_nombre, s.name as proveedor_nombre
    FROM cotizaciones_insumos ci
    LEFT JOIN insumos i ON ci.insumo_id = i.insumo_id
    LEFT JOIN categories c ON i.category_id = c.category_id
    LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
    WHERE ci.cotizacion_id = ?
    ORDER BY ci.cotizacion_insumo_id
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$insumos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización <?= $cotizacion['numero_cotizacion'] ?> | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #232a7c; margin: 0; padding: 0; }
        .cotizacion-container { 
            background: #fff; 
            max-width: 1200px; 
            margin: 30px auto; 
            box-shadow: 0 4px 24px rgba(18,24,102,0.10); 
            border-radius: 14px; 
            padding: 32px 36px 24px 36px; 
        }
        .cotizacion-header { 
            display: flex; 
            align-items: flex-start; 
            justify-content: space-between; 
            border-bottom: 2px solid #232a7c; 
            padding-bottom: 18px; 
            margin-bottom: 18px; 
        }
        .logo-empresa { height: 80px; margin-right: 18px; }
        .datos-empresa { flex: 1; }
        .datos-empresa h2 { font-size: 1.5rem; margin: 0 0 4px 0; color: #121866; }
        .datos-empresa p { margin: 0; font-size: 1rem; color: #232a7c; }
        .cotizacion-info { text-align: right; }
        .cotizacion-info .titulo-cot { 
            background: #ff9800; 
            color: #fff; 
            font-weight: 700; 
            padding: 6px 18px; 
            border-radius: 8px; 
            font-size: 1.1rem; 
            margin-bottom: 8px; 
            display: inline-block; 
        }
        .cotizacion-info img { height: 28px; margin-left: 8px; vertical-align: middle; }
        .datos-cliente { 
            margin: 18px 0 10px 0; 
            display: flex; 
            flex-wrap: wrap; 
            gap: 32px; 
        }
        .datos-cliente .campo { font-size: 1rem; margin-bottom: 2px; }
        .datos-cliente .campo strong { color: #121866; min-width: 90px; display: inline-block; }
        .tabla-cotizacion { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 0.98rem; 
        }
        .tabla-cotizacion th, .tabla-cotizacion td { 
            border: 1px solid #b0b6d0; 
            padding: 8px 6px; 
            text-align: center; 
            vertical-align: middle; 
        }
        .tabla-cotizacion th { background: #232a7c; color: #fff; font-size: 1rem; }
        .tabla-cotizacion td.descripcion { text-align: left; font-size: 0.97rem; }
        .tabla-cotizacion td.imagen { background: #f7f9fc; }
        .tabla-cotizacion td.precio-total { font-weight: 700; color: #232a7c; }
        .tabla-cotizacion td.costo-total { background: #e0e0e0; color: #232a7c; font-weight: 500; }
        .tabla-cotizacion tfoot td { font-weight: 700; background: #f4f6fb; color: #121866; }
        .condiciones { margin-top: 18px; font-size: 0.98rem; }
        .condiciones strong { color: #121866; }
        .btn { 
            background: #232a7c; 
            color: #fff; 
            border: none; 
            border-radius: 6px; 
            padding: 7px 16px; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
            margin: 4px 0; 
            transition: background 0.15s; 
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #121866; color: #fff; text-decoration: none; }
        .btn-danger { background: #e53935; }
        .btn-danger:hover { background: #b71c1c; }
        .btn-success { background: #43a047; }
        .btn-success:hover { background: #2e7d32; }
        .btn-warning { background: #ff9800; }
        .btn-warning:hover { background: #f57c00; }
        .estado-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        .estado-borrador { background: #fff3cd; color: #856404; }
        .estado-enviada { background: #d1ecf1; color: #0c5460; }
        .estado-aprobada { background: #d4edda; color: #155724; }
        .estado-rechazada { background: #f8d7da; color: #721c24; }
        .estado-convertida { background: #d1ecf1; color: #0c5460; }
        .acciones-cotizacion {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .alert {
            padding: 12px 16px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            border-radius: 8px;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .btn-close {
            float: right;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1;
            color: #000;
            opacity: .5;
            background: none;
            border: 0;
            cursor: pointer;
        }
        .btn-close:hover {
            opacity: .75;
        }
        @media print { 
            body { background: #fff; } 
            .cotizacion-container { box-shadow: none; border-radius: 0; margin: 0; padding: 0; } 
            th.costo-total, td.costo-total { display: none !important; }
            .badge.bg-danger { display: none !important; }
            .text-muted { display: none !important; }
            .acciones-cotizacion { display: none !important; }
        }
        .alert.shadow.rounded-4 {
            box-shadow: 0 4px 24px rgba(18,24,102,0.10) !important;
            border-radius: 1.2rem !important;
        }
        #modalConfirmarEstado .modal-content {
            border-radius: 1rem;
            box-shadow: 0 6px 32px rgba(18,24,102,0.13);
        }
        #modalConfirmarEstado .modal-header {
            background: #232a7c;
            color: #fff;
            border-top-left-radius: 1rem;
            border-top-right-radius: 1rem;
        }
        #modalConfirmarEstado .modal-footer {
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
        }
        #modalConfirmarEstado .btn-success {
            background: #43a047;
            border: none;
        }
        #modalConfirmarEstado .btn-success:hover {
            background: #388e3c;
        }
    </style>
</head>
<body>
    <div class="cotizacion-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow rounded-4" role="alert" style="font-size:1.08rem;">
                <i class="bi bi-check-circle-fill me-2"></i> <?= 
                    $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow rounded-4" role="alert" style="font-size:1.08rem;">
                <i class="bi bi-x-octagon-fill me-2"></i> <?= 
                    $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="acciones-cotizacion">
            <a href="index.php" class="btn" style="background:#757575;">← Volver al listado</a>
            <a href="editar.php?id=<?= $cotizacion_id ?>" class="btn btn-success">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="historial.php?id=<?= $cotizacion_id ?>" class="btn btn-info">
                <i class="bi bi-clock-history"></i> Historial
            </a>
            <a href="#" class="btn" onclick="window.print(); return false;">
                <i class="bi bi-printer"></i> Imprimir
            </a>
            
            <!-- Botones de cambio de estado -->
            <?php if ($cotizacion['nombre_estado'] === 'Borrador'): ?>
                <button type="button" class="btn btn-primary" onclick="cambiarEstado(<?= $cotizacion_id ?>, 2, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-send"></i> Enviar
                </button>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Enviada'): ?>
                <button type="button" class="btn btn-success" onclick="cambiarEstado(<?= $cotizacion_id ?>, 3, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
                <button type="button" class="btn btn-warning" onclick="cambiarEstado(<?= $cotizacion_id ?>, 4, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-x-circle"></i> Rechazar
                </button>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Aprobada'): ?>
                <button type="button" class="btn btn-warning" onclick="cambiarEstado(<?= $cotizacion_id ?>, 4, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-x-circle"></i> Rechazar
                </button>
                <a href="convertir.php?id=<?= $cotizacion_id ?>" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Convertir a Venta
                </a>
            <?php endif; ?>
            
            <?php if ($cotizacion['nombre_estado'] === 'Rechazada'): ?>
                <button type="button" class="btn btn-success" onclick="cambiarEstado(<?= $cotizacion_id ?>, 3, <?= $cotizacion['estado_id'] ?>)">
                    <i class="bi bi-check-circle"></i> Aprobar
                </button>
            <?php endif; ?>
            
            <span class="estado-badge estado-<?= strtolower($cotizacion['nombre_estado']) ?>">
                <?= htmlspecialchars($cotizacion['nombre_estado']) ?>
            </span>
        </div>

        <div class="cotizacion-header">
            <img src="../assets/img/LogoWeb.png" alt="Logo empresa" class="logo-empresa">
            <div class="datos-empresa">
                <h2>ALARMAS & CAMARAS DEL SURESTE</h2>
                <p>999 134 3979</p>
                <p>Mérida, Yucatán</p>
            </div>
            <div class="cotizacion-info">
                <div class="titulo-cot">Cotización</div>
                <div><strong><?= htmlspecialchars($cotizacion['numero_cotizacion']) ?></strong></div>
                <div>Fecha: <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) ?></div>
                <div>Válida hasta: <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'] . ' + ' . $cotizacion['validez_dias'] . ' days')) ?></div>
                <br>
                <div class="logos-dinamicos"></div>
            </div>
        </div>

        <div class="datos-cliente">
            <div class="campo"><strong>Cliente:</strong> <?= htmlspecialchars($cotizacion['cliente_nombre_real'] ?? $cotizacion['cliente_nombre']) ?></div>
            <?php if (($cotizacion['cliente_telefono_real'] ?? $cotizacion['cliente_telefono'])): ?>
                <div class="campo"><strong>Teléfono:</strong> <?= htmlspecialchars($cotizacion['cliente_telefono_real'] ?? $cotizacion['cliente_telefono']) ?></div>
            <?php endif; ?>
            <?php if (($cotizacion['cliente_direccion_real'] ?? $cotizacion['cliente_ubicacion'])): ?>
                <div class="campo"><strong>Ubicación:</strong> <?= htmlspecialchars($cotizacion['cliente_direccion_real'] ?? $cotizacion['cliente_ubicacion']) ?></div>
            <?php endif; ?>
            <div class="campo"><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($cotizacion['fecha_cotizacion'])) ?></div>
        </div>

        <table class="tabla-cotizacion">
            <thead>
                <tr>
                    <th>ITEM</th>
                    <th>DESCRIPCIÓN DEL PRODUCTO / SERVICIO</th>
                    <th>IMAGEN ILUSTRATIVA</th>
                    <th>CANT</th>
                    <th>PRECIO UNITARIO</th>
                    <th>PRECIO TOTAL</th>
                    <th class="costo-total">COSTO TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($producto = $productos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion">
                            <?php
                            $nombre = $producto['product_name'] ?? $producto['descripcion'] ?? 'Producto sin nombre';
                            echo htmlspecialchars($nombre);
                            
                            // Mostrar badge "Sin stock" si el stock actual es menor que la cantidad requerida
                            if ($producto['product_id'] && $producto['stock_actual'] < $producto['cantidad']) {
                                echo '<span class="badge bg-danger ms-2" style="font-size: 0.75rem;">Sin stock</span>';
                                // Para bobinas, mostrar información adicional
                                if ($producto['product_id']) {
                                    $stmt_tipo = $mysqli->prepare("SELECT tipo_gestion FROM products WHERE product_id = ?");
                                    $stmt_tipo->bind_param('i', $producto['product_id']);
                                    $stmt_tipo->execute();
                                    $tipo_gestion = $stmt_tipo->get_result()->fetch_assoc()['tipo_gestion'] ?? '';
                                    $stmt_tipo->close();
                                    
                                    if ($tipo_gestion === 'bobina') {
                                        echo '<br><small class="text-muted">Stock disponible: ' . number_format($producto['stock_actual'], 2) . 'm | Solicitado: ' . number_format($producto['cantidad'], 2) . 'm</small>';
                                    }
                                }
                            }
                            ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $producto['product_image'] ?? '';
                            if ($img && strpos($img, 'uploads/products/') === false) {
                                $img = 'uploads/products/' . $img;
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen" style="height:38px;">
                            <?php else: ?>
                                <span style="color:#ccc;">Sin imagen</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $producto['cantidad'] ?></td>
                        <td>$<?= number_format($producto['precio_unitario'], 2) ?></td>
                        <td class="precio-total">$<?= number_format($producto['precio_total'], 2) ?></td>
                        <td class="costo-total">
                            <?php
                            if (!empty($producto['cost_price'])) {
                                echo '$' . number_format($producto['cost_price'] * $producto['cantidad'], 2);
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php while ($insumo = $insumos->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion" style="max-width:320px;word-break:break-word;white-space:normal;">
                            <strong><?= htmlspecialchars($insumo['nombre_insumo'] ?? $insumo['insumo_nombre']) ?></strong>
                            <?php if ($insumo['categoria'] || $insumo['categoria_nombre']): ?>
                                <br><span class="badge bg-info" style="font-size: 0.75rem;"><?= htmlspecialchars($insumo['categoria'] ?? $insumo['categoria_nombre']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $insumo['insumo_imagen'] ?? '';
                            if ($img) {
                                if (strpos($img, 'uploads/insumos/') === false) {
                                    $img = 'uploads/insumos/' . $img;
                                }
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen Insumo" style="height:38px;">
                            <?php else: ?>
                                <span style="color:#ccc;"><i class="bi bi-tools"></i> Insumo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($insumo['cantidad'], 0) ?></td>
                        <td>$<?= number_format($insumo['precio_unitario'], 2) ?></td>
                        <td class="precio-total">$<?= number_format($insumo['precio_total'], 2) ?></td>
                        <td class="costo-total">N/A</td>
                    </tr>
                <?php endwhile; ?>
                
                <?php while ($servicio = $servicios->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="descripcion">
                            <strong><?= htmlspecialchars($servicio['nombre_servicio']) ?></strong>
                            <?php if ($servicio['descripcion']): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($servicio['descripcion']) ?></small>
                            <?php endif; ?>
                            <?php if ($servicio['servicio_categoria']): ?>
                                <br><span class="badge bg-info" style="font-size: 0.75rem;"><?= htmlspecialchars($servicio['servicio_categoria']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="imagen">
                            <?php
                            $img = $servicio['imagen'] ?? $servicio['servicio_imagen'] ?? '';
                            if ($img) {
                                if (strpos($img, 'uploads/services/') === false) {
                                    $img = 'uploads/services/' . $img;
                                }
                            }
                            ?>
                            <?php if ($img): ?>
                                <img src="../<?= htmlspecialchars($img) ?>" alt="Imagen" style="height:38px;">
                            <?php else: ?>
                                <span style="color:#ccc;"><i class="bi bi-tools"></i> Servicio</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format($servicio['cantidad'], 0) ?></td>
                        <td>$<?= number_format($servicio['precio_unitario'], 2) ?></td>
                        <td class="precio-total">$<?= number_format($servicio['precio_total'], 2) ?></td>
                        <td class="costo-total">N/A</td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">SUBTOTAL</td>
                    <td colspan="2" style="text-align:center;">$<?= number_format($cotizacion['subtotal'], 2) ?></td>
                </tr>
                <?php if ($cotizacion['descuento_porcentaje'] > 0): ?>
                    <tr>
                        <td colspan="5" style="text-align:right;">DESCUENTO (<?= $cotizacion['descuento_porcentaje'] ?>%)</td>
                        <td colspan="2" style="text-align:center;">$<?= number_format($cotizacion['descuento_monto'], 2) ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="5" style="text-align:right; font-size:1.1rem; color:#121866;">TOTAL</td>
                    <td colspan="2" style="text-align:center; font-size:1.1rem; color:#e53935;">$<?= number_format($cotizacion['total'], 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <?php if ($cotizacion['condiciones_pago'] || $cotizacion['observaciones']): ?>
            <div class="condiciones">
                <?php if ($cotizacion['condiciones_pago']): ?>
                    <strong>CONDICIONES DE PAGO:</strong> <?= htmlspecialchars($cotizacion['condiciones_pago']) ?><br>
                <?php endif; ?>
                <?php if ($cotizacion['observaciones']): ?>
                    <strong>OBSERVACIONES:</strong> <?= htmlspecialchars($cotizacion['observaciones']) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
let estadoParams = {};
function cambiarEstado(cotizacionId, nuevoEstadoId, estadoAnteriorId) {
    // Guarda los parámetros para usarlos después
    estadoParams = { cotizacionId, nuevoEstadoId, estadoAnteriorId };
    // Muestra el modal
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarEstado'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnConfirmarCambioEstado').addEventListener('click', function() {
        // Crea y envía el formulario como antes
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'cambiar_estado.php';

        const cotizacionIdInput = document.createElement('input');
        cotizacionIdInput.type = 'hidden';
        cotizacionIdInput.name = 'cotizacion_id';
        cotizacionIdInput.value = estadoParams.cotizacionId;

        const nuevoEstadoInput = document.createElement('input');
        nuevoEstadoInput.type = 'hidden';
        nuevoEstadoInput.name = 'nuevo_estado_id';
        nuevoEstadoInput.value = estadoParams.nuevoEstadoId;

        const estadoAnteriorInput = document.createElement('input');
        estadoAnteriorInput.type = 'hidden';
        estadoAnteriorInput.name = 'estado_anterior_id';
        estadoAnteriorInput.value = estadoParams.estadoAnteriorId;

        const redirectInput = document.createElement('input');
        redirectInput.type = 'hidden';
        redirectInput.name = 'redirect_url';
        redirectInput.value = window.location.href;

        form.appendChild(cotizacionIdInput);
        form.appendChild(nuevoEstadoInput);
        form.appendChild(estadoAnteriorInput);
        form.appendChild(redirectInput);

        document.body.appendChild(form);
        form.submit();
    });
});
</script>
<!-- Modal de confirmación para cambio de estado -->
<div class="modal fade" id="modalConfirmarEstado" tabindex="-1" aria-labelledby="modalConfirmarEstadoLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header" style="background: #232a7c; color: #fff; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
        <h5 class="modal-title d-flex align-items-center gap-2" id="modalConfirmarEstadoLabel">
          <i class="bi bi-question-circle-fill" style="font-size:2rem;color:#ffc107;"></i>
          Confirmar cambio de estado
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" style="font-size:1.15rem; color:#232a7c; padding: 2rem 1.5rem;">
        ¿Estás seguro de que quieres cambiar el estado de esta cotización?
      </div>
      <div class="modal-footer" style="border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success" id="btnConfirmarCambioEstado">Aceptar</button>
      </div>
    </div>
  </div>
</div>
<script>
// Personalización de encabezado desde localStorage
(function() {
  const cfg = JSON.parse(localStorage.getItem('cotiz_config_encabezado') || '{}');
  // Teléfono y ubicación
  if (cfg.telefono) {
    const tel = document.querySelector('.datos-empresa p:nth-child(2)');
    if (tel) tel.textContent = cfg.telefono;
  }
  if (cfg.ubicacion) {
    const ubi = document.querySelector('.datos-empresa p:nth-child(3)');
    if (ubi) ubi.textContent = cfg.ubicacion;
  }
  // Tamaño del logo de la empresa
  if (cfg.logoSize) {
    const logo = document.querySelector('.logo-empresa');
    if (logo) logo.style.height = cfg.logoSize + 'px';
  }
  // Logos dinámicos
  if (cfg.logos && Array.isArray(cfg.logos)) {
    const logosContainer = document.querySelector('.cotizacion-info .logos-dinamicos');
    if (logosContainer) logosContainer.innerHTML = '';
    cfg.logos.forEach(function(file) {
      const img = document.createElement('img');
      img.src = '../assets/img/' + file;
      img.alt = file.replace(/\.[a-zA-Z0-9]+$/, '');
      img.style.height = '28px';
      img.style.marginLeft = '8px';
      img.style.verticalAlign = 'middle';
      if (logosContainer) logosContainer.appendChild(img);
    });
  }
})();
// Lanzar impresión automática si la URL tiene ?imprimir=1
if (window.location.search.includes('imprimir=1')) {
  window.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() { window.print(); }, 300);
  });
}
</script>
<script>
(function() {
  const cfg = JSON.parse(localStorage.getItem('cotiz_config_encabezado') || '{}');
  if (cfg.mostrarSinStock !== false) {
    // Mostrar badge solo si corresponde
    document.querySelectorAll('.descripcion').forEach(function(td) {
      const badge = td.querySelector('.badge-sin-stock');
      if (!badge) return;
      // Lógica PHP embebida para saber si es sin stock
      const sinStock = td.innerHTML.includes('data-sin-stock="1"');
      if (sinStock) {
        badge.textContent = 'Sin stock';
        badge.className = 'badge bg-danger ms-2 badge-sin-stock';
        badge.style.display = '';
      }
    });
  }
})();
</script>
<script>
  // Cierra las alertas automáticamente después de 4 segundos
  setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
      if (alert.classList.contains('show')) {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
      }
    });
  }, 4000);
</script>
</body>
</html>