<?php
require_once '../connection.php';
$insumo_id = isset($_GET['insumo_id']) ? intval($_GET['insumo_id']) : 0;
if (!$insumo_id) die('ID de insumo no válido.');
// Consulta datos del insumo
$stmt = $mysqli->prepare("SELECT i.*, c.name as categoria_nombre, s.name as proveedor_nombre FROM insumos i LEFT JOIN categories c ON i.category_id = c.category_id LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id WHERE i.insumo_id = ?");
$stmt->bind_param('i', $insumo_id);
$stmt->execute();
$insumo = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$insumo) die('Insumo no encontrado.');
// Consulta movimientos
$stmt = $mysqli->prepare("SELECT m.*, u.username as usuario FROM insumos_movements m LEFT JOIN users u ON m.user_id = u.user_id WHERE m.insumo_id = ? ORDER BY m.fecha_movimiento ASC");
$stmt->bind_param('i', $insumo_id);
$stmt->execute();
$movs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$logo = '../assets/img/LogoWeb.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Insumo - <?= htmlspecialchars($insumo['nombre']) ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; color: #232a7c; background: #f4f6fb; }
        .reporte-container { background: #fff; max-width: 900px; margin: 40px auto; border-radius: 16px; box-shadow: 0 4px 24px rgba(18,24,102,0.10); padding: 32px; }
        .header-row { display: flex; align-items: center; justify-content: space-between; background: #232a7c; border-radius: 8px; padding: 10px 24px 10px 24px; margin-bottom: 24px; }
        .titulo { color: #fff; font-size: 1.5em; font-weight: bold; text-align: left; margin: 0; }
        .logo { height: 48px; margin-left: 16px; }
        .tabla-datos { margin: 24px 0 32px 0; width: 100%; }
        .tabla-datos td { padding: 4px 8px; }
        .tabla-movs { border-collapse: collapse; width: 100%; font-size: 0.98em; }
        .tabla-movs th { background: #f4f6fb; color: #232a7c; font-weight: bold; border: 1px solid #ccc; padding: 6px; }
        .tabla-movs td { border: 1px solid #ccc; padding: 5px; }
        .btn-descargar { background: #232a7c; color: #fff; border: none; padding: 10px 24px; border-radius: 8px; font-size: 1em; cursor: pointer; margin-bottom: 24px; }
        .btn-descargar:hover { background: #121866; }
    </style>
</head>
<body>
    <div class="reporte-container" id="reportePDF">
        <div class="header-row">
            <div class="titulo">Reporte de Movimientos de Insumo</div>
            <img src="<?= $logo ?>" class="logo" alt="Logo">
        </div>
        <table class="tabla-datos">
            <tr><td><b>Nombre:</b></td><td><?= htmlspecialchars($insumo['nombre']) ?></td></tr>
            <tr><td><b>Categoría:</b></td><td><?= htmlspecialchars($insumo['categoria_nombre']) ?></td></tr>
            <tr><td><b>Proveedor:</b></td><td><?= htmlspecialchars($insumo['proveedor_nombre']) ?></td></tr>
            <tr><td><b>Stock actual:</b></td><td><?= htmlspecialchars($insumo['cantidad']) . ' ' . htmlspecialchars($insumo['unidad']) ?></td></tr>
            <tr><td><b>Stock mínimo:</b></td><td><?= htmlspecialchars($insumo['minimo']) . ' ' . htmlspecialchars($insumo['unidad']) ?></td></tr>
            <tr><td><b>Precio unitario:</b></td><td>$<?= htmlspecialchars($insumo['precio_unitario']) ?>/<?= htmlspecialchars($insumo['unidad']) ?></td></tr>
            <tr><td><b>Estado:</b></td><td><?= htmlspecialchars($insumo['estado']) ?></td></tr>
            <tr><td><b>Exportado:</b></td><td><?= isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Usuario' ?></td></tr>
            <tr><td><b>Fecha de exportación:</b></td><td><?= date('d/m/Y H:i:s') ?></td></tr>
        </table>
        <table class="tabla-movs">
            <tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr>
            <?php if (count($movs) > 0): foreach ($movs as $mov): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($mov['fecha_movimiento'])) ?></td>
                    <td><?= $mov['tipo_movimiento'] === 'entrada' ? 'Entrada' : 'Salida' ?></td>
                    <td>
                        <?php
                        if ($mov['piezas_movidas'] && floatval($mov['piezas_movidas']) > 0) {
                            echo intval($mov['piezas_movidas']) . ' piezas';
                        } else {
                            echo floatval($mov['cantidad']) . ' ' . htmlspecialchars($insumo['unidad']);
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($mov['motivo'] ?: 'Sin motivo') ?></td>
                    <td><?= htmlspecialchars($mov['usuario'] ?: 'Sistema') ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;">No hay movimientos registrados</td></tr>
            <?php endif; ?>
        </table>
        <div style="text-align:center; margin-top:32px;">
            <button class="btn-descargar" onclick="descargarPDF()"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</button>
        </div>
    </div>
    <script>
    function descargarPDF() {
        const element = document.getElementById('reportePDF');
        html2pdf().set({
            margin: 10,
            filename: 'reporte_insumo_<?= preg_replace('/\s+/', '_', $insumo['nombre']) ?>.pdf',
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        }).from(element).save();
    }
    </script>
</body>
</html> 