document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', e => {
            const inputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;

            inputs.forEach(input => {
                // Ignorar inputs ocultos o deshabilitados
                if (input.offsetParent === null || input.disabled) return;
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault(); // Stop form submission
                // Mostrar alerta Bootstrap en lugar de alert nativo
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show';
                alertDiv.innerHTML = `
                    <i class="bi bi-exclamation-triangle"></i>
                    Por favor, completa todos los campos requeridos.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.insertBefore(alertDiv, form.firstChild);
            }
        });
    });

    // Confirmation before deleting items
   // const deleteButtons = document.querySelectorAll('.btn-delete');
   // deleteButtons.forEach(btn => {
   //     btn.addEventListener('click', (e) => {
   //         if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
   //             e.preventDefault();
   //         }
   //     });
   // });

    // Sidebar dinámico
    // const sidebar = document.getElementById('sidebar');
    // const toggleBtn = document.getElementById('toggleSidebar');
    // if (sidebar && toggleBtn) {
    //     toggleBtn.addEventListener('click', () => {
    //         sidebar.classList.toggle('collapsed');
    //     });
    // }
});

function editarInsumo(id) {
    // Obtener insumos desde PHP (debe estar disponible en el scope global)
    const insumos = window.insumosData || [];
    const insumo = insumos.find(i => i.id == id);
    if (!insumo) {
        alert('Insumo no encontrado');
        return;
    }
    // Modal de edición
    const modal = `
        <div class="modal fade" id="modalEditarInsumo" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Insumo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="editNombre" value="${insumo.nombre}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="editCantidad" value="${insumo.cantidad}" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stock mínimo</label>
                            <input type="number" class="form-control" id="editMinimo" value="${insumo.minimo}" min="0">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Proveedor</label>
                            <input type="text" class="form-control" id="editProveedor" value="${insumo.proveedor}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="editUbicacion" value="${insumo.ubicacion}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio unitario</label>
                            <input type="number" class="form-control" id="editPrecio" value="${insumo.precio_unitario}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="guardarEdicionInsumo(${id})">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modal);
    const modalElement = document.getElementById('modalEditarInsumo');
    const bootstrapModal = new bootstrap.Modal(modalElement);
    bootstrapModal.show();
    modalElement.addEventListener('hidden.bs.modal', function() {
        modalElement.remove();
    });
}

function guardarEdicionInsumo(id) {
    // Simulación de guardado (solo frontend)
    const insumos = window.insumosData || [];
    const insumo = insumos.find(i => i.id == id);
    if (!insumo) return;
    insumo.cantidad = parseInt(document.getElementById('editCantidad').value);
    insumo.minimo = parseInt(document.getElementById('editMinimo').value);
    insumo.proveedor = document.getElementById('editProveedor').value;
    insumo.ubicacion = document.getElementById('editUbicacion').value;
    insumo.precio_unitario = parseFloat(document.getElementById('editPrecio').value);
    Swal.fire({
        icon: 'success',
        title: '¡Insumo editado!',
        text: 'Los cambios se han guardado (simulado, solo frontend).',
        confirmButtonColor: '#232a7c',
        timer: 1500,
        showConfirmButton: false
    });
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarInsumo'));
    modal.hide();
    setTimeout(() => { window.location.reload(); }, 1200);
}

// Sidebar: resalta la sección activa según la URL actual
function resaltarSidebarActivo() {
    const path = window.location.pathname;
    if (path.includes('/products/')) {
        document.querySelector('.sidebar-productos')?.classList.add('active');
    } else if (path.includes('/inventory/')) {
        document.querySelector('.sidebar-inventario')?.classList.add('active');
    } else if (path.includes('/movements/')) {
        document.querySelector('.sidebar-movimientos')?.classList.add('active');
    } else if (path.includes('/cotizaciones/')) {
        document.querySelector('.sidebar-cotizaciones')?.classList.add('active');
    } else if (path.includes('/proveedores/')) {
        document.querySelector('.sidebar-proveedores')?.classList.add('active');
    } else if (path.includes('/clients/')) {
        document.querySelector('.sidebar-clientes')?.classList.add('active');
    } else if (path.includes('/insumos/')) {
        document.querySelector('.sidebar-insumos')?.classList.add('active');
    } else if (path.includes('/categories/')) {
        document.querySelector('.sidebar-categorias')?.classList.add('active');
    } else if (path.includes('/reports/')) {
        document.querySelector('.sidebar-reportes')?.classList.add('active');
    } else if (path.includes('/configuracion/')) {
        document.querySelector('.sidebar-configuracion')?.classList.add('active');
    } else if (path.includes('/system_config/')) {
        document.querySelector('.sidebar-sistema')?.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', resaltarSidebarActivo);

// --- Búsqueda y filtros en tiempo real para insumos ---
if (window.location.pathname.includes('insumos.php')) {
    document.addEventListener('DOMContentLoaded', function() {
        const busquedaInput = document.getElementById('busqueda');
        const categoriaSelect = document.getElementById('categoria');
        const proveedorSelect = document.getElementById('proveedor');
        const estadoSelect = document.getElementById('estado');
        const grid = document.querySelector('.insumos-grid');
        const emptyState = document.querySelector('.empty-state');
        let timeout = null;

        function renderInsumos(insumos) {
            if (!grid) return;
            grid.innerHTML = '';
            if (insumos.length === 0) {
                if (emptyState) emptyState.style.display = 'block';
                grid.style.display = 'none';
                return;
            }
            if (emptyState) emptyState.style.display = 'none';
            grid.style.display = 'grid';
            insumos.forEach(insumo => {
                const card = document.createElement('div');
                card.className = 'insumo-card';
                card.innerHTML = `
                    <div class="insumo-header">
                        <div>
                            <h5 class="insumo-title">${insumo.nombre}</h5>
                            <div class="insumo-category">${insumo.categoria || ''}</div>
                            <div class="producto-origen">
                                <i class="bi bi-arrow-right"></i> Derivado de: ${insumo.producto_origen || 'Producto no encontrado'}
                            </div>
                        </div>
                    </div>
                    <div class="stock-status">
                        <span class="status-badge status-${insumo.estado}">
                            ${insumo.estado === 'disponible' ? '<i class="bi bi-check-circle"></i> Disponible' : insumo.estado === 'bajo_stock' ? '<i class="bi bi-exclamation-triangle"></i> Bajo Stock' : '<i class="bi bi-x-circle"></i> Agotado'}
                        </span>
                        <span class="consumo-semanal">
                            <i class="bi bi-calendar-week"></i> ${insumo.consumo_semanal || 0} ${insumo.unidad}/sem
                        </span>
                    </div>
                    <div class="insumo-details">
                        <div class="detail-item">
                            <span class="detail-number">${insumo.cantidad}</span>
                            <span class="detail-label">Stock (${insumo.unidad})</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-number">${insumo.minimo}</span>
                            <span class="detail-label">Mínimo</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-number">$${parseFloat(insumo.precio_unitario).toFixed(2)}</span>
                            <span class="detail-label">Precio Unit.</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-number">$${(parseFloat(insumo.cantidad) * parseFloat(insumo.precio_unitario)).toFixed(2)}</span>
                            <span class="detail-label">Valor Total</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">
                            <strong>Proveedor:</strong> ${insumo.proveedor || ''} | 
                            <strong>Ubicación:</strong> ${insumo.ubicacion || ''} | 
                            <strong>Última actualización:</strong> ${insumo.ultima_actualizacion ? new Date(insumo.ultima_actualizacion).toLocaleDateString('es-ES') : ''}
                        </small>
                    </div>
                    <div class="insumo-actions">
                        <button class="btn-action btn-edit" onclick="editarInsumo(${insumo.insumo_id})">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        <button class="btn-action btn-movement" onclick="registrarMovimiento(${insumo.insumo_id})">
                            <i class="bi bi-arrow-left-right"></i> Movimiento
                        </button>
                        <button class="btn-action btn-report" onclick="verReporteSemanal(${insumo.insumo_id})">
                            <i class="bi bi-graph-up"></i> Reporte Sem.
                        </button>
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function fetchInsumos() {
            const params = new URLSearchParams({
                busqueda: busquedaInput ? busquedaInput.value : '',
                categoria: categoriaSelect ? categoriaSelect.value : '',
                proveedor: proveedorSelect ? proveedorSelect.value : '',
                estado: estadoSelect ? estadoSelect.value : ''
            });
            fetch('ajax_list.php?' + params.toString())
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        renderInsumos(data.data);
                        window.insumosData = data.data; // Actualizar lista global para acciones
                    }
                });
        }

        if (busquedaInput) {
            busquedaInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchInsumos, 300);
            });
        }
        [categoriaSelect, proveedorSelect, estadoSelect].forEach(sel => {
            if (sel) sel.addEventListener('change', fetchInsumos);
        });
    });
}
// --- Fin búsqueda/filtros AJAX insumos ---