<?php
require_once '../auth/middleware.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Control de Equipos por Técnico</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
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
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(18,24,102,0.08); text-align: center; }
        .stat-card .number { font-size: 2rem; font-weight: 700; color: #121866; }
        .stat-card .label { color: #666; font-size: 0.9rem; margin-top: 4px; }
        .controls-bar { background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 2px 8px rgba(18,24,102,0.08); }
        .equipos-table { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(18,24,102,0.08); }
        .equipos-table th { background: #121866; color: #fff; padding: 12px; font-weight: 600; }
        .equipos-table td { padding: 12px; border-bottom: 1px solid #e3e6f0; }
        .equipos-table tr:hover { background: #f7f9fc; }
        .status-badge { padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; }
        .status-activo { background: #e8f5e8; color: #2e7d32; }
        .status-mantenimiento { background: #fff3e0; color: #f57c00; }
        .status-retirado { background: #ffebee; color: #c62828; }
        .equipo-item { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding: 8px; background: #f7f9fc; border-radius: 8px; }
        .equipo-item input { flex: 1; border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
        .equipo-item select { border: 1px solid #ddd; border-radius: 4px; padding: 4px 8px; }
        .btn-sm { padding: 4px 8px; font-size: 0.8rem; }
        .modal-content { border-radius: 12px; }
        .modal-header { background: #121866; color: #fff; border-radius: 12px 12px 0 0; }
        .report-section { background: #fff; padding: 20px; border-radius: 12px; margin-top: 24px; box-shadow: 0 2px 8px rgba(18,24,102,0.08); }
        @media (max-width: 900px) { 
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .stats-cards { grid-template-columns: 1fr; }
            .equipos-table { font-size: 0.9rem; }
            .controls-bar .row { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-tools"></i> Control de Equipos por Técnico</h2>
        <button class="btn btn-primary" onclick="generarReporte()">
            <i class="bi bi-file-earmark-pdf"></i> Generar Reporte
        </button>
    </div>

    <!-- Estadísticas -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="number" id="totalTecnicos">8</div>
            <div class="label">Técnicos Activos</div>
        </div>
        <div class="stat-card">
            <div class="number" id="totalEquipos">24</div>
            <div class="label">Equipos Asignados</div>
        </div>
        <div class="stat-card">
            <div class="number" id="equiposMantenimiento">3</div>
            <div class="label">En Mantenimiento</div>
        </div>
        <div class="stat-card">
            <div class="number" id="equiposRetirados">1</div>
            <div class="label">Retirados</div>
        </div>
    </div>

    <!-- Controles -->
    <div class="controls-bar">
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Buscar técnico:</label>
                <input type="text" class="form-control" id="buscarTecnico" placeholder="Nombre del técnico...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Filtrar por estado:</label>
                <select class="form-select" id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="mantenimiento">En mantenimiento</option>
                    <option value="retirado">Retirado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Ordenar por:</label>
                <select class="form-select" id="ordenarPor">
                    <option value="nombre">Nombre</option>
                    <option value="equipos">Cantidad de equipos</option>
                    <option value="fecha">Fecha de asignación</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-success w-100" onclick="agregarTecnico()">
                    <i class="bi bi-person-plus"></i> Agregar
                </button>
            </div>
        </div>
    </div>

    <!-- Tabla de Equipos -->
    <div class="equipos-table">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Técnico</th>
                    <th>Equipos Asignados</th>
                    <th>Estado</th>
                    <th>Fecha Asignación</th>
                    <th>Última Actualización</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="equipos-body">
                <tr>
                    <td>
                        <strong>Juan Pérez</strong><br>
                        <small class="text-muted">ID: TEC-001</small>
                    </td>
                    <td>
                        <div class="equipos-lista">
                            <div class="equipo-item">
                                <input type="text" value="Multímetro Fluke 117" readonly>
                                <select class="form-select form-select-sm">
                                    <option value="activo" selected>Activo</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                                <button class="btn btn-danger btn-sm" onclick="eliminarEquipo(this)">X</button>
                            </div>
                            <div class="equipo-item">
                                <input type="text" value="Laptop Dell Latitude" readonly>
                                <select class="form-select form-select-sm">
                                    <option value="activo" selected>Activo</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                                <button class="btn btn-danger btn-sm" onclick="eliminarEquipo(this)">X</button>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm mt-2" onclick="agregarEquipo(this)">
                            <i class="bi bi-plus"></i> Agregar Equipo
                        </button>
                    </td>
                    <td><span class="status-badge status-activo">Activo</span></td>
                    <td>15/01/2024</td>
                    <td>Hoy 14:30</td>
                    <td>
                        <button class="btn btn-outline-primary btn-sm" onclick="editarTecnico(this)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="verHistorial(this)">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="eliminarTecnico(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <strong>María López</strong><br>
                        <small class="text-muted">ID: TEC-002</small>
                    </td>
                    <td>
                        <div class="equipos-lista">
                            <div class="equipo-item">
                                <input type="text" value="Escalera de Aluminio" readonly>
                                <select class="form-select form-select-sm">
                                    <option value="mantenimiento" selected>Mantenimiento</option>
                                    <option value="activo">Activo</option>
                                    <option value="retirado">Retirado</option>
                                </select>
                                <button class="btn btn-danger btn-sm" onclick="eliminarEquipo(this)">X</button>
                            </div>
                        </div>
                        <button class="btn btn-primary btn-sm mt-2" onclick="agregarEquipo(this)">
                            <i class="bi bi-plus"></i> Agregar Equipo
                        </button>
                    </td>
                    <td><span class="status-badge status-mantenimiento">Mantenimiento</span></td>
                    <td>20/01/2024</td>
                    <td>Ayer 16:45</td>
                    <td>
                        <button class="btn btn-outline-primary btn-sm" onclick="editarTecnico(this)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm" onclick="verHistorial(this)">
                            <i class="bi bi-clock-history"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm" onclick="eliminarTecnico(this)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Sección de Reportes -->
    <div class="report-section">
        <h4><i class="bi bi-graph-up"></i> Reportes y Estadísticas</h4>
        <div class="row">
            <div class="col-md-6">
                <h5>Equipos por Categoría</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Herramientas de Medición</span>
                        <span class="badge bg-primary">8</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Equipos de Computación</span>
                        <span class="badge bg-primary">6</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Herramientas Manuales</span>
                        <span class="badge bg-primary">5</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Equipos de Seguridad</span>
                        <span class="badge bg-primary">3</span>
                    </li>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Actividad Reciente</h5>
                <div class="timeline">
                    <div class="timeline-item">
                        <small class="text-muted">Hoy 14:30</small>
                        <p class="mb-1">Juan Pérez recibió Multímetro Fluke 117</p>
                    </div>
                    <div class="timeline-item">
                        <small class="text-muted">Ayer 16:45</small>
                        <p class="mb-1">María López reportó mantenimiento en Escalera</p>
                    </div>
                    <div class="timeline-item">
                        <small class="text-muted">15/01/2024</small>
                        <p class="mb-1">Nuevo técnico registrado: Carlos Rodríguez</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para editar técnico -->
<div class="modal fade" id="modalEditarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Técnico</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" class="form-control" id="editNombreTecnico">
                </div>
                <div class="mb-3">
                    <label class="form-label">ID de técnico</label>
                    <input type="text" class="form-control" id="editIdTecnico">
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="editEstadoTecnico">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="vacaciones">Vacaciones</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para historial -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history"></i> Historial de Equipos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="timeline">
                    <div class="timeline-item border-start border-primary ps-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>Asignación de Multímetro</strong>
                            <small class="text-muted">Hoy 14:30</small>
                        </div>
                        <p class="mb-1">Juan Pérez recibió Multímetro Fluke 117</p>
                        <small class="text-muted">Estado: Activo</small>
                    </div>
                    <div class="timeline-item border-start border-warning ps-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>Mantenimiento de Laptop</strong>
                            <small class="text-muted">Ayer 16:45</small>
                        </div>
                        <p class="mb-1">Laptop Dell Latitude enviada a mantenimiento</p>
                        <small class="text-muted">Estado: En mantenimiento</small>
                    </div>
                    <div class="timeline-item border-start border-success ps-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>Devolución de Herramientas</strong>
                            <small class="text-muted">15/01/2024</small>
                        </div>
                        <p class="mb-1">Destornilladores devueltos al almacén</p>
                        <small class="text-muted">Estado: Retirado</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Resalta el menú activo
document.querySelector('.sidebar-equipos').classList.add('active');

function agregarTecnico() {
    const tbody = document.getElementById('equipos-body');
    const tr = document.createElement('tr');
    const tecnicoId = 'TEC-' + String(tbody.children.length + 1).padStart(3, '0');
    tr.innerHTML = `
        <td>
            <strong><input type="text" value="Nuevo Técnico" class="form-control-sm"></strong><br>
            <small class="text-muted">ID: ${tecnicoId}</small>
        </td>
        <td>
            <div class="equipos-lista"></div>
            <button class="btn btn-primary btn-sm mt-2" onclick="agregarEquipo(this)">
                <i class="bi bi-plus"></i> Agregar Equipo
            </button>
        </td>
        <td><span class="status-badge status-activo">Activo</span></td>
        <td>${new Date().toLocaleDateString()}</td>
        <td>Ahora</td>
        <td>
            <button class="btn btn-outline-primary btn-sm" onclick="editarTecnico(this)">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-outline-info btn-sm" onclick="verHistorial(this)">
                <i class="bi bi-clock-history"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" onclick="eliminarTecnico(this)">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    actualizarEstadisticas();
}

function eliminarTecnico(btn) {
    if (confirm('¿Estás seguro de que quieres eliminar este técnico?')) {
        btn.closest('tr').remove();
        actualizarEstadisticas();
    }
}

function agregarEquipo(btn) {
    const lista = btn.parentNode.querySelector('.equipos-lista');
    const div = document.createElement('div');
    div.className = 'equipo-item';
    div.innerHTML = `
        <input type="text" value="Nuevo Equipo" class="form-control-sm">
        <select class="form-select form-select-sm">
            <option value="activo" selected>Activo</option>
            <option value="mantenimiento">Mantenimiento</option>
            <option value="retirado">Retirado</option>
        </select>
        <button class="btn btn-danger btn-sm" onclick="eliminarEquipo(this)">X</button>
    `;
    lista.appendChild(div);
    actualizarEstadisticas();
}

function eliminarEquipo(btn) {
    if (confirm('¿Estás seguro de que quieres eliminar este equipo?')) {
        btn.parentNode.remove();
        actualizarEstadisticas();
    }
}

function editarTecnico(btn) {
    const row = btn.closest('tr');
    const nombre = row.querySelector('strong').textContent;
    const id = row.querySelector('small').textContent.replace('ID: ', '');
    
    document.getElementById('editNombreTecnico').value = nombre;
    document.getElementById('editIdTecnico').value = id;
    
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTecnico'));
    modal.show();
}

function verHistorial(btn) {
    const modal = new bootstrap.Modal(document.getElementById('modalHistorial'));
    modal.show();
}

function generarReporte() {
    alert('Generando reporte PDF...');
    // Aquí iría la lógica para generar el reporte
}

function actualizarEstadisticas() {
    const filas = document.querySelectorAll('#equipos-body tr');
    const equipos = document.querySelectorAll('.equipo-item');
    const equiposMantenimiento = document.querySelectorAll('.equipo-item select option[value="mantenimiento"]:checked');
    const equiposRetirados = document.querySelectorAll('.equipo-item select option[value="retirado"]:checked');
    
    document.getElementById('totalTecnicos').textContent = filas.length;
    document.getElementById('totalEquipos').textContent = equipos.length;
    document.getElementById('equiposMantenimiento').textContent = equiposMantenimiento.length;
    document.getElementById('equiposRetirados').textContent = equiposRetirados.length;
}

// Filtros y búsqueda
document.getElementById('buscarTecnico').addEventListener('input', function() {
    const busqueda = this.value.toLowerCase();
    document.querySelectorAll('#equipos-body tr').forEach(tr => {
        const nombre = tr.querySelector('strong').textContent.toLowerCase();
        tr.style.display = nombre.includes(busqueda) ? '' : 'none';
    });
});

document.getElementById('filtroEstado').addEventListener('change', function() {
    const estado = this.value;
    document.querySelectorAll('#equipos-body tr').forEach(tr => {
        const statusBadge = tr.querySelector('.status-badge');
        const estadoFila = statusBadge ? statusBadge.textContent.toLowerCase() : '';
        tr.style.display = !estado || estadoFila.includes(estado) ? '' : 'none';
    });
});

// Inicializar estadísticas
actualizarEstadisticas();
</script>
</body>
</html> 