<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Procesamiento de alta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'agregar') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? null;
    if ($codigo && $nombre) {
        $stmt = $mysqli->prepare("INSERT INTO tecnicos (codigo, nombre, fecha_ingreso) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $codigo, $nombre, $fecha_ingreso);
        if ($stmt->execute()) {
            $success = 'Técnico registrado correctamente.';
        } else {
            $error = 'Error al registrar técnico: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Completa todos los campos obligatorios.';
    }
    // Respuesta AJAX
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        $tecnicos = $mysqli->query("SELECT * FROM tecnicos ORDER BY nombre ASC");
        ob_start();
        include __DIR__ . '/partials/tecnicos_listado.php';
        $html = ob_get_clean();
        echo json_encode(['success'=>$success, 'error'=>$error, 'tecnicos_html'=>$html]);
        exit;
    }
}
// Procesamiento de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'editar') {
    $tecnico_id = intval($_POST['tecnico_id']);
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $fecha_ingreso = $_POST['fecha_ingreso'] ?? null;
    if ($tecnico_id && $codigo && $nombre) {
        $stmt = $mysqli->prepare("UPDATE tecnicos SET codigo=?, nombre=?, fecha_ingreso=? WHERE tecnico_id=?");
        $stmt->bind_param('sssi', $codigo, $nombre, $fecha_ingreso, $tecnico_id);
        if ($stmt->execute()) {
            $success = 'Técnico actualizado correctamente.';
        } else {
            $error = 'Error al actualizar técnico: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Completa todos los campos obligatorios.';
    }
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        $tecnicos = $mysqli->query("SELECT * FROM tecnicos ORDER BY nombre ASC");
        ob_start();
        include __DIR__ . '/partials/tecnicos_listado.php';
        $html = ob_get_clean();
        echo json_encode(['success'=>$success, 'error'=>$error, 'tecnicos_html'=>$html]);
        exit;
    }
}
// Procesamiento de eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'eliminar') {
    $tecnico_id = intval($_POST['tecnico_id']);
    if ($tecnico_id) {
        $stmt = $mysqli->prepare("DELETE FROM tecnicos WHERE tecnico_id=?");
        $stmt->bind_param('i', $tecnico_id);
        if ($stmt->execute()) {
            $success = 'Técnico eliminado correctamente.';
        } else {
            $error = 'Error al eliminar técnico: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = 'Técnico no válido.';
    }
    if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
        $tecnicos = $mysqli->query("SELECT * FROM tecnicos ORDER BY nombre ASC");
        ob_start();
        include __DIR__ . '/partials/tecnicos_listado.php';
        $html = ob_get_clean();
        echo json_encode(['success'=>$success, 'error'=>$error, 'tecnicos_html'=>$html]);
        exit;
    }
}
// Listado principal
$tecnicos = $mysqli->query("SELECT * FROM tecnicos ORDER BY nombre ASC");
// Calcular el siguiente código de técnico
$res = $mysqli->query("SELECT MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) as max_codigo FROM tecnicos WHERE codigo LIKE 'TEC-%'");
$row = $res ? $res->fetch_assoc() : null;
$ultimo_num = $row && $row['max_codigo'] ? intval($row['max_codigo']) : 0;
$siguiente_codigo = 'TEC-' . str_pad($ultimo_num + 1, 4, '0', STR_PAD_LEFT);
// Fecha de hoy para el campo de fecha
$fecha_hoy = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Técnicos | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .main-content { margin-top: 40px; margin-left: 250px; padding: 24px; width: calc(100vw - 250px); box-sizing: border-box; }
        .sidebar.collapsed ~ .main-content { margin-left: 70px !important; width: calc(100vw - 70px) !important; transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s; }
        .tecnicos-header { background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 24px rgba(18,24,102,0.10); border: 1.5px solid #e3e6f0; }
        .tecnicos-title { font-size: 2.2rem; color: #121866; font-weight: 800; margin: 0; }
        .btn-agregar { font-size: 1.1rem; border-radius: 8px; font-weight: 600; }
        .table thead th { background: #121866; color: #fff; }
        .table td, .table th { vertical-align: middle; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="tecnicos-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="tecnicos-title"><i class="bi bi-person-badge"></i> Técnicos</h1>
            <p class="text-muted mb-0">Catálogo de técnicos registrados</p>
        </div>
        <button class="btn btn-primary btn-agregar" data-bs-toggle="modal" data-bs-target="#modalAgregarTecnico">
            <i class="bi bi-plus-circle"></i> Agregar técnico
        </button>
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
    <div id="tecnicosContainer">
        <?php include __DIR__ . '/partials/tecnicos_listado.php'; ?>
    </div>
</main>
<!-- Modal Agregar Técnico -->
<div class="modal fade" id="modalAgregarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off" class="form-ajax">
                <input type="hidden" name="accion" value="agregar">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-plus"></i> Agregar técnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" id="codigo" required value="<?= $siguiente_codigo ?>">
                    </div>
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_ingreso" class="form-label">Fecha de ingreso</label>
                        <input type="date" class="form-control" name="fecha_ingreso" id="fecha_ingreso" value="<?= $fecha_hoy ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Editar Técnico -->
<div class="modal fade" id="modalEditarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" autocomplete="off" class="form-ajax">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="tecnico_id" id="edit_tecnico_id">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar técnico</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" name="codigo" id="edit_codigo" required readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="edit_nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_fecha_ingreso" class="form-label">Fecha de ingreso</label>
                        <input type="date" class="form-control" name="fecha_ingreso" id="edit_fecha_ingreso">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle"></i> Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Eliminar Técnico -->
<div class="modal fade" id="modalEliminarTecnico" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" class="form-ajax">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="tecnico_id" id="delete_tecnico_id">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-trash"></i> Eliminar técnico</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar al técnico <strong id="delete_tecnico_nombre"></strong>?</p>
                    <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('.sidebar-movimientos').classList.remove('active');
    // Puedes agregar una clase activa para técnicos si la agregas al sidebar
    // document.querySelector('.sidebar-tecnicos').classList.add('active');
    // Delegación de eventos para formularios AJAX
    function recargarTecnicosLista(data) {
        if (data.tecnicos_html) {
            document.getElementById('tecnicosContainer').innerHTML = data.tecnicos_html;
        }
        if (data.success) {
            mostrarMensaje('success', data.success);
        } else if (data.error) {
            mostrarMensaje('danger', data.error);
        }
        delegarBotonesTecnicos();
    }
    function mostrarMensaje(tipo, mensaje) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${tipo} alert-dismissible fade show`;
        alert.role = 'alert';
        alert.innerHTML = `<i class="bi bi-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${mensaje}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.querySelector('.main-content').insertBefore(alert, document.querySelector('.main-content').firstChild);
        setTimeout(() => { if (alert) alert.remove(); }, 4000);
    }
    // Delegación de eventos para formularios AJAX
    function onAjaxFormSubmit(e) {
        const form = e.target;
        if (form.classList.contains('form-ajax')) {
            e.preventDefault();
            const formData = new FormData(form);
            formData.append('ajax', '1');
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                // Cerrar modal
                const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                if (modal) modal.hide();
                recargarTecnicosLista(data);
            });
        }
    }
    document.addEventListener('submit', onAjaxFormSubmit);
    // Limpiar formularios al cerrar modales
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modalEl => {
        modalEl.addEventListener('hidden.bs.modal', function() {
            const forms = modalEl.querySelectorAll('form');
            forms.forEach(f => f.reset());
        });
    });
    // Delegar eventos para nuevos botones de editar/eliminar tras AJAX
    function delegarBotonesTecnicos() {
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('edit_tecnico_id').value = this.dataset.id;
                document.getElementById('edit_codigo').value = this.dataset.codigo;
                document.getElementById('edit_nombre').value = this.dataset.nombre;
                document.getElementById('edit_fecha_ingreso').value = this.dataset.fecha || '';
                // Mostrar el modal de edición
                const modal = new bootstrap.Modal(document.getElementById('modalEditarTecnico'));
                modal.show();
            };
        });
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = function() {
                document.getElementById('delete_tecnico_id').value = this.dataset.id;
                document.getElementById('delete_tecnico_nombre').textContent = this.dataset.nombre;
                // Mostrar el modal de eliminación
                const modal = new bootstrap.Modal(document.getElementById('modalEliminarTecnico'));
                modal.show();
            };
        });
    }
    document.addEventListener('DOMContentLoaded', delegarBotonesTecnicos);
</script>
</body>
</html>