<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Crear carpeta de servicios si no existe
$upload_dir = '../uploads/services/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Procesamiento de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar':
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $categoria = trim($_POST['categoria']);
                $precio = floatval($_POST['precio']);
                $imagen = '';
                // Procesar subida de imagen
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['imagen'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (in_array($file['type'], $allowed_types)) {
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'servicio_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = $upload_dir . $filename;
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $imagen = $filename;
                        } else {
                            $error = 'Error al subir la imagen.';
                        }
                    } else {
                        $error = 'Tipo de archivo no permitido. Solo JPG, PNG, GIF, WEBP.';
                    }
                }
                if (!$imagen) {
                    $imagen = '';
                }
                if ($nombre && $precio > 0 && !$error) {
                    $stmt = $mysqli->prepare("INSERT INTO servicios (nombre, descripcion, categoria, precio, imagen) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('sssds', $nombre, $descripcion, $categoria, $precio, $imagen);
                    if ($stmt->execute()) {
                        header("Location: index.php?success=1");
                        exit;
                    } else {
                        $error = 'Error al agregar servicio: ' . $stmt->error;
                    }
                    $stmt->close();
                } else if (!$error) {
                    $error = 'Completa todos los campos obligatorios.';
                }
                break;
            case 'editar':
                $servicio_id = intval($_POST['servicio_id']);
                $nombre = trim($_POST['nombre']);
                $descripcion = trim($_POST['descripcion']);
                $categoria = trim($_POST['categoria']);
                $precio = floatval($_POST['precio']);
                // Inicializar imagen con la actual (si existe)
                $imagen = isset($_POST['imagen_actual']) ? trim($_POST['imagen_actual']) : '';
                // Procesar subida de imagen nueva
                if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['imagen'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (in_array($file['type'], $allowed_types)) {
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = 'servicio_' . time() . '_' . uniqid() . '.' . $extension;
                        $filepath = $upload_dir . $filename;
                        if (move_uploaded_file($file['tmp_name'], $filepath)) {
                            $imagen = $filename;
                        } else {
                            $error = 'Error al subir la imagen.';
                        }
                    } else {
                        $error = 'Tipo de archivo no permitido. Solo JPG, PNG, GIF, WEBP.';
                    }
                }
                // Si no hay imagen nueva ni anterior, dejarlo como cadena vacía
                if (!$imagen) {
                    $imagen = '';
                }
                // --- LOG DE DEPURACIÓN ---
                $log = date('Y-m-d H:i:s') . "\n";
                $log .= "servicio_id: $servicio_id\n";
                $log .= "nombre: $nombre\n";
                $log .= "descripcion: $descripcion\n";
                $log .= "categoria: $categoria\n";
                $log .= "precio: $precio\n";
                $log .= "imagen_actual: " . (isset($_POST['imagen_actual']) ? $_POST['imagen_actual'] : 'NO SET') . "\n";
                $log .= "imagen_final: $imagen\n";
                file_put_contents(__DIR__ . '/servicios_debug.log', $log . "\n", FILE_APPEND);
                // --- FIN LOG ---
                if ($servicio_id && $nombre && $precio > 0 && !$error) {
                    $stmt = $mysqli->prepare("UPDATE servicios SET nombre = ?, descripcion = ?, categoria = ?, precio = ?, imagen = ? WHERE servicio_id = ?");
                    $stmt->bind_param('sssdsi', $nombre, $descripcion, $categoria, $precio, $imagen, $servicio_id);
                    $sql_debug = $mysqli->real_escape_string("UPDATE servicios SET nombre = '$nombre', descripcion = '$descripcion', categoria = '$categoria', precio = $precio, imagen = '$imagen' WHERE servicio_id = $servicio_id");
                    file_put_contents(__DIR__ . '/servicios_debug.log', "SQL: $sql_debug\n", FILE_APPEND);
                    if ($stmt->execute()) {
                        file_put_contents(__DIR__ . '/servicios_debug.log', "RESULTADO: OK\n\n", FILE_APPEND);
                        header("Location: index.php?success=1");
                        exit;
                    } else {
                        $error = 'Error al actualizar servicio: ' . $stmt->error;
                        file_put_contents(__DIR__ . '/servicios_debug.log', "RESULTADO: ERROR: $error\n\n", FILE_APPEND);
                    }
                    $stmt->close();
                } else if (!$error) {
                    $error = 'Completa todos los campos obligatorios.';
                }
                break;
            case 'eliminar':
                $servicio_id = intval($_POST['servicio_id']);
                if ($servicio_id) {
                    // Obtener imagen antes de eliminar
                    $stmt = $mysqli->prepare("SELECT imagen FROM servicios WHERE servicio_id = ?");
                    $stmt->bind_param('i', $servicio_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $servicio = $result->fetch_assoc();
                    $stmt->close();
                    // Eliminar imagen si existe
                    if ($servicio && $servicio['imagen'] && file_exists($upload_dir . $servicio['imagen'])) {
                        unlink($upload_dir . $servicio['imagen']);
                    }
                    $stmt = $mysqli->prepare("UPDATE servicios SET is_active = 0 WHERE servicio_id = ?");
                    $stmt->bind_param('i', $servicio_id);
                    if ($stmt->execute()) {
                        header("Location: index.php?success=1");
                        exit;
                    } else {
                        $error = 'Error al eliminar servicio: ' . $stmt->error;
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Obtener servicios
$servicios = $mysqli->query("SELECT * FROM servicios WHERE is_active = 1 ORDER BY categoria, nombre ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Servicios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fb; }
        .main-content { background: #fff; border-radius: 18px; box-shadow: 0 4px 32px rgba(18,24,102,0.07); margin-top: 40px; margin-left: 250px; padding: 24px; width: calc(100vw - 250px); box-sizing: border-box; }
        .sidebar.collapsed ~ .main-content { margin-left: 70px !important; width: calc(100vw - 70px) !important; transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s; }
        .form-section { background: #fff; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 12px rgba(18,24,102,0.07); }
        .section-title { font-size: 1.3rem; font-weight: 700; color: #121866; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .servicio-imagen { max-width: 60px; max-height: 60px; object-fit: cover; border-radius: 4px; }
        .imagen-preview { max-width: 100px; max-height: 100px; object-fit: cover; border-radius:4px; margin-top:10px; }
        .file-input-wrapper { position: relative; }
        .file-input-wrapper input[type=file] { position: absolute; opacity: 0; width:100%; cursor: pointer; }
        .file-input-wrapper .btn { pointer-events: none; }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-tools"></i> Gestión de Servicios</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalServicio">
            <i class="bi bi-plus-circle"></i> Nuevo Servicio
        </button>
    </div>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Imagen</th>
                    <th>Servicio</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Precio</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servicios && $servicios->num_rows > 0): ?>
                    <?php while ($servicio = $servicios->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php
                                $img_path = '../uploads/services/' . $servicio['imagen'];
                                if ($servicio['imagen']) {
                                    if (file_exists($img_path)) {
                                        echo '<img src="' . $img_path . '" alt="Imagen servicio" class="servicio-imagen">';
                                    } else {
                                        echo '<span style="color:red">No existe: ' . htmlspecialchars($servicio['imagen']) . '</span>';
                                    }
                                } else {
                                    echo '<i class="bi bi-tools text-muted" style="font-size: 2rem;"></i>';
                                }
                                ?>
                            </td>
                            <td><strong><?= htmlspecialchars($servicio['nombre']) ?></strong></td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($servicio['categoria'] ?: 'Sin categoría') ?></span>
                            </td>
                            <td><?= htmlspecialchars($servicio['descripcion'] ?: '-') ?></td>
                            <td>$<?= number_format($servicio['precio'], 2) ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarServicio(<?= $servicio['servicio_id'] ?>, '<?= htmlspecialchars($servicio['nombre']) ?>', '<?= htmlspecialchars($servicio['descripcion']) ?>', '<?= htmlspecialchars($servicio['categoria']) ?>', <?= $servicio['precio'] ?>, '<?= htmlspecialchars($servicio['imagen']) ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarServicio(<?= $servicio['servicio_id'] ?>, '<?= htmlspecialchars($servicio['nombre']) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            <i class="bi bi-inbox"></i> No hay servicios registrados
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
<!-- Modal para agregar/editar servicio -->
<div class="modal fade" id="modalServicio" tabindex="-1" aria-labelledby="modalServicioLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalServicioLabel">Nuevo Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="accion" id="accionServicio" value="agregar">
                    <input type="hidden" name="servicio_id" id="servicio_id">
                    <input type="hidden" name="imagen_actual" id="imagen_actual">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del servicio *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="categoria" class="form-label">Categoría</label>
                            <input type="text" class="form-control" id="categoria" name="categoria" placeholder="ej: Instalación">
                        </div>
                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio *</label>
                            <input type="number" class="form-control" id="precio" name="precio" min="0" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label for="imagen" class="form-label">Imagen del servicio</label>
                            <div class="file-input-wrapper">
                                <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*" onchange="previewImage(this)">
                                <button type="button" class="btn btn-outline-secondary w-100">
                                    <i class="bi bi-upload"></i>Seleccionar imagen
                                </button>
                            </div>
                            <div id="imagen-preview"></div>
                        </div>
                        <div class="col-md-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Descripción detallada del servicio..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/script.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    const preview = document.getElementById('imagen-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="imagen-preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}
function editarServicio(id, nombre, descripcion, categoria, precio, imagen) {
    document.getElementById('accionServicio').value = 'editar';
    document.getElementById('servicio_id').value = id;
    document.getElementById('nombre').value = nombre;
    document.getElementById('descripcion').value = descripcion;
    document.getElementById('categoria').value = categoria;
    document.getElementById('precio').value = precio;
    document.getElementById('imagen_actual').value = (imagen && imagen !== 'null' && imagen !== 'undefined' && imagen !== '0') ? imagen : '';
    document.getElementById('modalServicioLabel').textContent = 'Editar Servicio';
    const preview = document.getElementById('imagen-preview');
    if (imagen && imagen !== 'null' && imagen !== 'undefined' && imagen.trim() !== '' && imagen !== '0') {
        const safeImagen = imagen.replace(/"/g, '&quot;');
        preview.innerHTML = `<img src="../uploads/services/${safeImagen}" class="imagen-preview" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class='text-muted'><i class='bi bi-image'></i> Imagen no encontrada</span>';">`;
    } else {
        preview.innerHTML = '';
    }
    const modal = new bootstrap.Modal(document.getElementById('modalServicio'));
    modal.show();
}
function eliminarServicio(id, nombre) {
    if (confirm(`¿Estás seguro de que quieres eliminar el servicio "${nombre}"?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="servicio_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
$('#modalServicio').on('hidden.bs.modal', function() {
    $('#accionServicio').val('agregar');
    $('#servicio_id').val('');
    $('#nombre').val('');
    $('#descripcion').val('');
    $('#categoria').val('');
    $('#precio').val('');
    $('#imagen_actual').val('');
    $('#imagen').val('');
    $('#imagen-preview').html('');
    $('#modalServicioLabel').text('Nuevo Servicio');
});
</script>
</body>
</html> 