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
        $product_name = trim($_POST['product_name']);
        $sku = trim($_POST['sku']);
        $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
        $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : 0.00;
        $quantity = intval($_POST['quantity']);
        
        // Manejar categoría (existente o nueva)
        $category_id = intval($_POST['category']);
        $new_category = isset($_POST['new_category']) ? trim($_POST['new_category']) : '';
        
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
                }
            }
        }

        // Nuevo: tipo de gestión
        $tipo_gestion = isset($_POST['tipo_gestion']) ? $_POST['tipo_gestion'] : 'normal';
        if (!in_array($tipo_gestion, $allowed_tipos)) $tipo_gestion = 'normal';

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
        }

        // 1. Frontend: Cambiar label y comportamiento del campo cuando es bobina
        $metros_bobina = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        
        if ($tipo_gestion === 'bobina') {
            $cantidad_inicial = 1; // Siempre 1 bobina
            if ($metros_bobina <= 0) {
                $error = 'Los metros de la bobina deben ser mayores a 0.';
            }
        } else {
            $cantidad_inicial = $metros_bobina;
            if ($cantidad_inicial <= 0) {
                $error = 'La cantidad actual del stock debe ser mayor a 0.';
            }
        }

        $cost_price = isset($_POST['cost_price']) ? floatval($_POST['cost_price']) : null;
        $min_stock = isset($_POST['min_stock']) ? intval($_POST['min_stock']) : null;
        $max_stock = isset($_POST['max_stock']) ? intval($_POST['max_stock']) : null;

        if ($product_name && $price >= 0 && $quantity >= 0) {
            $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, sku = ?, price = ?, quantity = ?, category_id = ?, supplier_id = ?, description = ?, barcode = ?, image = ?, tipo_gestion = ?, cost_price = ?, min_stock = ?, max_stock = ? WHERE product_id = ?");
            $stmt->bind_param("ssdiissssssddi", $product_name, $sku, $price, $quantity, $category_id, $supplier_id, $description, $barcode, $image_path, $tipo_gestion, $cost_price, $min_stock, $max_stock, $product_id);

            if ($stmt->execute()) {
                // 2. Backend: Manejar bobinas en la edición
                if ($tipo_gestion === 'bobina' && $metros_bobina > 0) {
                    $stmt_check = $mysqli->prepare("SELECT COUNT(*) as total FROM bobinas WHERE product_id = ?");
                    $stmt_check->bind_param("i", $product_id);
                    $stmt_check->execute();
                    $result = $stmt_check->get_result();
                    $bobinas_existentes = $result->fetch_assoc()['total'];
                    $stmt_check->close();
                    
                    if ($bobinas_existentes == 0) {
                        $stmt_bobina = $mysqli->prepare("INSERT INTO bobinas (product_id, metros_actuales, identificador) VALUES (?, ?, ?)");
                        $identificador = "Bobina #1";
                        $stmt_bobina->bind_param("ids", $product_id, $metros_bobina, $identificador);
                        $stmt_bobina->execute();
                        $stmt_bobina->close();
                    }
                }
                $success = "Producto actualizado correctamente.";
                // Refresh product data
                $product['product_name'] = $product_name;
                $product['sku'] = $sku;
                $product['price'] = $price;
                $product['quantity'] = $quantity;
                $product['category_id'] = $category_id;
                $product['supplier_id'] = $supplier_id;
                $product['description'] = $description;
                $product['barcode'] = $barcode;
                $product['image'] = $image_path;
                $product['tipo_gestion'] = $tipo_gestion;
                $product['cost_price'] = $cost_price;
                $product['min_stock'] = $min_stock;
                $product['max_stock'] = $max_stock;
                // Si el tipo de gestión cambió de bobina a otro y hay bobinas asociadas, eliminarlas si el usuario lo confirmó
                if ($tipo_gestion_actual === 'bobina' && $tipo_gestion !== 'bobina') {
                    // Si el usuario confirmó la eliminación
                    if (isset($_POST['confirmar_eliminar_bobinas']) && $_POST['confirmar_eliminar_bobinas'] === '1') {
                        $mysqli->query("DELETE FROM bobinas WHERE product_id = $product_id");
                    }
                }
            } else {
                $error = "Error en la base de datos: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $error = "Por favor, completa todos los campos correctamente.";
        }
    }
