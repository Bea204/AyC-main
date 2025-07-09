<?php
// Maquetado interactivo de cotización (no guarda, solo visual)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización Interactiva</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6fb; color: #232a7c; margin: 0; padding: 0; }
        .cotizacion-container { background: #fff; max-width: 980px; margin: 30px auto; box-shadow: 0 4px 24px rgba(18,24,102,0.10); border-radius: 14px; padding: 32px 36px 24px 36px; }
        .cotizacion-header { display: flex; align-items: flex-start; justify-content: space-between; border-bottom: 2px solid #232a7c; padding-bottom: 18px; margin-bottom: 18px; }
        .logo-empresa { height: 80px; margin-right: 18px; }
        .datos-empresa { flex: 1; }
        .datos-empresa h2 { font-size: 1.5rem; margin: 0 0 4px 0; color: #121866; }
        .datos-empresa p { margin: 0; font-size: 1rem; color: #232a7c; }
        .cotizacion-info { text-align: right; }
        .cotizacion-info .titulo-cot { background: #ff9800; color: #fff; font-weight: 700; padding: 6px 18px; border-radius: 8px; font-size: 1.1rem; margin-bottom: 8px; display: inline-block; }
        .cotizacion-info img { height: 28px; margin-left: 8px; vertical-align: middle; }
        .datos-cliente { margin: 18px 0 10px 0; display: flex; flex-wrap: wrap; gap: 32px; }
        .datos-cliente .campo { font-size: 1rem; margin-bottom: 2px; }
        .datos-cliente .campo strong { color: #121866; min-width: 90px; display: inline-block; }
        .tabla-cotizacion { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.98rem; }
        .tabla-cotizacion th, .tabla-cotizacion td { border: 1px solid #b0b6d0; padding: 8px 6px; text-align: center; vertical-align: middle; }
        .tabla-cotizacion th { background: #232a7c; color: #fff; font-size: 1rem; }
        .tabla-cotizacion td.descripcion { text-align: left; font-size: 0.97rem; }
        .tabla-cotizacion td.imagen { background: #f7f9fc; }
        .tabla-cotizacion td.precio-total { font-weight: 700; color: #232a7c; }
        .tabla-cotizacion td.costo-total { background: #e0e0e0; color: #232a7c; font-weight: 500; }
        .tabla-cotizacion tfoot td { font-weight: 700; background: #f4f6fb; color: #121866; }
        .condiciones { margin-top: 18px; font-size: 0.98rem; }
        .condiciones strong { color: #121866; }
        .btn { background: #232a7c; color: #fff; border: none; border-radius: 6px; padding: 7px 16px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 4px 0; transition: background 0.15s; }
        .btn:hover { background: #121866; }
        .btn-danger { background: #e53935; }
        .btn-danger:hover { background: #b71c1c; }
        input, textarea { font-family: inherit; font-size: 1rem; border: 1px solid #b0b6d0; border-radius: 5px; padding: 4px 6px; background: #f7f9fc; color: #232a7c; }
        input[type="number"] { width: 70px; }
        input[type="text"] { width: 100%; }
        .tabla-cotizacion input, .tabla-cotizacion textarea { width: 100%; box-sizing: border-box; }
        .tabla-cotizacion input[type="number"] { width: 70px; }
        .tabla-cotizacion input[type="text"] { width: 100%; }
        .tabla-cotizacion input[type="url"] { width: 120px; }
        .tabla-cotizacion .btn { padding: 4px 10px; font-size: 0.95rem; }
        .tabla-cotizacion .btn-danger { padding: 4px 10px; font-size: 0.95rem; }
        @media print { body { background: #fff; } .cotizacion-container { box-shadow: none; border-radius: 0; margin: 0; padding: 0; } .btn, .btn-danger, .tabla-cotizacion .btn { display: none !important; } input, textarea { border: none !important; background: none !important; color: #232a7c !important; } }
    </style>
</head>
<body>
<div class="cotizacion-container">
    <a href="../dashboard/index.php" class="btn" style="background:#757575; color:#fff; margin-bottom:18px; display:inline-block;">← Volver al dashboard</a>
    <form id="form-cotizacion" autocomplete="off" onsubmit="return false;">
    <div class="cotizacion-header">
        <img src="../assets/img/LogoWeb.png" alt="Logo empresa" class="logo-empresa">
        <div class="datos-empresa">
            <input type="text" name="empresa_nombre" value="ALARMAS & CAMARAS DEL SURESTE" style="font-size:1.5rem; font-weight:700; color:#121866; border:none; background:transparent; width:100%;">
            <input type="text" name="empresa_telefono" value="999 134 3979" style="font-size:1rem; color:#232a7c; border:none; background:transparent; width:100%;">
            <input type="text" name="empresa_ubicacion" value="Mérida, Yucatán" style="font-size:1rem; color:#232a7c; border:none; background:transparent; width:100%;">
        </div>
        <div class="cotizacion-info">
            <div class="titulo-cot">Cotización - Cámaras</div><br>
            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Dahua_Technology_logo.svg" alt="Dahua">
            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2b/TP-Link_Logo_2016.svg" alt="TP-Link">
            <img src="https://upload.wikimedia.org/wikipedia/commons/7/7e/LinkedPro_logo.png" alt="LinkedPro" style="background:#fff;">
        </div>
    </div>
    <div class="datos-cliente">
        <div class="campo"><strong>Cliente:</strong> <input type="text" name="cliente_nombre" value="Black Diamond"></div>
        <div class="campo"><strong>Teléfono:</strong> <input type="text" name="cliente_telefono" value="999 134 3979"></div>
        <div class="campo"><strong>Ubicación:</strong> <input type="text" name="cliente_ubicacion" value="Mérida / Yucatán"></div>
        <div class="campo"><strong>Fecha:</strong> <input type="date" name="cliente_fecha" value="2025-07-04"></div>
    </div>
    <table class="tabla-cotizacion" id="tabla-cotizacion">
        <thead>
            <tr>
                <th>ITEM</th>
                <th>DESCRIPCIÓN DEL PRODUCTO / SERVICIO</th>
                <th>IMAGEN ILUSTRATIVA (URL)</th>
                <th>CANT</th>
                <th>PRECIO UNITARIO</th>
                <th>PRECIO TOTAL</th>
                <th>COSTO TOTAL</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="productos-body">
            <tr>
                <td class="item-num">1</td>
                <td class="descripcion"><textarea name="descripcion[]">DAHUA HAC-B1A51-U-28 - Cámara Bullet de 5MP, 2.8mm, 106° apertura, IR 30m, IP67, soporta CVI/CVBS/AHD/TVI</textarea></td>
                <td class="imagen"><input type="url" name="imagen[]" value="https://www.syscom.mx/imagenes_productos/DH-HAC-B1A51N-U-0280B.jpg"><br><img src="https://www.syscom.mx/imagenes_productos/DH-HAC-B1A51N-U-0280B.jpg" alt="Imagen" style="height:38px; margin-top:2px;"></td>
                <td><input type="number" name="cantidad[]" value="8" min="1" onchange="recalcular()"></td>
                <td><input type="number" name="precio_unitario[]" value="719.00" step="0.01" min="0" onchange="recalcular()"></td>
                <td class="precio-total">$5,752.00</td>
                <td><input type="number" name="costo_total[]" value="3096.00" step="0.01" min="0"></td>
                <td><button type="button" class="btn btn-danger" onclick="eliminarFila(this)">X</button></td>
            </tr>
            <tr>
                <td class="item-num">2</td>
                <td class="descripcion"><textarea name="descripcion[]">DAHUA XVR5108HS-4KL-I3 - DVR 4K WizSense de 8 canales + 8 IP, SMD Plus, protección perimetral, IA, H.265+, soporta CVI, AHD, TVI, CVBS</textarea></td>
                <td class="imagen"><input type="url" name="imagen[]" value="https://www.syscom.mx/imagenes_productos/XVR5108HS-4KL-I3.jpg"><br><img src="https://www.syscom.mx/imagenes_productos/XVR5108HS-4KL-I3.jpg" alt="Imagen" style="height:38px; margin-top:2px;"></td>
                <td><input type="number" name="cantidad[]" value="1" min="1" onchange="recalcular()"></td>
                <td><input type="number" name="precio_unitario[]" value="4850.00" step="0.01" min="0" onchange="recalcular()"></td>
                <td class="precio-total">$4,850.00</td>
                <td><input type="number" name="costo_total[]" value="2845.00" step="0.01" min="0"></td>
                <td><button type="button" class="btn btn-danger" onclick="eliminarFila(this)">X</button></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align:right;">SUBTOTAL</td>
                <td colspan="2" id="subtotal" style="text-align:center;">$0.00</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:right;">DESCUENTO (%)</td>
                <td colspan="2"><input type="number" id="descuento" value="15" min="0" max="100" style="width:60px;" onchange="recalcular()"> %</td>
                <td></td>
            </tr>
            <tr>
                <td colspan="5" style="text-align:right; font-size:1.1rem; color:#121866;">TOTAL</td>
                <td colspan="2" id="total" style="text-align:center; font-size:1.1rem; color:#e53935;">$0.00</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    <button type="button" class="btn" onclick="agregarFila()">Agregar producto</button>
    <div class="condiciones">
        <strong>CONDICIONES DE PAGO:</strong> <input type="text" name="condiciones" value="En caso de requerir factura se agrega IVA" style="width:60%;">
        <br>
        <strong>OBSERVACIONES:</strong> <input type="text" name="observaciones" value="1 año de garantía por defectos de fábrica" style="width:60%;">
    </div>
    </form>
</div>
<script>
function recalcular() {
    let subtotal = 0;
    const filas = document.querySelectorAll('#productos-body tr');
    filas.forEach((tr, idx) => {
        // Actualiza el número de ítem
        tr.querySelector('.item-num').textContent = idx + 1;
        // Obtiene cantidad y precio unitario
        const cantidad = parseFloat(tr.querySelector('input[name="cantidad[]"]').value) || 0;
        const precio = parseFloat(tr.querySelector('input[name="precio_unitario[]"]').value) || 0;
        const total = cantidad * precio;
        tr.querySelector('.precio-total').textContent = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2});
        subtotal += total;
        // Actualiza la imagen si cambia la URL
        const url = tr.querySelector('input[name="imagen[]"]').value;
        tr.querySelector('img').src = url;
    });
    document.getElementById('subtotal').textContent = '$' + subtotal.toLocaleString('es-MX', {minimumFractionDigits:2});
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    const total = subtotal - (subtotal * descuento / 100);
    document.getElementById('total').textContent = '$' + total.toLocaleString('es-MX', {minimumFractionDigits:2});
}
function eliminarFila(btn) {
    const tr = btn.closest('tr');
    tr.remove();
    recalcular();
}
function agregarFila() {
    const tbody = document.getElementById('productos-body');
    const idx = tbody.children.length + 1;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td class="item-num">${idx}</td>
        <td class="descripcion"><textarea name="descripcion[]"></textarea></td>
        <td class="imagen"><input type="url" name="imagen[]" value=""><br><img src="" alt="Imagen" style="height:38px; margin-top:2px;"></td>
        <td><input type="number" name="cantidad[]" value="1" min="1" onchange="recalcular()"></td>
        <td><input type="number" name="precio_unitario[]" value="0.00" step="0.01" min="0" onchange="recalcular()"></td>
        <td class="precio-total">$0.00</td>
        <td><input type="number" name="costo_total[]" value="0.00" step="0.01" min="0"></td>
        <td><button type="button" class="btn btn-danger" onclick="eliminarFila(this)">X</button></td>
    `;
    tbody.appendChild(tr);
    recalcular();
}
document.querySelectorAll('input, textarea').forEach(el => {
    el.addEventListener('input', recalcular);
});
window.onload = recalcular;
</script>
</body>
</html> 