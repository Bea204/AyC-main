<?php
    require_once '../auth/middleware.php';
    require_once '../connection.php';

    $success = $error = "";
    $sku_auto_generado = false;

    // Obtener productos para el select
    $products = $mysqli->query("SELECT product_id, product_name FROM products ORDER BY product_name");

    // Obtener categorías existentes para el select
    $categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
    
    // Obtener proveedores existentes para el select
    $proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

    // ID de la categoría 'Cables y Conectores'
    $bobina_category_id = 13;

    // Procesar formulario
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = intval($_POST['quantity']);
        
        // Nuevo: tipo de gestión
        $tipo_gestion = isset($_POST['tipo_gestion']) ? $_POST['tipo_gestion'] : 'normal';
        $allowed_tipos = ['normal','bobina','bolsa','par'];
        if (!in_array($tipo_gestion, $allowed_tipos)) $tipo_gestion = 'normal';
        
        // Manejar categoría (existente o nueva)
        $category_id = isset($_POST['category']) && $_POST['category'] !== '' ? intval($_POST['category']) : null;
        $new_category = trim($_POST['new_category'] ?? '');
        if (!empty($new_category)) {
            // Insertar nueva categoría
            $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $new_category);
            if ($stmt->execute()) {
                $category_id = $stmt->insert_id;
            }
            $stmt->close();
        }
        
        // Manejar proveedor (existente o nuevo)
        $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null;
        $new_supplier = trim($_POST['new_supplier'] ?? '');
        if (!empty($new_supplier)) {
            // Insertar nuevo proveedor
            $stmt = $mysqli->prepare("INSERT INTO suppliers (name) VALUES (?)");
            $stmt->bind_param("s", $new_supplier);
            if ($stmt->execute()) {
                $supplier_id = $stmt->insert_id;
            }
            $stmt->close();
        }
        
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        if ($description === '') $description = null;

        // Procesar código de barras
        $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
        if ($barcode === '') $barcode = null;

        // Subida de imagen
        $image_path = null;
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
                }
            }
        }

        // Si el SKU está vacío, generar uno automáticamente
        if ($sku === '') {
            // Buscar el último SKU AUTO generado
            $result = $mysqli->query("SELECT sku FROM products WHERE sku LIKE 'AUTO-%' ORDER BY product_id DESC LIMIT 1");
            $last_auto = $result && $result->num_rows > 0 ? $result->fetch_assoc()['sku'] : null;
            if ($last_auto && preg_match('/AUTO-(\\d+)/', $last_auto, $m)) {
                $next_num = intval($m[1]) + 1;
            } else {
                $next_num = 1;
            }
            $sku = 'AUTO-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
            $sku_auto_generado = true;
        }

        $cost_price = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null;
        $min_stock = isset($_POST['min_stock']) && $_POST['min_stock'] !== '' ? intval($_POST['min_stock']) : null;
        $max_stock = isset($_POST['max_stock']) && $_POST['max_stock'] !== '' ? intval($_POST['max_stock']) : null;
        // Si el precio está vacío y hay cost_price, sugerir price = cost_price * 1.3
        if ((empty($_POST['price']) || $_POST['price'] == 0) && $cost_price !== null) {
            $price = round($cost_price * 1.3, 2);
        }

        // Validación básica
        if ($product_name && $price >= 0 && $quantity >= 0 && $quantity > 0) {
            $stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, quantity, category_id, supplier_id, description, barcode, image, tipo_gestion, cost_price, min_stock, max_stock) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiisssssddi", $product_name, $sku, $price, $quantity, $category_id, $supplier_id, $description, $barcode, $image_path, $tipo_gestion, $cost_price, $min_stock, $max_stock);
            if ($stmt->execute()) {
                $new_product_id = $stmt->insert_id;
                // Si el tipo de gestión es bobina, mostrar opción de registrar bobinas
                if ($tipo_gestion === 'bobina') {
                    $success = "Producto tipo bobina agregado correctamente. Ahora puedes registrar las bobinas individuales.";
                } else {
                    $success = "Producto agregado correctamente.";
                }
            } else {
                $error = "Error en la base de datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            if (!$product_name) {
                $error = "El nombre del producto es obligatorio.";
            } elseif ($price < 0) {
                $error = "El precio no puede ser negativo.";
            } elseif ($quantity <= 0) {
                $error = "La cantidad inicial debe ser mayor a 0.";
            } else {
                $error = "Por favor, completa todos los campos correctamente.";
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar producto | Gestor de inventarios Alarmas y Cámaras de seguridad del sureste</title>
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
        .card-form::before {
            display: none;
        }
        .card-form h2 {
            text-align: center;
            margin-bottom: 28px;
            color: #121866;
            font-size: 2.1rem;
            font-weight: 700;
            position: relative;
        }
        .card-form h2 i {
            color: #232a7c;
            margin-right: 10px;
            background: none;
            -webkit-background-clip: unset;
            -webkit-text-fill-color: unset;
        }
        .form-section {
            margin-bottom: 22px;
            padding: 22px 18px 18px 18px;
            border-radius: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        .form-section:hover {
            transform: none;
            box-shadow: none;
        }
        .form-section h6 {
            color: #232a7c;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section h6 i {
            color: #667eea;
            font-size: 1.2rem;
        }
        .form-label {
            font-weight: 600;
            color: #232a7c;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .input-group-text {
            background: #f4f6fb;
            border: none;
            color: #232a7c;
            font-size: 1.2rem;
            border-radius: 8px 0 0 8px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1.5px solid #cfd8dc;
            background: #f7f9fc;
            font-size: 1rem;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
            background: #fff;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
            padding-top: 18px;
            border-top: 1.5px solid #f0f0f0;
        }
        .form-actions button, .form-actions a {
            flex: 1;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary {
            background: #232a7c;
            border: none;
        }
        .btn-primary:hover {
            background: #121866;
            transform: none;
            box-shadow: none;
        }
        .btn-secondary {
            background: #6c757d;
            border: none;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
            color: white;
        }
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        .alert-info {
            background: #e3f2fd;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }
        .alert-success {
            background: #e8f5e8;
            color: #2e7d32;
            border-left: 4px solid #4caf50;
        }
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #f44336;
        }
        .form-check {
            border: 1.5px solid #e3e6f0;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            background: #f7f9fc;
            cursor: pointer;
        }
        .form-check:hover {
            border-color: #121866;
            background: #e3e6fa;
        }
        .form-check-input:checked + .form-check-label {
            color: #121866;
            font-weight: 600;
        }
        .form-check-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }
        .form-check-label i {
            font-size: 1.3rem;
            color: #667eea;
        }
        .form-check-input:checked ~ .form-check-label i {
            color: #667eea;
        }
        .form-check-inline {
            margin-right: 15px;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 0.9rem;
        }
        .input-group .form-control {
            border-radius: 0 8px 8px 0;
        }
        .btn-close {
            opacity: 0.7;
        }
        .btn-close:hover {
            opacity: 1;
        }
        .d-flex.gap-2 .btn {
            padding: 8px 16px;
            font-size: 0.9rem;
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            .form-wrapper { 
                max-width: 95vw; 
                padding: 0 10px; 
            }
            .card-form { 
                padding: 18px 6px; 
            }
            .form-section {
                padding: 12px;
            }
            .form-actions {
                flex-direction: column;
            }
            .form-check-inline {
                display: block;
                margin-bottom: 10px;
            }
        }
        .floating-label {
            position: relative;
            margin-bottom: 20px;
        }
        .floating-label input,
        .floating-label textarea,
        .floating-label select {
            width: 100%;
            padding: 15px;
            border: 1.5px solid #cfd8dc;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f7f9fc;
        }
        .floating-label input:focus,
        .floating-label textarea:focus,
        .floating-label select:focus {
            border-color: #121866;
            box-shadow: 0 0 0 2px #e3e6fa;
            outline: none;
            background: #fff;
        }
        .floating-label label {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #6c757d;
            transition: all 0.3s ease;
            pointer-events: none;
            font-size: 1rem;
        }
        .floating-label input:focus + label,
        .floating-label input:not(:placeholder-shown) + label,
        .floating-label textarea:focus + label,
        .floating-label textarea:not(:placeholder-shown) + label,
        .floating-label select:focus + label,
        .floating-label select:not([value=""]) + label {
            top: -10px;
            left: 10px;
            font-size: 0.8rem;
            color: #667eea;
            background: #fff;
            padding: 0 5px;
        }
    </style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="form-wrapper">
            <div class="card-form">
                <h2><i class="bi bi-plus-circle"></i> Agregar producto</h2>
                
                <?php if ($sku_auto_generado): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="bi bi-info-circle"></i>
                        El SKU se generó automáticamente: <b><?= htmlspecialchars($sku) ?></b>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div id="alertSkuRealtime" class="alert alert-info" style="display:none;margin-bottom:25px;">
                    <i class="bi bi-info-circle"></i>
                    Si dejas este campo vacío, el sistema generará un SKU automáticamente al guardar.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex flex-column align-items-start gap-2" role="alert">
                        <div>
                            <i class="bi bi-check-circle"></i>
                            <?= $success ?>
                        </div>
                        <?php if (strpos($success, 'bobina') !== false): ?>
                            <div class="d-flex gap-2 mt-2">
                                <a href="../bobinas/add.php?product_id=<?= $new_product_id ?? '' ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Registrar bobinas
                                </a>
                                <a href="list.php" class="btn btn-success btn-sm">
                                    <i class="bi bi-list"></i> Ver todos los productos
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="d-flex gap-2 mt-2">
                                <a href="add.php" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Agregar otro producto
                                </a>
                                <a href="list.php" class="btn btn-success btn-sm">
                                    <i class="bi bi-list"></i> Ver todos los productos
                                </a>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form action="" method="POST" id="formAgregarProducto" enctype="multipart/form-data">
                    <!-- SECCIÓN 1: IDENTIFICACIÓN DEL PRODUCTO -->
                    <div class="form-section">
                        <h6><i class="bi bi-box"></i> Identificación del producto</h6>
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="floating-label">
                                    <input type="text" class="form-control" name="product_name" id="product_name" required placeholder=" ">
                                    <label for="product_name">Nombre del producto *</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="floating-label">
                                    <input type="text" class="form-control" name="sku" id="sku" placeholder=" ">
                                    <label for="sku">SKU</label>
                                    <small class="text-muted">Se genera automáticamente si está vacío</small>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label">Categoría</label>
                                <div class="input-group mb-3">
                                    <select class="form-select" name="category" id="category">
                                        <option value="">Selecciona una categoría</option>
                                        <?php if ($categorias) { while ($cat = $categorias->fetch_assoc()): ?>
                                            <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                        <?php endwhile; } ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria" title="Nueva categoría">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Selector de proveedor con botón + alineado -->
                            <div class="col-md-6">
                                <label for="supplier_id" class="form-label">Proveedor</label>
                                <div class="input-group mb-3">
                                    <select class="form-select" name="supplier_id" id="supplier_id">
                                        <option value="">Selecciona un proveedor</option>
                                        <?php if ($proveedores) { while ($prov = $proveedores->fetch_assoc()): ?>
                                            <option value="<?= $prov['supplier_id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                                        <?php endwhile; } ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoProveedor" data-bs-toggle="tooltip" title="Nuevo proveedor">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="toggleBarcode" onclick="toggleBarcodeInput()">
                                <label class="form-check-label" for="toggleBarcode">Agregar código de barras</label>
                            </div>
                            <div class="mb-3" id="barcodeField" style="display:none;">
                                <label for="barcode" class="form-label">Código de barras</label>
                                <input type="text" class="form-control" name="barcode" id="barcode" maxlength="50" autocomplete="off">
                                <small class="text-muted">Opcional. Escanea o ingresa el código de barras si aplica.</small>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: INVENTARIO Y GESTIÓN -->
                    <div class="form-section">
                        <h6><i class="bi bi-gear"></i> Inventario y gestión</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="tipo_gestion" class="form-label">Tipo de gestión</label>
                                <div class="d-flex flex-wrap gap-3">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_normal" value="normal" checked>
                                        <label class="form-check-label" for="tipo_normal"><i class="bi bi-box"></i> Normal (por unidades)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bobina" value="bobina">
                                        <label class="form-check-label" for="tipo_bobina"><i class="bi bi-receipt"></i> Bobina (por metros)</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_bolsa" value="bolsa">
                                        <label class="form-check-label" for="tipo_bolsa"><i class="bi bi-bag"></i> Bolsa</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tipo_gestion" id="tipo_par" value="par">
                                        <label class="form-check-label" for="tipo_par"><i class="bi bi-2-circle"></i> Par</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="quantity" class="form-label">Cantidad inicial *</label>
                                <input type="number" class="form-control" name="quantity" id="quantity" required>
                            </div>
                            <div class="col-md-4">
                                <label for="min_stock" class="form-label">Stock mínimo</label>
                                <input type="number" class="form-control" name="min_stock" id="min_stock">
                            </div>
                            <div class="col-md-4">
                                <label for="max_stock" class="form-label">Stock máximo</label>
                                <input type="number" class="form-control" name="max_stock" id="max_stock">
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 3: PRECIOS -->
                    <div class="form-section">
                        <h6><i class="bi bi-currency-dollar"></i> Precios</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="cost_price" class="form-label">Costo unitario de compra/fabricación *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="cost_price" id="cost_price" required>
                                    <span class="input-group-text" tabindex="0" data-bs-toggle="tooltip" title="Costo real de adquisición o fabricación del producto. El sistema sugerirá el precio de venta automáticamente como un 30% más, pero puedes editarlo."><i class="bi bi-question-circle-fill"></i></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label">Precio de venta *</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                                    <span class="input-group-text" tabindex="0" data-bs-toggle="tooltip" title="Precio sugerido: costo + 30%. Puedes modificarlo si deseas otro margen de ganancia."><i class="bi bi-question-circle-fill"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 4: IMAGEN Y DESCRIPCIÓN -->
                    <div class="form-section">
                        <h6><i class="bi bi-image"></i> Imagen y descripción</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="image" class="form-label">Imagen del producto</label>
                                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                                <small class="text-muted">Opcional - Formatos: JPG, PNG, GIF</small>
                            </div>
                            <div class="col-md-6">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SECCIÓN 5: CONFIGURACIÓN DE BOBINA (solo si es bobina) -->
                    <div class="form-section" id="bobinaSection" style="display:none;">
                        <h6><i class="bi bi-receipt"></i> Configuración de Bobina</h6>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            <strong>Producto tipo bobina:</strong> Después de crear el producto, podrás registrar las bobinas individuales con sus metros.
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="floating-label">
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="metros_iniciales" id="metros_iniciales" placeholder=" ">
                                    <label for="metros_iniciales">Metros sugeridos por bobina</label>
                                    <small class="text-muted">Solo para referencia</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="floating-label">
                                    <input type="text" class="form-control" name="identificador" id="identificador" placeholder=" ">
                                    <label for="identificador">Formato de identificador</label>
                                    <small class="text-muted">Solo para referencia</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Agregar producto
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Alerta visual en tiempo real para el SKU
        const skuInput = document.getElementById('sku');
        const alertSku = document.getElementById('alertSkuRealtime');
        function checkSkuAlert() {
            if (skuInput) { // Proteger la variable
                if (skuInput.value.trim() === '') {
                    if (alertSku) alertSku.style.display = 'block';
                } else {
                    if (alertSku) alertSku.style.display = 'none';
                }
            }
        }
        if (skuInput) { // Proteger la variable
            skuInput.addEventListener('input', checkSkuAlert);
            checkSkuAlert(); // Mostrar alerta al cargar si está vacío
        }

        // Mostrar/ocultar campo de código de barras
        function toggleBarcodeInput() {
            const cb = document.getElementById('toggleBarcode');
            const field = document.getElementById('barcodeField');
            if (cb && field) { // Proteger las variables
                if (cb.checked) {
                    field.style.display = 'block';
                } else {
                    field.style.display = 'none';
                    document.getElementById('barcode').value = '';
                }
            }
        }
        // Si el usuario ya había ingresado un código de barras, mostrar el campo al cargar
        window.addEventListener('DOMContentLoaded', function() {
            const barcode = document.getElementById('barcode');
            if (barcode && barcode.value) {
                const toggleBarcode = document.getElementById('toggleBarcode');
                if (toggleBarcode) toggleBarcode.checked = true;
                const barcodeField = document.getElementById('barcodeField');
                if (barcodeField) barcodeField.style.display = 'block';
            }
        });

        // Manejar campos de nueva categoría y proveedor protegida
        const categorySelect = document.getElementById('category');
        const newCategoryInput = document.getElementById('new_category');
        if (categorySelect && newCategoryInput) { // Proteger las variables
            // Cuando se selecciona una categoría existente, limpiar el campo de nueva categoría
            categorySelect.addEventListener('change', function() {
                if (this.value !== '') {
                    if (newCategoryInput) newCategoryInput.value = '';
                    if (newCategoryInput) newCategoryInput.disabled = true;
                } else {
                    if (newCategoryInput) newCategoryInput.disabled = false;
                }
            });

            // Cuando se escribe en nueva categoría, limpiar el select
            newCategoryInput.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    if (categorySelect) categorySelect.value = '';
                }
            });
        }
        const supplierSelect = document.getElementById('supplier_id');
        const newSupplierInput = document.getElementById('new_supplier');
        if (supplierSelect && newSupplierInput) { // Proteger las variables
            // Manejar proveedores existentes y nuevos
            // Si el select de proveedores tiene un valor, significa que es un proveedor existente
            // Si el input de nuevo proveedor tiene un valor, significa que es un nuevo proveedor
            // Si el select está vacío, significa que el usuario quiere agregar un nuevo proveedor
            // Si el input de nuevo proveedor está vacío, significa que el usuario quiere agregar un nuevo proveedor

            // Al cargar, verificar si el proveedor seleccionado es un nuevo proveedor
            const selectedSupplierOption = supplierSelect.options[supplierSelect.selectedIndex];
            if (selectedSupplierOption && selectedSupplierOption.value === '') {
                if (newSupplierInput) newSupplierInput.value = ''; // Limpiar el input de nuevo proveedor si ya hay un proveedor seleccionado
                if (newSupplierInput) newSupplierInput.disabled = false; // Habilitar el input de nuevo proveedor
            } else {
                if (newSupplierInput) newSupplierInput.value = ''; // Limpiar el input de nuevo proveedor si ya hay un proveedor seleccionado
                if (newSupplierInput) newSupplierInput.disabled = true; // Deshabilitar el input de nuevo proveedor
            }
        }

        // --- Lógica robusta para tipo de gestión bobina ---
        document.addEventListener('DOMContentLoaded', function() {
            const tipoGestionRadios = document.querySelectorAll('input[name="tipo_gestion"]');
            const bobinaSection = document.getElementById('bobinaSection');
            const cantidadInput = document.getElementById('quantity');

            function actualizarBobina() {
                const checked = document.querySelector('input[name="tipo_gestion"]:checked');
                if (checked && checked.value === 'bobina') {
                    if (bobinaSection) bobinaSection.style.display = '';
                    if (cantidadInput) cantidadInput.disabled = true;
                } else {
                    if (bobinaSection) bobinaSection.style.display = 'none';
                    if (cantidadInput) cantidadInput.disabled = false;
                }
            }

            tipoGestionRadios.forEach(radio => {
                radio.addEventListener('change', actualizarBobina);
            });
            actualizarBobina();
        });
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    var costInput = document.getElementById('cost_price');
    var priceInput = document.getElementById('price');
    var userEdited = false;

    // Detecta si el usuario edita manualmente el precio
    priceInput.addEventListener('input', function() {
        userEdited = true;
        if (!this.value || parseFloat(this.value) === 0) {
            userEdited = false; // Si borra el precio, vuelve a sugerir
        }
    });

    costInput.addEventListener('input', function() {
        var costo = parseFloat(this.value);
        if (!isNaN(costo) && costo > 0) {
            var sugerido = Math.round(costo * 1.3 * 100) / 100;
            if (!userEdited || !priceInput.value || parseFloat(priceInput.value) === 0) {
                priceInput.value = sugerido;
            }
        }
    });
});
</script>