?>

<!DOCTYPE html>
    <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Editar producto</title>
            <link rel="stylesheet" href="../assets/css/style.css">
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                .form-container {
                    max-width: 700px;
                    margin: 40px auto;
                    padding: 32px 28px 28px 28px;
                    background: linear-gradient(120deg, #f4f6fb 80%, #e3f2fd 100%);
                    border-radius: 18px;
                    box-shadow: 0 8px 32px rgba(18,24,102,0.13);
                }
                .form-section {
                    margin-bottom: 18px;
                    padding-bottom: 10px;
                    border-bottom: 1px solid #e3e6f0;
                }
                .form-section:last-child {
                    border-bottom: none;
                }
                .form-row {
                    display: flex;
                    gap: 18px;
                    flex-wrap: wrap;
                }
                .form-col {
                    flex: 1 1 220px;
                    min-width: 220px;
                }
                @media (max-width: 700px) {
                    .form-row { flex-direction: column; gap: 0; }
                    .form-col { min-width: 100%; }
                    .form-container { padding: 14px 4px; }
                }
                .toggle-barcode-btn {
                    background: #e3f2fd;
                    color: #1565c0;
                    border: none;
                    border-radius: 7px;
                    padding: 7px 14px;
                    font-size: 0.98rem;
                    font-weight: 600;
                    margin-bottom: 8px;
                    cursor: pointer;
                    transition: background 0.18s;
                }
                .toggle-barcode-btn:hover {
                    background: #1565c0;
                    color: #fff;
                }
                .barcode-section { display: none; }
                .barcode-section.active { display: block; animation: fadeIn 0.3s; }
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                .acciones-form {
                    display: flex;
                    gap: 10px;
                    margin-top: 18px;
                }
                .acciones-form .btn { flex: 1; }
                .success, .error {
                    margin-bottom: 18px;
                }
                @media (max-width: 700px) {
                    .form-container { max-width: 98vw; padding: 18px 6px; }
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
                    margin-bottom: 18px;
                    font-size: 1.05rem;
                    font-weight: 500;
                }
                .alert-auto-sku svg {
                    width: 26px;
                    height: 26px;
                    fill: #1565c0;
                    flex-shrink: 0;
                }
            </style>
        </head>
        <body>
            <div class="form-container">
                <h2><i class="bi bi-pencil-square"></i> Editar producto</h2>
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
                <form action="" method="POST" enctype="multipart/form-data" id="formEditarProducto">
                    <!-- SECCIÓN 1: IDENTIFICACIÓN DEL PRODUCTO -->
                    <div class="form-section">
                        <h6><i class="bi bi-info-circle"></i> Identificación</h6>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="product_name" class="form-label">Nombre</label>
                                <input type="text" class="form-control" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                            </div>
                            <div class="form-col mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" class="form-control" name="sku" id="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
                            </div>
                        </div>
                        <button type="button" class="toggle-barcode-btn" onclick="toggleBarcode()">
                            <i class="bi bi-upc-scan"></i> <?= empty($product['barcode']) ? 'Agregar' : 'Editar' ?> código de barras
                        </button>
                        <div class="barcode-section<?= !empty($product['barcode']) ? ' active' : '' ?>" id="barcodeSection">
                            <label for="barcode" class="form-label">Código de barras (opcional)</label>
                            <input type="text" class="form-control" name="barcode" id="barcode" value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                        </div>
                    </div>
                    <!-- SECCIÓN 2: INVENTARIO Y GESTIÓN -->
                    <div class="form-section">
                        <h6><i class="bi bi-gear"></i> Inventario y gestión</h6>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="quantity" class="form-label">Cantidad actual del stock</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" value="<?= htmlspecialchars($product['quantity']) ?>" readonly tabindex="-1">
                                <small class="form-text text-muted">El stock solo se puede modificar mediante movimientos de inventario.</small>
                            </div>
                            <div class="form-col mb-3">
                                <label for="min_stock" class="form-label">Stock mínimo</label>
                                <input type="number" class="form-control" name="min_stock" id="min_stock" value="<?= htmlspecialchars($product['min_stock'] ?? '') ?>">
                            </div>
                            <div class="form-col mb-3">
                                <label for="max_stock" class="form-label">Stock máximo</label>
                                <input type="number" class="form-control" name="max_stock" id="max_stock" value="<?= htmlspecialchars($product['max_stock'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="tipo_gestion" class="form-label">Tipo de gestión</label>
                                <select class="form-select" name="tipo_gestion" id="tipo_gestion" required>
                                    <option value="normal" <?= $product['tipo_gestion'] === 'normal' ? 'selected' : '' ?>>Normal</option>
                                    <option value="bobina" <?= $product['tipo_gestion'] === 'bobina' ? 'selected' : '' ?>>Bobina</option>
                                    <option value="bolsa" <?= $product['tipo_gestion'] === 'bolsa' ? 'selected' : '' ?>>Bolsa</option>
                                    <option value="par" <?= $product['tipo_gestion'] === 'par' ? 'selected' : '' ?>>Par</option>
                                    <option value="kit" <?= $product['tipo_gestion'] === 'kit' ? 'selected' : '' ?>>Kit</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- SECCIÓN 3: PRECIOS -->
                    <div class="form-section">
                        <h6><i class="bi bi-cash-coin"></i> Precios</h6>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="cost_price" class="form-label">Costo unitario</label>
                                <input type="number" step="0.01" class="form-control" name="cost_price" id="cost_price" value="<?= htmlspecialchars($product['cost_price'] ?? '') ?>">
                            </div>
                            <div class="form-col mb-3">
                                <label for="price" class="form-label">Precio de venta</label>
                                <input type="number" step="0.01" class="form-control" name="price" id="price" value="<?= $product['price'] ?>" required>
                            </div>
                        </div>
                    </div>
                    <!-- SECCIÓN 4: CATEGORÍA Y PROVEEDOR -->
                    <div class="form-section">
                        <h6><i class="bi bi-tags"></i> Categoría y proveedor</h6>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="category" class="form-label">Categoría</label>
                                <select class="form-select" name="category" id="category" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php if ($categorias) { $categorias->data_seek(0); while ($cat = $categorias->fetch_assoc()): ?>
                                        <option value="<?= $cat['category_id'] ?>" <?= (isset($product['category_id']) && $product['category_id'] == $cat['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                            <div class="form-col mb-3">
                                <label for="supplier_id" class="form-label">Proveedor</label>
                                <select class="form-select" name="supplier_id" id="supplier_id" required>
                                    <option value="">Selecciona un proveedor</option>
                                    <?php if ($proveedores) { $proveedores->data_seek(0); while ($prov = $proveedores->fetch_assoc()): ?>
                                        <option value="<?= $prov['supplier_id'] ?>" <?= (isset($product['supplier_id']) && $product['supplier_id'] == $prov['supplier_id']) ? 'selected' : '' ?>><?= htmlspecialchars($prov['name']) ?></option>
                                    <?php endwhile; } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- SECCIÓN 5: DESCRIPCIÓN E IMAGEN -->
                    <div class="form-section">
                        <h6><i class="bi bi-image"></i> Imagen y descripción</h6>
                        <div class="form-row">
                            <div class="form-col mb-3">
                                <label for="description" class="form-label">Descripción</label>
                                <textarea class="form-control" name="description" id="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                            </div>
                            <div class="form-col mb-3">
                                <label for="image" class="form-label">Imagen</label>
                                <?php if (isset($product['image']) && $product['image']): ?>
                                    <div class="mb-2">
                                        <img src="<?= htmlspecialchars($product['image']) ?>" alt="Imagen actual" style="width:48px;height:48px;object-fit:cover;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.08);">
                                        <span class="text-muted ms-2">Imagen actual</span>
                                    </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="acciones-form">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Actualizar producto
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al listado
                        </a>
                    </div>
                </form>
            </div>
            <?php if ($bobinas && $bobinas->num_rows > 0): ?>
                <div class="mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4><i class="bi bi-receipt"></i> Bobinas asociadas</h4>
                        <div class="d-flex gap-2">
                            <a href="../bobinas/gestionar.php?product_id=<?= $product_id ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-gear"></i> Gestionar bobinas
                            </a>
                            <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Agregar bobina
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Identificador</th>
                                    <th>Metros iniciales</th>
                                    <th>Metros actuales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i=1; while ($b = $bobinas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $i++ ?></td>
                                        <td><?= htmlspecialchars($b['identificador']) ?: '<span class="text-muted">-</span>' ?></td>
                                        <td><?= number_format($b['metros_iniciales'], 2) ?></td>
                                        <td><?= number_format($b['metros_actuales'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($tipo_gestion_actual === 'bobina'): ?>
                <div class="mt-4">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        <strong>Producto tipo bobina:</strong> Este producto está configurado para gestionarse por metros en bobinas, pero aún no tiene bobinas registradas.
                    </div>
                    <a href="../bobinas/add.php?product_id=<?= $product_id ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Registrar primera bobina
                    </a>
                </div>
            <?php endif; ?>
            <script src="../assets/js/script.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script>
            // Mostrar/ocultar campos de bobina según tipo_gestion
            const tipoGestionSelect = document.getElementById('tipo_gestion');
            const bobinaSection = document.getElementById('bobinaSection');
            const advertenciaBobinas = document.getElementById('advertenciaBobinas');
            let bobinasAsociadas = <?= isset($bobinas) && $bobinas->num_rows > 0 ? 'true' : 'false' ?>;
            let tipoGestionOriginal = '<?= $tipo_gestion_actual ?>';
            tipoGestionSelect.addEventListener('change', function() {
                if (this.value === 'bobina') {
                    bobinaSection.style.display = '';
                    advertenciaBobinas.style.display = 'none';
                } else {
                    bobinaSection.style.display = 'none';
                    document.getElementById('metros_iniciales').value = '';
                    document.getElementById('identificador').value = '';
                    // Si el producto era bobina y hay bobinas asociadas, advertir
                    if (tipoGestionOriginal === 'bobina' && bobinasAsociadas) {
                        advertenciaBobinas.innerHTML = 'Este producto tiene bobinas asociadas. Si cambias el tipo de gestión perderás todas las bobinas. <br><label><input type="checkbox" name="confirmar_eliminar_bobinas" value="1" required> Confirmo que deseo eliminar todas las bobinas asociadas.</label>';
                        advertenciaBobinas.style.display = 'block';
                    } else {
                        advertenciaBobinas.style.display = 'none';
                    }
                }
            });
            // Al cargar, mostrar si corresponde
            if (tipoGestionSelect.value === 'bobina') {
                bobinaSection.style.display = '';
            }
            </script>
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
                  const select = document.getElementById('supplier');
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
            <script>
            // 1. Frontend: Cambiar label y comportamiento del campo cuando es bobina
            document.addEventListener('DOMContentLoaded', function() {
                const tipoGestionRadios = document.querySelectorAll('input[name="tipo_gestion"]');
                const bobinaSection = document.getElementById('bobinaSection');
                const cantidadInput = document.getElementById('quantity');
                const rowCantidadInicial = document.getElementById('rowCantidadInicial');
                const cantidadLabel = document.querySelector('label[for="quantity"]');

                function actualizarBobina() {
                    const checked = document.querySelector('input[name="tipo_gestion"]:checked');
                    if (checked && checked.value === 'bobina') {
                        if (bobinaSection) bobinaSection.style.display = '';
                        if (cantidadInput) {
                            cantidadInput.disabled = false;
                            cantidadInput.required = true;
                            cantidadInput.placeholder = 'Ej: 305, 610, etc.';
                        }
                        if (cantidadLabel) cantidadLabel.textContent = 'Metros de la bobina inicial *';
                        if (rowCantidadInicial) rowCantidadInicial.style.display = '';
                    } else {
                        if (bobinaSection) bobinaSection.style.display = 'none';
                        if (cantidadInput) {
                            cantidadInput.disabled = false;
                            cantidadInput.required = true;
                            cantidadInput.placeholder = 'Ej: 10, 50, etc.';
                        }
                        if (cantidadLabel) cantidadLabel.textContent = 'Cantidad actual del stock *';
                        if (rowCantidadInicial) rowCantidadInicial.style.display = '';
                    }
                }

                tipoGestionRadios.forEach(radio => {
                    radio.addEventListener('change', actualizarBobina);
                });
                actualizarBobina();
            });
            </script>
            <script>
            function toggleBarcode() {
                const section = document.getElementById('barcodeSection');
                section.classList.toggle('active');
                if (section.classList.contains('active')) {
                    document.getElementById('barcode').focus();
                }
            }
            </script>
        </body>
    </html>
