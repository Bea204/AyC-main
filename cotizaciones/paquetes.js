// Paquetes inteligentes para cotizaciones
// Guardado en localStorage

const PAQUETES_KEY = 'cotiz_paquetes';

function getPaquetes() {
    return JSON.parse(localStorage.getItem(PAQUETES_KEY) || '[]');
}

function savePaquetes(paquetes) {
    localStorage.setItem(PAQUETES_KEY, JSON.stringify(paquetes));
}

function addPaquete(paquete) {
    const paquetes = getPaquetes();
    paquetes.push(paquete);
    savePaquetes(paquetes);
}

function updatePaquete(index, paquete) {
    const paquetes = getPaquetes();
    paquetes[index] = paquete;
    savePaquetes(paquetes);
}

function deletePaquete(index) {
    const paquetes = getPaquetes();
    paquetes.splice(index, 1);
    savePaquetes(paquetes);
}

// Estructura de un paquete:
// {
//   nombre: 'Kit Cámaras',
//   items: [
//     { tipo_item: 'producto', product_id: 1, nombre: 'Cámara', tipo: 'principal', factor: 1 },
//     { tipo_item: 'servicio', servicio_id: 2, nombre: 'Instalación', tipo: 'relacionado', factor: 1 },
//     ...
//   ]
// }

// Aplica la lógica de sincronización de cantidades
function sincronizarPaquete(paquete, cantidades) {
    // cantidades: { product_id: cantidad }
    const principal = paquete.items.find(i => i.tipo === 'principal');
    if (!principal) return cantidades;
    const cantidadPrincipal = cantidades[principal.product_id] || 1;
    const nuevasCantidades = { ...cantidades };
    paquete.items.forEach(item => {
        if (item.tipo === 'relacionado') {
            nuevasCantidades[item.product_id] = cantidadPrincipal * item.factor;
        }
    });
    return nuevasCantidades;
}

// Exporta funciones para usar en el frontend
window.PaquetesCotizacion = {
    getPaquetes,
    savePaquetes,
    addPaquete,
    updatePaquete,
    deletePaquete,
    sincronizarPaquete
};

// Renderiza el formulario de creación/edición de paquete
window.renderPaqueteForm = function({productos, paquete, onSave, onCancel}) {
    // productos: [{product_id, product_name}]
    // serviciosArray: global
    // insumosArray: global
    let html = '';
    html += `<div class='mb-3'>
        <label class='form-label'>Nombre del paquete</label>
        <input type='text' class='form-control' id='paqNombre' value='${paquete?.nombre ? paquete.nombre.replace(/'/g, "&#39;") : ''}' placeholder='Ej: Kit cámaras 8ch'>
    </div>`;
    html += `<div class='mb-2'><b>Ítems del paquete</b></div>`;
    html += `<table class='table table-bordered'><thead><tr><th>Tipo</th><th>Nombre</th><th>Tipo relación</th><th>Factor</th><th></th></tr></thead><tbody id='paqItemsTbody'>`;
    (paquete?.items || []).forEach((item, idx) => {
        html += `<tr>
            <td>${item.tipo_item === 'servicio' ? 'Servicio' : (item.tipo_item === 'insumo' ? 'Insumo' : 'Producto')}</td>
            <td>${item.nombre}</td>
            <td>
                <select class='form-select paq-tipo' data-idx='${idx}'>
                    <option value='principal' ${item.tipo==='principal'?'selected':''}>Principal</option>
                    <option value='relacionado' ${item.tipo==='relacionado'?'selected':''}>Relacionado</option>
                </select>
            </td>
            <td><input type='number' class='form-control paq-factor' data-idx='${idx}' value='${item.factor}' min='0.01' step='0.01' style='width:80px;'></td>
            <td><button class='btn btn-sm btn-danger' onclick='window.removePaqueteItem(${idx})'><i class='bi bi-trash'></i></button></td>
        </tr>`;
    });
    html += `</tbody></table>`;
    // Select para productos
    html += `<div class='mb-2'>
        <select class='form-select' id='paqProductoSelect'>
            <option value=''>Agregar producto...</option>`;
    productos.forEach(p => {
        if (!(paquete.items || []).some(i => i.tipo_item === 'producto' && i.product_id == p.product_id)) {
            html += `<option value='${p.product_id}'>${p.product_name}</option>`;
        }
    });
    html += `</select>
    </div>`;
    // Select para servicios
    html += `<div class='mb-2'>
        <select class='form-select' id='paqServicioSelect'>
            <option value=''>Agregar servicio...</option>`;
    (window.serviciosArray || []).forEach(s => {
        if (!(paquete.items || []).some(i => i.tipo_item === 'servicio' && i.servicio_id == s.servicio_id)) {
            html += `<option value='${s.servicio_id}'>${s.nombre}</option>`;
        }
    });
    html += `</select>
    </div>`;
    // Select para insumos
    html += `<div class='mb-2'>
        <select class='form-select' id='paqInsumoSelect'>
            <option value=''>Agregar insumo...</option>`;
    (window.insumosArray || []).forEach(ins => {
        if (!(paquete.items || []).some(i => i.tipo_item === 'insumo' && i.insumo_id == ins.insumo_id)) {
            html += `<option value='${ins.insumo_id}'>${ins.nombre}</option>`;
        }
    });
    html += `</select>
    </div>`;
    html += `<div class='d-flex justify-content-end gap-2'>
        <button class='btn btn-success' id='btnGuardarPaquete'><i class='bi bi-check-circle'></i> Guardar</button>
        <button class='btn btn-secondary' id='btnCancelarPaquete'><i class='bi bi-x-circle'></i> Cancelar</button>
    </div>`;
    return html;
};

