<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";

    // Procesar formularios
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        if ($_POST['action'] === 'add' && !empty($_POST['category_name'])) {
            $category_name = trim($_POST['category_name']);
            
            // Verificar si la categoría ya existe
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ?");
            $stmt->bind_param("s", $category_name);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc()['count'] > 0;
            $stmt->close();
            
            if ($exists) {
                $error = "La categoría ya existe.";
            } else {
                // Insertar nueva categoría
                $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->bind_param("s", $category_name);
                if ($stmt->execute()) {
                    $success = "Categoría agregada correctamente.";
                } else {
                    $error = "Error al agregar la categoría: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'edit' && !empty($_POST['category_id']) && !empty($_POST['category_name'])) {
            $category_id = intval($_POST['category_id']);
            $category_name = trim($_POST['category_name']);
            
            // Verificar si el nombre ya existe en otra categoría
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM categories WHERE name = ? AND category_id != ?");
            $stmt->bind_param("si", $category_name, $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc()['count'] > 0;
            $stmt->close();
            
            if ($exists) {
                $error = "Ya existe una categoría con ese nombre.";
            } else {
                // Actualizar categoría
                $stmt = $mysqli->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
                $stmt->bind_param("si", $category_name, $category_id);
                if ($stmt->execute()) {
                    $success = "Categoría actualizada correctamente.";
                } else {
                    $error = "Error al actualizar la categoría: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['category_id'])) {
            $category_id = intval($_POST['category_id']);
            
            // Verificar si hay productos usando esta categoría
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $has_products = $result->fetch_assoc()['count'] > 0;
            $stmt->close();
            
            if ($has_products) {
                $error = "No se puede eliminar la categoría porque tiene productos asociados.";
            } else {
                // Eliminar categoría
                $stmt = $mysqli->prepare("DELETE FROM categories WHERE category_id = ?");
                $stmt->bind_param("i", $category_id);
                if ($stmt->execute()) {
                    $success = "Categoría eliminada correctamente.";
                } else {
                    $error = "Error al eliminar la categoría: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }

    // Obtener todas las categorías con conteo de productos
    $categorias = $mysqli->query("
        SELECT 
            c.category_id,
            c.name, 
            COUNT(p.product_id) as total_productos,
            SUM(p.quantity) as stock_total,
            AVG(p.price) as precio_promedio
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id
        GROUP BY c.category_id, c.name
        ORDER BY total_productos DESC, c.name ASC
    ");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorías | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
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
        .titulo-lista {
            font-size: 2rem;
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .category-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .category-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .category-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .category-actions {
            display: flex;
            gap: 8px;
        }
        .btn-action {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-delete {
            background: #ffebee;
            color: #c62828;
        }
        .btn-delete:hover {
            background: #c62828;
            color: #fff;
        }
        .category-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .category-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }
        .stat-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .stat-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .form-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            margin-bottom: 24px;
        }
        .form-card .card-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
            border: none;
        }
        .form-card .card-body {
            padding: 24px;
        }
        .form-label {
            font-weight: 600;
            color: #121866;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #cfd8dc;
            background: #f7f9fc;
            font-size: 1rem;
            padding: 12px 16px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
        }
        .btn-primary {
            background: linear-gradient(135deg, #121866, #232a7c);
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(18,24,102,0.3);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .modal-header {
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 16px 16px 0 0;
            border: none;
        }
        .modal-body {
            padding: 24px;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .titulo-lista { font-size: 1.4rem; }
            .category-stats { grid-template-columns: repeat(2, 1fr); }
            .category-actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista">
            <i class="bi bi-tags"></i> 
            Gestión de Categorías
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show d-flex flex-column align-items-start gap-2" role="alert">
                <div>
                    <i class="bi bi-check-circle"></i>
                    <?= $success ?>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="list.php" class="btn btn-success btn-sm"><i class="bi bi-list"></i> Volver al listado de productos</a>
                    <?php if (isset($_POST['action']) && $_POST['action'] === 'add'): ?>
                        <a href="categories.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Agregar otra categoría</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulario para nueva categoría -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Nueva Categoría</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formNuevaCategoria">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="category_name" class="form-label">Nombre de la categoría</label>
                                <input type="text" class="form-control" name="category_name" id="category_name" required 
                                       placeholder="Ej: Cámaras de Seguridad">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Crear Categoría
                    </button>
                </form>
            </div>
        </div>

        <!-- Lista de categorías -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="color: #121866; font-weight: 600;">
                <i class="bi bi-list-ul"></i> Categorías Existentes
            </h4>
            <small class="text-muted">
                <?= $categorias ? $categorias->num_rows : 0 ?> categorías
            </small>
        </div>

        <?php if ($categorias && $categorias->num_rows > 0): ?>
            <div class="row">
                <?php while ($cat = $categorias->fetch_assoc()): ?>
                    <div class="col-lg-6 col-xl-4 mb-3">
                        <div class="category-card">
                            <div class="category-header">
                                <h5 class="category-title">
                                    <i class="bi bi-tag"></i> 
                                    <?= htmlspecialchars($cat['name']) ?>
                                </h5>
                                <div class="category-actions">
                                    <button type="button" class="btn-action btn-edit" 
                                            onclick="editarCategoria(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($cat['total_productos'] == 0): ?>
                                        <button type="button" class="btn-action btn-delete" 
                                                onclick="eliminarCategoria(<?= $cat['category_id'] ?>, '<?= htmlspecialchars($cat['name']) ?>')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="category-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?= $cat['total_productos'] ?></span>
                                    <span class="stat-label">Productos</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?= $cat['stock_total'] ?: 0 ?></span>
                                    <span class="stat-label">Stock</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number">$<?= number_format($cat['precio_promedio'] ?: 0, 0) ?></span>
                                    <span class="stat-label">Precio Prom.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-tags"></i>
                <h5>No hay categorías creadas</h5>
                <p>Crea tu primera categoría para organizar mejor tus productos.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4">
            <a href="list.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Volver al listado de productos
            </a>
        </div>
    </main>

    <!-- Modal para editar categoría -->
    <div class="modal fade" id="modalEditarCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Categoría</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditarCategoria">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="category_id" id="edit_category_id">
                        <div class="mb-3">
                            <label for="edit_category_name" class="form-label">Nombre de la categoría</label>
                            <input type="text" class="form-control" name="category_name" id="edit_category_name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para confirmar eliminación -->
    <div class="modal fade" id="modalEliminarCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Confirmar Eliminación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEliminarCategoria">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" id="delete_category_id">
                        <p>¿Estás seguro de que quieres eliminar la categoría <strong id="delete_category_name"></strong>?</p>
                        <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-productos').classList.add('active');

        // Función para editar categoría
        function editarCategoria(id, nombre) {
            document.getElementById('edit_category_id').value = id;
            document.getElementById('edit_category_name').value = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarCategoria'));
            modal.show();
        }

        // Función para eliminar categoría
        function eliminarCategoria(id, nombre) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = nombre;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEliminarCategoria'));
            modal.show();
        }

        // Auto-focus en el campo de nombre al abrir el modal de edición
        document.getElementById('modalEditarCategoria').addEventListener('shown.bs.modal', function () {
            document.getElementById('edit_category_name').focus();
        });

        // Auto-focus en el campo de nombre al cargar la página
        document.getElementById('category_name').focus();
    </script>
</body>
</html> 