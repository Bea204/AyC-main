<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    // Obtener filtros desde GET
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
    $proveedor = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';

    // Construir consulta SQL con filtros
    $query = "SELECT p.*, c.name as category_name, s.name as supplier_name,
               COALESCE(SUM(b.metros_actuales), 0) as metros_totales,
               COUNT(b.bobina_id) as total_bobinas
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id 
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        LEFT JOIN bobinas b ON p.product_id = b.product_id AND b.is_active = 1
        WHERE 1=1";
    $params = [];
    $types = '';
    if ($busqueda) {
        $query .= " AND (p.product_name LIKE ? OR p.sku LIKE ? OR c.name LIKE ? OR s.name LIKE ? OR p.description LIKE ?)";
        $like = "%$busqueda%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
        $types .= 'sssss';
    }
    if ($categoria) {
        $query .= " AND c.category_id = ?";
        $params[] = $categoria;
        $types .= 'i';
    }
    if ($proveedor) {
        $query .= " AND s.supplier_id = ?";
        $params[] = $proveedor;
        $types .= 'i';
    }
    $query .= " GROUP BY p.product_id, p.product_name, p.sku, p.price, p.quantity, p.category_id, p.supplier_id, p.description, p.barcode, p.image, p.tipo_gestion, p.cost_price, p.min_stock, p.max_stock, p.is_active, p.created_at, p.updated_at, c.name, s.name
        ORDER BY p.created_at DESC";
    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Obtener categorías únicas para el filtro
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    // Obtener proveedores únicos para el filtro
    $proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Productos | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
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
         /* Sobrescribe el fondo del thead de las tablas de productos */
        .table thead,
        .table thead th,
        thead.table-dark th {
            background: #121866 !important;
            color: #fff !important;
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
        .busqueda-bar {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
            padding: 18px 18px 10px 18px;
        }
        #busquedaProducto {
            flex: 1 1 220px;
            padding: 10px 14px;
            border: 1.5px solid #cfd8dc;
            border-radius: 7px;
            font-size: 1rem;
            background: #f7f9fc;
            transition: border 0.2s;
            outline: none;
        }
        #busquedaProducto:focus {
            border-color: #121866;
        }
        #filtroCategoria, #filtroProveedor {
            min-width: 150px;
            max-width: 200px;
        }
        .filtros-container {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .add-btn, .btn-outline-info {
            display: inline-block;
            width: auto;
            padding: 8px 18px;
            font-size: 0.98rem;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(18,24,102,0.08);
            transition: background 0.18s;
            margin-bottom: 0;
        }
        .add-btn {
            background: #121866;
            color: #fff;
        }
        .add-btn:hover {
            background: #232a7c;
        }
        .btn-outline-info {
            border: 1.5px solid #00bcd4;
            color: #00bcd4;
            background: #f4f6fb;
        }
        .btn-outline-info:hover {
            background: #00bcd4;
            color: #fff;
        }
        .acciones {
            display: flex;
            gap: 6px;
            justify-content: center;
        }
        .acciones a {
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        @media (max-width: 900px) {
            .main-content { width: calc(100vw - 70px); margin-left: 70px; max-width: 100vw; padding: 12px 2vw; box-sizing: border-box; }
            .titulo-lista { font-size: 1.2rem; }
            .busqueda-bar { flex-direction: column; gap: 10px; align-items: stretch; padding: 12px 6px 6px 6px; }
            .btn, .add-btn, .btn-outline-info { display: block; width: 100%; }
            .acciones { flex-direction: row; gap: 4px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="titulo-lista"><i class="bi bi-box-seam"></i> Listado de productos</div>
        <div class="busqueda-bar">
            <form method="GET" class="filtros-container" id="formFiltros">
                <input type="text" id="busquedaProducto" name="busqueda" class="form-control" placeholder="Buscar por nombre, SKU, categoría, proveedor..." value="<?= htmlspecialchars($busqueda) ?>" autocomplete="off">
                <select id="filtroCategoria" name="categoria" class="form-select">
                    <option value="">Todas las categorías</option>
                    <?php $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($cat['category_id']) ?>" <?= $categoria == $cat['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <select id="filtroProveedor" name="proveedor" class="form-select">
                    <option value="">Todos los proveedores</option>
                    <?php $proveedores->data_seek(0); while ($prov = $proveedores->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($prov['supplier_id']) ?>" <?= $proveedor == $prov['supplier_id'] ? 'selected' : '' ?>><?= htmlspecialchars($prov['name']) ?></option>
                    <?php endwhile; ?>
                </select>
                <a href="list.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i> Limpiar</a>
                <a href="add.php" class="add-btn"><i class="bi bi-plus-circle"></i> Agregar producto</a>
                <a href="categories.php" class="btn btn-outline-info"><i class="bi bi-tags"></i> Gestionar Categorías</a>
            </form>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <small class="text-muted">
                Mostrando <?= $result ? $result->num_rows : 0 ?> productos
            </small>
        </div>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'stock'): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i>
                No puedes eliminar un producto que aún tiene stock. Debes de dar de baja el inventario primero.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover table-productos">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col" class="img-col">Imagen</th>
                            <th scope="col">Producto</th>
                            <th scope="col" class="barcode-col">Código de barras</th>
                            <th scope="col">Categoría</th>
                            <th scope="col">Proveedor</th>
                            <th scope="col">Precio Unitario</th>
                            <th scope="col">Cantidad</th>
                            <th scope="col">Fecha de alta</th>
                            <th scope="col" style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                            <tr data-product-id="<?= $row['product_id'] ?>" 
                                data-category="<?= htmlspecialchars($row['category_name'] ?? '') ?>"
                                data-supplier="<?= htmlspecialchars($row['supplier_name'] ?? '') ?>">
                                <td><?= $i++ ?></td>
                                <td class="img-col">
                                    <?php if ($row['image']): ?>
                                        <img src="../<?= htmlspecialchars($row['image']) ?>" alt="Imagen del producto" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="bi bi-image" style="font-size: 2rem;"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="img-nombre-wrap">
                                    <div>
                                            <div class="nombre-producto"><?= htmlspecialchars($row['product_name']) ?></div>
                                            <div class="sku-badge">SKU: <?= htmlspecialchars($row['sku']) ?></div>
                                        <?php if ($row['description']): ?>
                                                <div class="desc-producto"><?= htmlspecialchars($row['description']) ?></div>
                                        <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="barcode-col">
                                    <?php if ($row['barcode']): ?>
                                        <span><?= htmlspecialchars($row['barcode']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin código</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['category_name'] ?? 'Sin categoría') ?></td>
                                <td><?= htmlspecialchars($row['supplier_name'] ?? 'Sin proveedor') ?></td>
                                <td>$<?= number_format($row['price'], 2) ?></td>
                                <td>
                                    <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                        <?php if ($row['total_bobinas'] > 0): ?>
                                            <?= $row['total_bobinas'] ?> bobina<?= $row['total_bobinas'] != 1 ? 's' : '' ?> (<?= number_format($row['metros_totales'], 0) ?>m)
                                        <?php else: ?>
                                            0 bobinas
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge <?= $row['quantity'] > 10 ? 'bg-success' : ($row['quantity'] > 0 ? 'bg-warning' : 'bg-danger') ?>">
                                            <?= $row['quantity'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <div class="acciones">
                                        <a href="edit.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-primary btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($row['tipo_gestion'] === 'bobina'): ?>
                                            <a href="../bobinas/gestionar.php?product_id=<?= $row['product_id'] ?>" class="btn btn-outline-info btn-sm" title="Gestionar bobinas">
                                                <i class="bi bi-receipt"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="delete.php?id=<?= $row['product_id'] ?>" class="btn btn-outline-danger btn-sm btn-delete" title="Eliminar" 
                                           onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-box-seam" style="font-size: 4rem; color: #ccc;"></i>
                <h4 class="mt-3">No hay productos</h4>
                <p class="text-muted">Agrega tu primer producto para comenzar</p>
                <a href="add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Agregar Producto
                </a>
            </div>
        <?php endif; ?>
    </main>

    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-productos').classList.add('active');
        // Enviar automáticamente el formulario al cambiar categoría o proveedor
        document.getElementById('filtroCategoria').addEventListener('change', function() {
            document.getElementById('formFiltros').submit();
        });
        document.getElementById('filtroProveedor').addEventListener('change', function() {
            document.getElementById('formFiltros').submit();
        });
        // Enviar automáticamente el formulario al escribir en el input de búsqueda (con retardo)
        let busquedaTimeout;
        document.getElementById('busquedaProducto').addEventListener('input', function() {
            clearTimeout(busquedaTimeout);
            busquedaTimeout = setTimeout(function() {
                document.getElementById('formFiltros').submit();
            }, 400);
        });
    </script>
</body>
</html>
