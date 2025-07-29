<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";
    $product = null;
    $sku_auto_generado = false;

    // Get product ID from URL
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header("Location: list.php");
        exit;
    }

    $product_id = intval($_GET['id']);

    // Fetch existing product
    $stmt = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if (!$product) {
        header("Location: list.php");
        exit;
    }

    // Obtener bobinas asociadas a este producto
    $bobinas = $mysqli->query("SELECT * FROM bobinas WHERE product_id = $product_id ORDER BY bobina_id ASC");

    // Obtener categorías existentes para el select
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores existentes para el select
    $proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

    // Obtener tipo de gestión actual
    $tipo_gestion_actual = isset($product['tipo_gestion']) ? $product['tipo_gestion'] : 'normal';
    $allowed_tipos = ['normal','bobina','bolsa','par','kit'];
    if (!in_array($tipo_gestion_actual, $allowed_tipos)) $tipo_gestion_actual = 'normal';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log('DEBUG: Formulario POST recibido en edit.php');
        
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        error_log('DEBUG: Datos procesados - product_name: ' . $product_name . ', sku: ' . $sku . ', price: ' . $price . ', quantity: ' . $quantity);
        
        // Manejar categoría (existente o nueva)
        $category_id = isset($_POST['category']) && $_POST['category'] !== '' ? intval($_POST['category']) : null;
        $new_category = trim($_POST['new_category'] ?? '');
        
        if (!empty($new_category)) {
            $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $new_category);
            if ($stmt->execute()) {
                $category_id = $stmt->insert_id;
                error_log('DEBUG: Nueva categoría creada con ID: ' . $category_id);
            }
            $stmt->close();
        }
        
        // Manejar proveedor (existente o nuevo)
        $supplier_id = isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '' ? intval($_POST['supplier_id']) : null;
        $new_supplier = trim($_POST['new_supplier'] ?? '');
        if (!empty($new_supplier)) {
            $stmt = $mysqli->prepare("INSERT INTO suppliers (name) VALUES (?)");
            $stmt->bind_param("s", $new_supplier);
            if ($stmt->execute()) {
                $supplier_id = $stmt->insert_id;
                error_log('DEBUG: Nuevo proveedor creado con ID: ' . $supplier_id);
            }
            $stmt->close();
        }
        
        // Validar que supplier_id sea válido si se proporcionó
        if ($supplier_id !== null) {
            $stmt = $mysqli->prepare("SELECT supplier_id FROM suppliers WHERE supplier_id = ?");
            $stmt->bind_param("i", $supplier_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error = "El proveedor seleccionado no existe.";
                $supplier_id = null;
            }
            $stmt->close();
        }
        
        // Validar que category_id sea válido si se proporcionó
        if ($category_id !== null) {
            $stmt = $mysqli->prepare("SELECT category_id FROM categories WHERE category_id = ?");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                $error = "La categoría seleccionada no existe.";
                $category_id = null;
            }
            $stmt->close();
        }
        
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        if ($description === '') $description = null;

        // Subida de imagen
        $image_path = $product['image'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $img_tmp = $_FILES['image']['tmp_name'];
            $img_name = basename($_FILES['image']['name']);
            $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array($img_ext, $allowed)) {
                $dir = '../uploads/products/';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $new_name = uniqid('prod_') . '.' . $img_ext;
                $dest = $dir . $new_name;
                if (move_uploaded_file($img_tmp, $dest)) {
                    $image_path = 'uploads/products/' . $new_name;
                    error_log('DEBUG: Imagen subida: ' . $image_path);
                }
            }
        }

        // Nuevo: tipo de gestión
        $tipo_gestion = isset($_POST['tipo_gestion']) ? $_POST['tipo_gestion'] : 'normal';
        if (!in_array($tipo_gestion, $allowed_tipos)) $tipo_gestion = 'normal';
        
        error_log('DEBUG: Tipo gestión: ' . $tipo_gestion);

        // Si el SKU está vacío, generar uno automáticamente
        if ($sku === '') {
            $result = $mysqli->query("SELECT sku FROM products WHERE sku LIKE 'AUTO-%' ORDER BY product_id DESC LIMIT 1");
            $last_auto = $result && $result->num_rows > 0 ? $result->fetch_assoc()['sku'] : null;
            if ($last_auto && preg_match('/AUTO-(\\d+)/', $last_auto, $m)) {
                $next_num = intval($m[1]) + 1;
            } else {
                $next_num = 1;
            }
            $sku = 'AUTO-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            $sku_auto_generado = true;
            error_log('DEBUG: SKU auto-generado: ' . $sku);
        }

        // Campos adicionales del add.php
        $cost_price = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null;
        $min_stock = isset($_POST['min_stock']) && $_POST['min_stock'] !== '' ? intval($_POST['min_stock']) : null;
        $max_stock = isset($_POST['max_stock']) && $_POST['max_stock'] !== '' ? intval($_POST['max_stock']) : null;
        $unit_measure = isset($_POST['unit_measure']) ? trim($_POST['unit_measure']) : null;

        // Validación específica para bobinas (solo si se está editando la cantidad)
        if ($tipo_gestion === 'bobina' && isset($_POST['quantity']) && $_POST['quantity'] !== $product['quantity']) {
            $metros_bobina = $quantity;
            if ($metros_bobina <= 0) {
                $error = 'Los metros de la bobina deben ser mayores a 0.';
            }
        }

        error_log('DEBUG: Antes de validación - product_name: ' . $product_name . ', price: ' . $price . ', quantity: ' . $quantity);

        // Cambia la validación principal:
        // Antes:
        // if ($product_name && $price >= 0 && $quantity >= 0 && empty($error)) {
        // Después:
        if ($product_name && $price >= 0 && empty($error)) {
            error_log('DEBUG: Validación pasada, ejecutando UPDATE');
            error_log('DEBUG: category_id: ' . ($category_id ?? 'NULL') . ', supplier_id: ' . ($supplier_id ?? 'NULL'));
            
            // Construir la consulta dinámicamente para manejar NULLs
            $update_fields = [];
            $params = [];
            $types = '';
            
            $update_fields[] = "product_name = ?";
            $params[] = $product_name;
            $types .= 's';
            
            $update_fields[] = "sku = ?";
            $params[] = $sku;
            $types .= 's';
            
            $update_fields[] = "price = ?";
            $params[] = $price;
            $types .= 'd';
            
            $update_fields[] = "quantity = ?";
            $params[] = $quantity;
            $types .= 'i';
            
            $update_fields[] = "category_id = ?";
            $params[] = $category_id;
            $types .= 'i';
            
            $update_fields[] = "supplier_id = ?";
            $params[] = $supplier_id;
            $types .= 'i';
            
            $update_fields[] = "description = ?";
            $params[] = $description;
            $types .= 's';
            
            $update_fields[] = "barcode = ?";
            $params[] = $barcode;
            $types .= 's';
            
            $update_fields[] = "image = ?";
            $params[] = $image_path;
            $types .= 's';
            
            $update_fields[] = "tipo_gestion = ?";
            $params[] = $tipo_gestion;
            $types .= 's';
            
            $update_fields[] = "cost_price = ?";
            $params[] = $cost_price;
            $types .= 'd';
            
            $update_fields[] = "min_stock = ?";
            $params[] = $min_stock;
            $types .= 'i';
            
            $update_fields[] = "max_stock = ?";
            $params[] = $max_stock;
            $types .= 'i';
            
            $update_fields[] = "unit_measure = ?";
            $params[] = $unit_measure;
            $types .= 's';
            
            $params[] = $product_id;
            $types .= 'i';
            
            $query = "UPDATE products SET " . implode(", ", $update_fields) . " WHERE product_id = ?";
            $stmt = $mysqli->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                error_log('DEBUG: UPDATE ejecutado correctamente. Filas afectadas: ' . $stmt->affected_rows);
                
                // Manejo de bobinas si el tipo de gestión es bobina (solo si se cambió la cantidad)
                if ($tipo_gestion === 'bobina' && $quantity > 0 && $quantity !== $product['quantity']) {
                    $stmt_check = $mysqli->prepare("SELECT COUNT(*) as total FROM bobinas WHERE product_id = ?");
                    $stmt_check->bind_param("i", $product_id);
                    $stmt_check->execute();
                    $result = $stmt_check->get_result();
                    $bobinas_existentes = $result->fetch_assoc()['total'];
                    $stmt_check->close();
                    
                    if ($bobinas_existentes == 0) {
                        $stmt_bobina = $mysqli->prepare("INSERT INTO bobinas (product_id, metros_actuales, identificador) VALUES (?, ?, ?)");
                        $identificador = "Bobina #1";
                        $stmt_bobina->bind_param("ids", $product_id, $quantity, $identificador);
                        $stmt_bobina->execute();
                        $stmt_bobina->close();
                        error_log('DEBUG: Bobina creada automáticamente');
                    }
                }
                
                if ($stmt->affected_rows > 0) {
                    $success = "Producto actualizado correctamente.";
                    error_log('DEBUG: Éxito - Producto actualizado');
                } else {
                    $error = "No se realizaron cambios en el producto.";
                    error_log('DEBUG: No se afectaron filas en el UPDATE');
                }
                // Recargar datos del producto actualizado
                $stmt2 = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
                $stmt2->bind_param("i", $product_id);
                $stmt2->execute();
                $product = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            } else {
                $error = "Error en la base de datos: " . $stmt->error;
                error_log('DEBUG: Error en UPDATE: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            $error = "Por favor, completa todos los campos correctamente.";
            error_log('DEBUG: Validación fallida - product_name: ' . ($product_name ? 'OK' : 'VACÍO') . ', price: ' . $price . ', quantity: ' . $quantity); 
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar producto | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
            min-height: 100vh;
        }
        .form-wrapper {
            max-width: 800px;
            margin: 40px auto 0 auto;
            padding: 0 20px;
        }
        .card-form {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 4px 32px rgba(18,24,102,0.10);
            padding: 36px 32px 32px 32px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .card-form h2 {
            text-align: center;
            margin-bottom: 28px;
            color: #121866;
            font-size: 2.1rem;
            font-weight: 700;
        }
        .form-section {
            margin-bottom: 22px;
            padding: 22px 18px 18px 18px;
            border-radius: 15px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .form-section h6 {
            color: #121866;
            font-weight: 700;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-row-horizontal {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 12px;
        }
        .form-row-horizontal label {
            min-width: 120px;
            margin-bottom: 0;
            font-weight: 600;
            color: #232a7c;
            flex-shrink: 0;
        }
        .form-row-horizontal input,
        .form-row-horizontal select,
        .form-row-horizontal textarea {
            flex: 1 1 0%;
        }
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }
        .form-actions button,
        .form-actions a {
            flex: 1;
        }
        .alert-auto-sku {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #e3f2fd;
            color: #1565c0;
            border: 1.5px solid #90caf9;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        @media (max-width: 700px) {
            .form-wrapper { padding: 0 10px; }
            .card-form { padding: 24px 20px 20px 20px; }
            .form-row-horizontal { flex-direction: column; align-items: stretch; }
            .form-row-horizontal label { min-width: auto; }
        }
    </style>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="form-wrapper">
            <div class="card-form">
                <h2><i class="bi bi-pencil"></i> Editar Producto</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle"></i> <?= $success ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
                    <div class="form-section">
                        <h6><i class="bi bi-info-circle"></i> Información básica</h6>
                        <div class="form-row-horizontal">
                            <label for="product_name">Nombre del producto *</label>
                            <input type="text" class="form-control" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                        </div>
                        <div class="form-row-horizontal">
                            <label for="sku">SKU</label>
                            <input type="text" class="form-control" name="sku" id="sku" value="<?= htmlspecialchars($product['sku']) ?>" maxlength="50">
                        </div>
                        <div class="form-row-horizontal">
                            <label for="barcode">Código de barras</label>
                            <input type="text" class="form-control" name="barcode" id="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>" maxlength="50">
                        </div>
                    </div>

                    <!-- SECCIÓN 2: INVENTARIO Y GESTIÓN -->
                    <div class="form-section">
                        <h6><i class="bi bi-gear"></i> Inventario y gestión</h6>
                        <div class="form-row-horizontal">
                            <label for="unit_measure">Unidad de medida</label>
                            <input type="text" class="form-control" name="unit_measure" id="unit_measure" value="<?= htmlspecialchars($product['unit_measure'] ?? '') ?>" placeholder="Ej: piezas, metros, cajas">
                        </div>
                        <div class="form-row-horizontal">
                            <label for="tipo_gestion">Tipo de gestión</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_normal" value="normal" <?= $tipo_gestion_actual === 'normal' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_normal"><i class="bi bi-box"></i> Normal</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bobina" value="bobina" <?= $tipo_gestion_actual === 'bobina' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_bobina"><i class="bi bi-receipt"></i> Bobina</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bolsa" value="bolsa" <?= $tipo_gestion_actual === 'bolsa' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_bolsa"><i class="bi bi-bag"></i> Bolsa</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_par" value="par" <?= $tipo_gestion_actual === 'par' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tipo_par"><i class="bi bi-123"></i> Par</label>
                                </div>
                            </div>
                        </div>
                        <div class="form-row-horizontal" id="rowCantidadInicial">
                            <label for="quantity">Cantidad actual del stock</label>
                            <input type="number" class="form-control" name="quantity" id="quantity" value="<?= $product['quantity'] ?>" readonly tabindex="-1">
                            <small class="text-muted">El stock solo se puede modificar mediante movimientos de inventario</small>
                        </div>
                        <div class="form-row-horizontal">
                            <label for="min_stock">Stock mínimo</label>
                            <input type="number" class="form-control" name="min_stock" id="min_stock" value="<?= htmlspecialchars($product['min_stock'] ?? '') ?>">
                        </div>
                        <div class="form-row-horizontal">
                            <label for="max_stock">Stock máximo</label>
                            <input type="number" class="form-control" name="max_stock" id="max_stock" value="<?= htmlspecialchars($product['max_stock'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- SECCIÓN 3: CATEGORÍA Y PROVEEDOR -->
                    <div class="form-section">
                        <h6><i class="bi bi-tags"></i> Categoría y proveedor</h6>
                        <div class="form-row-horizontal">
                            <label for="category">Categoría</label>
                            <select class="form-select" name="category" id="category">
                                <option value="">Selecciona una categoría</option>
                                <?php if ($categorias) { $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                                    <option value="<?= $cat['category_id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $cat['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="form-row-horizontal">
                            <label for="new_category">Nueva categoría (opcional)</label>
                            <input type="text" class="form-control" name="new_category" id="new_category" placeholder="Escribe para crear una nueva categoría">
                        </div>
                        <div class="form-row-horizontal">
                            <label for="supplier_id">Proveedor</label>
                            <select class="form-select" name="supplier_id" id="supplier_id">
                                <option value="">Selecciona un proveedor</option>
                                <?php if ($proveedores) { $proveedores->data_seek(0); while ($prov = $proveedores->fetch_assoc()): ?>
                                    <option value="<?= $prov['supplier_id'] ?>" <?= (isset($product['supplier_id']) && $product['supplier_id'] == $prov['supplier_id']) ? 'selected' : '' ?>><?= htmlspecialchars($prov['name']) ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>
                        <div class="form-row-horizontal">
                            <label for="new_supplier">Nuevo proveedor (opcional)</label>
                            <input type="text" class="form-control" name="new_supplier" id="new_supplier" placeholder="Escribe para crear un nuevo proveedor">
                        </div>
                    </div>

                    <!-- SECCIÓN 4: PRECIOS -->
                    <div class="form-section">
                        <h6><i class="bi bi-cash-coin"></i> Precios</h6>
                        <div class="form-row-horizontal">
                            <label for="cost_price">Costo unitario</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" name="cost_price" id="cost_price" value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row-horizontal">
                            <label for="price">Precio de venta *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" name="price" id="price" value="<?= $product['price'] ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 5: IMAGEN Y DESCRIPCIÓN -->
                    <div class="form-section">
                        <h6><i class="bi bi-image"></i> Imagen y descripción</h6>
                        <div class="form-row-horizontal">
                            <label for="image">Imagen</label>
                            <?php if (isset($product['image']) && $product['image']): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="Imagen actual" style="width:48px;height:48px;object-fit:cover;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                    <span class="text-muted ms-2">Imagen actual</span>
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="image" id="image" accept="image/*">
                        </div>
                        <div class="form-row-horizontal">
                            <label for="description">Descripción</label>
                            <textarea class="form-control" name="description" id="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Actualizar producto
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- SECCIÓN DE BOBINAS ASOCIADAS -->
            <?php if ($bobinas && $bobinas->num_rows > 0): ?>
                <div class="card-form">
                    <h6><i class="bi bi-receipt"></i> Bobinas asociadas</h6>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Identificador</th>
                                    <th>Metros disponibles</th>
                                    <th>Fecha ingreso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; while ($b = $bobinas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($b['identificador']) ?: '<span class="text-muted">-</span>' ?></td>
                                        <td><?= number_format($b['metros_actuales'], 2) ?></td>
                                        <td><?= date('d/m/Y', strtotime($b['created_at'])) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <a href="../bobinas/gestionar.php?product_id=<?= $product_id ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-gear"></i> Gestionar bobinas
                        </a>
                        <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle"></i> Agregar bobina
                        </a>
                    </div>
                </div>
            <?php elseif ($tipo_gestion_actual === 'bobina'): ?>
                <div class="card-form">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Producto tipo bobina:</strong> Este producto está configurado para gestionarse por metros en bobinas, pero aún no tiene bobinas registradas.
                    </div>
                    <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar primera bobina
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <!-- Botón flotante para volver al listado -->
    <a href="list.php" class="btn btn-secondary btn-fab-volver" title="Volver al listado">
        <i class="bi bi-arrow-left"></i> Volver al listado
    </a>
    <style>
    .btn-fab-volver {
        position: fixed;
        bottom: 32px;
        right: 32px;
        z-index: 9999;
        border-radius: 50px;
        padding: 14px 24px;
        font-size: 1.1rem;
        box-shadow: 0 4px 16px rgba(18,24,102,0.13);
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0.93;
        transition: opacity 0.18s, box-shadow 0.18s;
    }
    .btn-fab-volver:hover {
        opacity: 1;
        box-shadow: 0 8px 32px rgba(18,24,102,0.18);
    }
    @media (max-width: 700px) {
        .btn-fab-volver {
            right: 12px;
            bottom: 12px;
            padding: 10px 16px;
            font-size: 1rem;
        }
    }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script moderno para confirmar edición con SweetAlert2
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const btnSubmit = form.querySelector('button[type="submit"]');
            
            btnSubmit.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '¿Guardar cambios?',
                    text: '¿Deseas guardar los cambios en este producto?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
            
            // Lógica para manejar cambios en tipo de gestión
            const tipoGestionRadios = document.querySelectorAll('input[name="tipo_gestion"]');
            const cantidadInput = document.getElementById('quantity');
            const cantidadLabel = document.querySelector('label[for="quantity"]');
            const smallText = cantidadInput.nextElementSibling;
            
            function actualizarComportamientoBobina() {
                const checked = document.querySelector('input[name="tipo_gestion"]:checked');
                if (checked && checked.value === 'bobina') {
                    if (cantidadLabel) cantidadLabel.textContent = 'Metros totales en bobinas';
                    if (smallText) smallText.textContent = 'El stock solo se puede modificar mediante movimientos de inventario';
                    cantidadInput.placeholder = 'Ej: 305, 610, etc.';
                } else {
                    if (cantidadLabel) cantidadLabel.textContent = 'Cantidad actual del stock';
                    if (smallText) smallText.textContent = 'El stock solo se puede modificar mediante movimientos de inventario';
                    cantidadInput.placeholder = 'Ej: 10, 50, etc.';
                }
            }
            
            tipoGestionRadios.forEach(radio => {
                radio.addEventListener('change', actualizarComportamientoBobina);
            });
            
            // Ejecutar al cargar
            actualizarComportamientoBobina();
        });
    </script>
</body>
</html>