window.removePaqueteItem = function(idx) {
    if (window._paqEdit && window._paqEdit.items) {
        window._paqEdit.items.splice(idx, 1);
        window._paqRender();
    }
};
// --- Mantener el nombre al agregar producto ---
// Sobrescribe la función de nuevoPaquete en crear.php para guardar el nombre antes de renderizar
if (window._paqRender) {
    const oldRender = window._paqRender;
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        oldRender();
    };
} 

// Hacer funciones disponibles globalmente
window.editarPaquete = function(idx) {
    const paquetes = window.PaquetesCotizacion.getPaquetes();
    const paquete = paquetes[idx];
    if (!paquete) return;
    
    window._paqEdit = { 
        nombre: paquete.nombre, 
        items: [...paquete.items] 
    };
    window._paqEditIndex = idx;
    
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        const panel = document.getElementById('paquetesPanel');
        panel.innerHTML = window.renderPaqueteForm({
            productos: productosArray.map(p => ({ product_id: p.product_id, product_name: p.product_name })),
            paquete: window._paqEdit,
            onSave: window.guardarPaqueteEditado,
            onCancel: renderPaquetesPanel
        });
        // Agregar producto o servicio al paquete
        if (typeof window._paqRender === 'function') {
            const oldRender = window._paqRender;
            window._paqRender = function() {
                const nombreInput = document.getElementById('paqNombre');
                if (nombreInput && window._paqEdit) {
                    window._paqEdit.nombre = nombreInput.value;
                }
                oldRender();
                // Productos
                const prodSel = document.getElementById('paqProductoSelect');
                if (prodSel) {
                    prodSel.onchange = function() {
                        const pid = this.value;
                        if (!pid) return;
                        const prod = productosArray.find(p => p.product_id == pid);
                        if (!prod) return;
                        if (window._paqEdit.items.some(i => i.tipo_item === 'producto' && i.product_id == prod.product_id)) return;
                        window._paqEdit.items.push({ tipo_item: 'producto', product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
                        window._paqRender();
                    };
                }
                // Servicios
                const servSel = document.getElementById('paqServicioSelect');
                if (servSel) {
                    servSel.onchange = function() {
                        const sid = this.value;
                        if (!sid) return;
                        const serv = (window.serviciosArray || []).find(s => s.servicio_id == sid);
                        if (!serv) return;
                        if (window._paqEdit.items.some(i => i.tipo_item === 'servicio' && i.servicio_id == serv.servicio_id)) return;
                        window._paqEdit.items.push({ tipo_item: 'servicio', servicio_id: serv.servicio_id, nombre: serv.nombre, tipo: 'relacionado', factor: 1 });
                        window._paqRender();
                    };
                }
            };
        }
        document.getElementById('btnGuardarPaquete').onclick = window.guardarPaqueteEditado;
        document.getElementById('btnCancelarPaquete').onclick = renderPaquetesPanel;
        document.querySelectorAll('.paq-tipo').forEach(sel => {
            sel.onchange = function() {
                window._paqEdit.items[this.dataset.idx].tipo = this.value;
            };
        });
        document.querySelectorAll('.paq-factor').forEach(inp => {
            inp.oninput = function() {
                window._paqEdit.items[this.dataset.idx].factor = parseFloat(this.value) || 1;
            };
        });
    };
    window._paqRender();
};

window.guardarPaqueteEditado = function() {
    window._paqEdit.nombre = document.getElementById('paqNombre').value.trim();
    if (!window._paqEdit.nombre || window._paqEdit.items.length === 0) {
        mostrarNotificacion('Ponle nombre y al menos un producto al paquete.', 'warning');
        return;
    }
    window.PaquetesCotizacion.updatePaquete(window._paqEditIndex, window._paqEdit);
    renderPaquetesPanel();
    mostrarNotificacion('Paquete actualizado correctamente.', 'success');
};

window.eliminarPaquete = function(idx) {
    if (confirm('¿Eliminar este paquete?')) {
        window.PaquetesCotizacion.deletePaquete(idx);
        renderPaquetesPanel();
        mostrarNotificacion('Paquete eliminado correctamente.', 'info');
    }
}; 

// Función para crear nuevo paquete
function nuevoPaquete() {
    window._paqEdit = { nombre: '', items: [] };
    window._paqRender = function() {
        // Guardar el nombre antes de renderizar
        const nombreInput = document.getElementById('paqNombre');
        if (nombreInput && window._paqEdit) {
            window._paqEdit.nombre = nombreInput.value;
        }
        const panel = document.getElementById('paquetesPanel');
        panel.innerHTML = window.renderPaqueteForm({
            productos: productosArray.map(p => ({ product_id: p.product_id, product_name: p.product_name })),
            paquete: window._paqEdit,
            onSave: guardarPaquete,
            onCancel: renderPaquetesPanel
        });
        // --- AGREGAR PRODUCTO ---
        const prodSel = document.getElementById('paqProductoSelect');
        if (prodSel) {
            prodSel.onchange = function() {
                const pid = this.value;
                if (!pid) return;
                const prod = productosArray.find(p => p.product_id == pid);
                if (!prod) return;
                if (window._paqEdit.items.some(i => i.tipo_item === 'producto' && i.product_id == prod.product_id)) return;
                window._paqEdit.items.push({ tipo_item: 'producto', product_id: prod.product_id, nombre: prod.product_name, tipo: 'relacionado', factor: 1 });
                window._paqRender();
            };
        }
        // --- AGREGAR SERVICIO ---
        const servSel = document.getElementById('paqServicioSelect');
        if (servSel) {
            servSel.onchange = function() {
                const sid = this.value;
                if (!sid) return;
                const serv = (window.serviciosArray || []).find(s => s.servicio_id == sid);
                if (!serv) return;
                if (window._paqEdit.items.some(i => i.tipo_item === 'servicio' && i.servicio_id == serv.servicio_id)) return;
                window._paqEdit.items.push({ tipo_item: 'servicio', servicio_id: serv.servicio_id, nombre: serv.nombre, tipo: 'relacionado', factor: 1 });
                window._paqRender();
            };
        }
        document.getElementById('btnGuardarPaquete').onclick = guardarPaquete;
        document.getElementById('btnCancelarPaquete').onclick = renderPaquetesPanel;
        document.querySelectorAll('.paq-tipo').forEach(sel => {
            sel.onchange = function() {
                window._paqEdit.items[this.dataset.idx].tipo = this.value;
            };
        });
        document.querySelectorAll('.paq-factor').forEach(inp => {
            inp.oninput = function() {
                window._paqEdit.items[this.dataset.idx].factor = parseFloat(this.value) || 1;
            };
        });
    };
    window._paqRender();
} 

// --- Agregar insumo al paquete ---
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'paqInsumoSelect') {
        const iid = e.target.value;
        if (!iid) return;
        const ins = (window.insumosArray || []).find(i => i.insumo_id == iid);
        if (!ins) return;
        if (window._paqEdit.items.some(i => i.tipo_item === 'insumo' && i.insumo_id == ins.insumo_id)) return;
        window._paqEdit.items.push({ tipo_item: 'insumo', insumo_id: ins.insumo_id, nombre: ins.nombre, tipo: 'relacionado', factor: 1 });
        window._paqRender();
    }
});

// --- Al aplicar paquete, agregar insumos a insumosCotizacion ---
// Elimina cualquier bloque fuera de función que use 'paquete.items.forEach' y asegúrate de que solo se use dentro de aplicarPaqueteCotizacion. 