<!-- Modal Alta Rápida Proveedor (compacto) -->
<div class="modal fade" id="modalNuevoProveedor" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 370px;">
    <div class="modal-content">
      <form id="formNuevoProveedorRapido">
        <div class="modal-header py-2">
          <h6 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo Proveedor</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pb-2 pt-3">
          <div class="mb-2">
            <label class="form-label mb-1">Nombre del proveedor <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" name="name" required autofocus placeholder="Ej: Syscom, PCH, etc.">
          </div>
          <div class="accordion accordion-flush mb-2" id="opcionalesProveedor">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOpcionales">
                <button class="accordion-button collapsed py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOpcionales" aria-expanded="false" aria-controls="collapseOpcionales" style="font-size:0.98rem;">
                  Más datos opcionales
                </button>
              </h2>
              <div id="collapseOpcionales" class="accordion-collapse collapse" aria-labelledby="headingOpcionales" data-bs-parent="#opcionalesProveedor">
                <div class="accordion-body py-2 px-2">
                  <div class="mb-2">
                    <input type="text" class="form-control form-control-sm mb-1" name="contact_name" placeholder="Nombre de contacto">
                    <input type="text" class="form-control form-control-sm mb-1" name="phone" placeholder="Teléfono">
                    <input type="email" class="form-control form-control-sm mb-1" name="email" placeholder="Email">
                    <textarea class="form-control form-control-sm" name="address" rows="1" placeholder="Dirección"></textarea>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div id="nuevoProveedorMsg" class="mb-1"></div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Alta rápida de proveedor vía AJAX
