<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once '../includes/bobina_helpers.php';

// Obtener productos y tipos para JS
$products = $mysqli->query("SELECT product_id, product_name, tipo_gestion, barcode FROM products ORDER BY product_name");
$productos_array = [];
while ($row = $products->fetch_assoc()) {
    $productos_array[] = $row;
}
$movement_types = $mysqli->query("SELECT movement_type_id, name FROM movement_types ORDER BY name");
$tipos_array = [];
while ($row = $movement_types->fetch_assoc()) {
    $tipos_array[] = $row;
}
// Obtener técnicos para el select
$tecnicos = $mysqli->query("SELECT tecnico_id, nombre FROM tecnicos ORDER BY nombre");
$tecnicos_array = [];
while ($row = $tecnicos->fetch_assoc()) {
    $tecnicos_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar movimientos | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .main-content { max-width: 900px; margin: 40px auto 0 auto; }
        .main-content h2 { margin-bottom: 22px; }
        .table-movs th, .table-movs td { vertical-align: middle; }
        .barcode-btn { border-left: none; }
        .barcode-btn:hover { background-color: #6c757d; color: white; }
        .toast-flotante { position: fixed; top: 30px; right: 30px; z-index: 9999; min-width: 260px; display: none; }
        @media (max-width: 900px) { .main-content { max-width: 98vw; padding: 0 2vw; } }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h2>Registrar movimientos de inventario</h2>
    <div class="mb-3">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a movimientos
        </a>
    </div>
    <!-- Select opcional de técnico -->
    <form id="movimientosForm" autocomplete="off">
        <div class="mb-3">
            <label for="tecnico_id" class="form-label">Técnico (opcional)</label>
            <select class="form-select" name="tecnico_id" id="tecnico_id">
                <option value="">-- Sin técnico --</option>
                <?php foreach ($tecnicos_array as $t): ?>
                    <option value="<?= $t['tecnico_id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <table class="table table-bordered table-movs" id="tablaMovimientos">
            <thead class="table-light">
                <tr>
                    <th>Producto</th>
                    <th>Tipo de movimiento</th>
                    <th>Cantidad/Metros</th>
                    <th>Bobina (si aplica)</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <!-- Filas dinámicas -->
            </tbody>
        </table>
        <div class="mb-3">
            <button type="button" class="btn btn-secondary" id="btnAgregarFila">
                <i class="bi bi-plus-circle"></i> Agregar otro producto
            </button>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
                <i class="bi bi-check-circle"></i> Registrar todos los movimientos
            </button>
            <a href="index.php" class="btn btn-outline-secondary flex-fill">Cancelar</a>
        </div>
    </form>
</main>
<div id="toastFlotante" class="toast-flotante"></div>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Datos PHP a JS ---
const productos = <?php echo json_encode($productos_array); ?>;
const tipos = <?php echo json_encode($tipos_array); ?>;

// --- Función para crear una fila de movimiento con autocompletado ---
function crearFilaMovimiento() {
    const tr = document.createElement('tr');
    // Producto (solo select clásico y escáner)
    const tdProd = document.createElement('td');
    tdProd.style.position = 'relative';
    // Select visible
    const selectProd = document.createElement('select');
    selectProd.className = 'form-select prod-select';
    selectProd.name = 'producto[]';
    selectProd.required = true;
    selectProd.style.width = '100%';
    selectProd.innerHTML = '<option value="">Selecciona producto</option>' +
        productos.map(p => `<option value="${p.product_id}" data-tipo="${p.tipo_gestion}" data-barcode="${p.barcode || ''}" data-sku="${p.sku || ''}">${p.product_name}${p.sku ? ' | SKU: ' + p.sku : ''}${p.barcode ? ' | CB: ' + p.barcode : ''}${p.tipo_gestion === 'bobina' ? ' (Bobina)' : ''}</option>`).join('');
    tdProd.appendChild(selectProd);
    // Botón escáner
    const btnBarcode = document.createElement('button');
    btnBarcode.type = 'button';
    btnBarcode.className = 'btn btn-outline-secondary barcode-btn ms-1';
    btnBarcode.title = 'Buscar por código de barras';
    btnBarcode.innerHTML = '<i class="bi bi-upc-scan"></i>';
    btnBarcode.onclick = function() {
        mostrarModalBarcode(selectProd);
    };
    tdProd.appendChild(btnBarcode);
    tr.appendChild(tdProd);
    // Sincronización: select → input
    selectProd.addEventListener('change', function() {
        const selected = productos.find(p => p.product_id == selectProd.value);
        if (selected) {
            // No hay input de búsqueda manual, por lo que no se actualiza el inputBuscador
        }
    });
    // Sincronización: input → select (filtra y selecciona)
    // No hay input de búsqueda manual, por lo que no se añade esta lógica
    // Tipo de movimiento
    const tdTipo = document.createElement('td');
    const selectTipo = document.createElement('select');
    selectTipo.className = 'form-select';
    selectTipo.name = 'tipo[]';
    selectTipo.required = true;
    selectTipo.innerHTML = '<option value="">Tipo</option>' +
        tipos.map(t => `<option value="${t.movement_type_id}">${t.name}</option>`).join('');
    // Si no existe la opción Devolución, la agrego
    if (!tipos.some(t => t.name && t.name.toLowerCase() === 'devolución')) {
        const optDevolucion = document.createElement('option');
        optDevolucion.value = 'devolucion';
        optDevolucion.textContent = 'Devolución';
        selectTipo.appendChild(optDevolucion);
    }
    // Si no existe la opción Ajuste, la agrego
    if (!tipos.some(t => t.name && t.name.toLowerCase() === 'ajuste')) {
        const optAjuste = document.createElement('option');
        optAjuste.value = 'ajuste';
        optAjuste.textContent = 'Ajuste';
        selectTipo.appendChild(optAjuste);
    }
    tdTipo.appendChild(selectTipo);

    // Selector de dirección para ajuste
    const ajusteDirDiv = document.createElement('div');
    ajusteDirDiv.style.display = 'none';
    ajusteDirDiv.innerHTML = `
        <select class="form-select mt-2 ajuste-direccion-select" name="ajuste_direccion[]">
            <option value="sumar">Sumar stock</option>
            <option value="restar">Restar stock</option>
        </select>
    `;
    tdTipo.appendChild(ajusteDirDiv);
    tr.appendChild(tdTipo);

    selectTipo.addEventListener('change', function() {
        if (selectTipo.options[selectTipo.selectedIndex].text.toLowerCase().includes('ajuste')) {
            ajusteDirDiv.style.display = 'block';
        } else {
            ajusteDirDiv.style.display = 'none';
        }
        actualizarOpcionesBobina();
    });
    // Cantidad
    const tdCant = document.createElement('td');
    const inputCant = document.createElement('input');
    inputCant.type = 'number';
    inputCant.className = 'form-control';
    inputCant.name = 'cantidad[]';
    inputCant.min = '0.01';
    inputCant.step = 'any';
    inputCant.required = true;
    tdCant.appendChild(inputCant);
    tr.appendChild(tdCant);
    // Bobina
    const tdBobina = document.createElement('td');
    const selectBobina = document.createElement('select');
    selectBobina.className = 'form-select';
    selectBobina.name = 'bobina[]';
    selectBobina.innerHTML = '<option value="">-</option>';
    tdBobina.appendChild(selectBobina);
    tr.appendChild(tdBobina);
    // Eliminar
    const tdDel = document.createElement('td');
    const btnDel = document.createElement('button');
    btnDel.type = 'button';
    btnDel.className = 'btn btn-danger btn-sm';
    btnDel.innerHTML = '<i class="bi bi-trash"></i>';
    btnDel.onclick = function() {
        tr.remove();
    };
    tdDel.appendChild(btnDel);
    tr.appendChild(tdDel);
    // Lógica para mostrar bobinas si aplica
    selectProd.addEventListener('change', function() {
        actualizarOpcionesBobina();
    });
    selectTipo.addEventListener('change', function() {
        actualizarOpcionesBobina();
    });
    function actualizarOpcionesBobina() {
        const tipo = selectProd.options[selectProd.selectedIndex].dataset.tipo;
        const prodId = selectProd.value;
        const tipoMovId = selectTipo.value;
        if (tipo === 'bobina' && prodId) {
            // Buscar si el tipo de movimiento es entrada, ajuste o devolución
            let isEntrada = false;
            if (tipoMovId) {
                const tipoMov = tipos.find(t => t.movement_type_id == tipoMovId);
                if (tipoMov && tipoMov.name) {
                    const nombre = tipoMov.name.toLowerCase();
                    if (
                        nombre.includes('entrada') ||
                        nombre.includes('ajuste') ||
                        nombre.includes('devolucion') || // sin tilde
                        nombre.includes('devolución')    // con tilde
                    ) {
                        isEntrada = true;
                    }
                } else if (tipoMov && tipoMov.is_entry == 1) {
                    isEntrada = true;
                }
            }
            // Fetch bobinas (todas o solo con metros > 0)
            let url = `../bobinas/bobinas_por_producto.php?product_id=${prodId}`;
            if (!isEntrada) {
                url += '&solo_disponibles=1';
            }
            fetch(url)
                .then(r => r.json())
                .then(bobinas => {
                    selectBobina.innerHTML = '<option value="">-- Selecciona una bobina --</option>';
                    bobinas.forEach(bobina => {
                        const opt = document.createElement('option');
                        opt.value = bobina.bobina_id;
                        opt.textContent = `${bobina.identificador || 'Bobina #' + bobina.bobina_id} - ${bobina.metros_actuales}m disponibles`;
                        if (bobina.metros_actuales == 0) {
                            opt.textContent += ' (agotada)';
                        }
                        selectBobina.appendChild(opt);
                    });
                    // Agregar opción para nueva bobina
                    const optNueva = document.createElement('option');
                    optNueva.value = 'nueva';
                    optNueva.textContent = '[Nueva bobina]';
                    selectBobina.appendChild(optNueva);
                    selectBobina.disabled = false;
                });
            selectBobina.disabled = false;
        } else {
            selectBobina.innerHTML = '<option value="">-</option>';
            selectBobina.disabled = true;
        }
    }
    // Inicialmente deshabilitar bobina
    selectBobina.disabled = true;
    return tr;
}

// --- Modal para escanear código de barras ---
let modalBarcode = null;
function mostrarModalBarcode(selectProd) {
    if (!modalBarcode) {
        modalBarcode = document.createElement('div');
        modalBarcode.className = 'modal fade';
        modalBarcode.id = 'modalBuscarBarcode';
        modalBarcode.tabIndex = -1;
        modalBarcode.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-upc-scan"></i> Buscar por código de barras</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" class="form-control" id="barcodeInputModal" placeholder="Escanea o ingresa el código de barras">
                    <div id="barcodeResultModal" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSeleccionarProductoModal" style="display:none;">Seleccionar producto</button>
                </div>
            </div>
        </div>`;
        document.body.appendChild(modalBarcode);
        // Eventos
        modalBarcode.addEventListener('hidden.bs.modal', function() {
            document.getElementById('barcodeInputModal').value = '';
            document.getElementById('barcodeResultModal').innerHTML = '';
            document.getElementById('btnSeleccionarProductoModal').style.display = 'none';
        });
    }
    const modal = new bootstrap.Modal(modalBarcode);
    modal.show();
    setTimeout(() => {
        document.getElementById('barcodeInputModal').focus();
    }, 300);
    let productoEncontrado = null;
    document.getElementById('barcodeInputModal').oninput = function() {
        const barcode = this.value.trim().toUpperCase().replace(/\s+/g, '');
        const resultDiv = document.getElementById('barcodeResultModal');
        const btnSeleccionar = document.getElementById('btnSeleccionarProductoModal');
        let encontrado = false;
        for (let p of productos) {
            let optBarcode = (p.barcode || '').trim().toUpperCase().replace(/\s+/g, '');
            if (optBarcode && optBarcode === barcode) {
                productoEncontrado = p;
                encontrado = true;
                break;
            }
        }
        if (encontrado) {
            resultDiv.innerHTML = `<div class='alert alert-success'><i class='bi bi-check-circle'></i> <strong>Producto encontrado:</strong><br>${productoEncontrado.product_name}</div>`;
            btnSeleccionar.style.display = 'inline-block';
        } else {
            resultDiv.innerHTML = `<div class='alert alert-warning'><i class='bi bi-exclamation-triangle'></i> <strong>Producto no encontrado</strong><br>No se encontró ningún producto con el código de barras: <code>${barcode}</code></div>`;
            btnSeleccionar.style.display = 'none';
            productoEncontrado = null;
        }
    };
    document.getElementById('btnSeleccionarProductoModal').onclick = function() {
        if (productoEncontrado) {
            selectProd.value = productoEncontrado.product_id;
            selectProd.dispatchEvent(new Event('change'));
            modal.hide();
        }
    };
    document.getElementById('barcodeInputModal').onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            if (productoEncontrado) {
                document.getElementById('btnSeleccionarProductoModal').click();
            }
        }
    };
}

// --- Agregar fila inicial ---
function agregarFila() {
    const tbody = document.querySelector('#tablaMovimientos tbody');
    tbody.appendChild(crearFilaMovimiento());
}
document.getElementById('btnAgregarFila').onclick = agregarFila;
window.onload = agregarFila;

// --- Envío AJAX del formulario ---
document.getElementById('movimientosForm').onsubmit = function(e) {
    e.preventDefault();
    const form = this;
    const datos = [];
    const filas = form.querySelectorAll('tbody tr');
    let valido = true;
    filas.forEach(tr => {
        const producto = tr.querySelector('select[name="producto[]"]').value;
        const tipo = tr.querySelector('select[name="tipo[]"]').value;
        const cantidad = tr.querySelector('input[name="cantidad[]"]').value;
        const bobina = tr.querySelector('select[name="bobina[]"]').value;
        let ajuste_direccion = null;
        const ajusteDirSelect = tr.querySelector('.ajuste-direccion-select');
        if (ajusteDirSelect && ajusteDirSelect.parentElement.style.display !== 'none') {
            ajuste_direccion = ajusteDirSelect.value;
        }
        if (!producto || !tipo || !cantidad || (tr.querySelector('select[name="bobina[]"]').disabled === false && !bobina && tr.querySelector('select[name="bobina[]"]').options.length > 1)) {
            valido = false;
        }
        datos.push({ producto, tipo, cantidad, bobina, ajuste_direccion });
    });
    if (!valido) {
        showToast('Completa todos los campos de cada fila.', 'danger');
        return;
    }
    // Obtener el técnico seleccionado
    const tecnico_id = document.getElementById('tecnico_id').value;
    fetch('add_multiple.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ movimientos: datos, tecnico_id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showToast(res.success, 'success');
            // Limpiar tabla y dejar una fila
            const tbody = document.querySelector('#tablaMovimientos tbody');
            tbody.innerHTML = '';
            agregarFila();
        } else if (res.error) {
            showToast(res.error, 'danger');
            if (res.detalles) {
                showToast(res.detalles.join('<br>'), 'danger');
            }
        }
    })
    .catch(() => {
        showToast('Error de red o del servidor.', 'danger');
    });
};

// --- Toast flotante ---
function showToast(message, type = 'success') {
    const toast = document.getElementById('toastFlotante');
    toast.innerHTML = `<div class="toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0 show" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
    toast.style.display = 'block';
    setTimeout(() => { toast.style.display = 'none'; }, 3500);
    toast.querySelector('.btn-close').onclick = function() { toast.style.display = 'none'; };
}
</script>
</body>
</html>
