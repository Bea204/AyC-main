document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', e => {
            const inputs = form.querySelectorAll('input[required], select[required]');
            let valid = true;

            inputs.forEach(input => {
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
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            if (!confirm('¿Estás seguro de que quieres eliminar este producto?')) {
                e.preventDefault();
            }
        });
    });

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