const formNuevoProveedor = document.getElementById('formNuevoProveedorRapido');
formNuevoProveedor.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(formNuevoProveedor);
  fetch('../proveedores/add.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    const msg = document.getElementById('nuevoProveedorMsg');
    if (data.success) {
      msg.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + data.success + '</div>';
      // Agregar al select y seleccionar
      const select = document.getElementById('supplier_id');
      const option = document.createElement('option');
      option.value = data.id;
      option.textContent = data.name;
      select.appendChild(option);
      select.value = data.id;
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevoProveedor'));
        modal.hide();
        msg.innerHTML = '';
        formNuevoProveedor.reset();
      }, 900);
    } else {
      msg.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.error || 'Error inesperado') + '</div>';
    }
  })
  .catch(() => {
    document.getElementById('nuevoProveedorMsg').innerHTML = '<div class="alert alert-danger">Error de red</div>';
  });
});
</script>

<!-- Modal Alta Rápida Categoría (compacto) -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 340px;">
    <div class="modal-content">
      <form id="formNuevaCategoriaRapida">
        <div class="modal-header py-2">
          <h6 class="modal-title"><i class="bi bi-tags"></i> Nueva Categoría</h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body pb-2 pt-3">
          <div class="mb-2">
            <label class="form-label mb-1">Nombre de la categoría <span class="text-danger">*</span></label>
            <input type="text" class="form-control form-control-sm" name="name" required autofocus placeholder="Ej: Cables, Herramientas, etc.">
          </div>
          <div id="nuevaCategoriaMsg" class="mb-1"></div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle"></i> Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
// Alta rápida de categoría vía AJAX
const formNuevaCategoria = document.getElementById('formNuevaCategoriaRapida');
formNuevaCategoria.addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(formNuevaCategoria);
  fetch('../categories/add.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    const msg = document.getElementById('nuevaCategoriaMsg');
    if (data.success) {
      msg.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + data.success + '</div>';
      // Agregar al select y seleccionar
      const select = document.getElementById('category');
      const option = document.createElement('option');
      option.value = data.id;
      option.textContent = data.name;
      select.appendChild(option);
      select.value = data.id;
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalNuevaCategoria'));
        modal.hide();
        msg.innerHTML = '';
        formNuevaCategoria.reset();
      }, 900);
    } else {
      msg.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + (data.error || 'Error inesperado') + '</div>';
    }
  })
  .catch(() => {
    document.getElementById('nuevaCategoriaMsg').innerHTML = '<div class="alert alert-danger">Error de red</div>';
  });
});
</script>
</body>
</html>
