<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

$success = $error = '';

// Obtener el producto
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$product_id = intval($_GET['id']);
$stmt = $mysqli->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: list.php');
    exit;
}

// Obtener categorías y proveedores
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('DEBUG: Formulario POST recibido en edit-simple.php');
    
    $product_name = trim($_POST['product_name']);
    $sku = trim($_POST['sku']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category']);
    $supplier_id = intval($_POST['supplier_id']);
    $description = trim($_POST['description']);
    $tipo_gestion = $_POST['tipo_gestion'];
    
    error_log('DEBUG: Datos - product_name: ' . $product_name . ', price: ' . $price . ', quantity: ' . $quantity);
    
    if ($product_name && $price >= 0 && $quantity >= 0) {
        $stmt = $mysqli->prepare("UPDATE products SET product_name = ?, sku = ?, price = ?, quantity = ?, category_id = ?, supplier_id = ?, description = ?, tipo_gestion = ? WHERE product_id = ?");
        $stmt->bind_param("ssdiissi", $product_name, $sku, $price, $quantity, $category_id, $supplier_id, $description, $tipo_gestion, $product_id);
        
        if ($stmt->execute()) {
            error_log('DEBUG: UPDATE exitoso - Filas afectadas: ' . $stmt->affected_rows);
            $success = "Producto actualizado correctamente.";
            
            // Recargar datos
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
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Producto - Versión Simple</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <main class="main-content">
        <div class="container">
            <h2><i class="bi bi-pencil"></i> Editar Producto</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?= $success ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-container">
                <div class="mb-3">
                    <label for="product_name" class="form-label">Nombre del producto *</label>
                    <input type="text" class="form-control" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="sku" class="form-label">SKU</label>
                    <input type="text" class="form-control" name="sku" id="sku" value="<?= htmlspecialchars($product['sku']) ?>">
                </div>
                
                <div class="mb-3">
                    <label for="price" class="form-label">Precio *</label>
                    <input type="number" step="0.01" class="form-control" name="price" id="price" value="<?= $product['price'] ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Cantidad *</label>
                    <input type="number" class="form-control" name="quantity" id="quantity" value="<?= $product['quantity'] ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-control" name="category" id="category">
                        <option value="">Seleccionar categoría</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= $cat['category_id'] ?>" <?= $product['category_id'] == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Proveedor</label>
                    <select class="form-control" name="supplier_id" id="supplier_id">
                        <option value="">Seleccionar proveedor</option>
                        <?php while ($sup = $proveedores->fetch_assoc()): ?>
                            <option value="<?= $sup['supplier_id'] ?>" <?= $product['supplier_id'] == $sup['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sup['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="tipo_gestion" class="form-label">Tipo de gestión</label>
                    <select class="form-control" name="tipo_gestion" id="tipo_gestion">
                        <option value="normal" <?= $product['tipo_gestion'] == 'normal' ? 'selected' : '' ?>>Normal</option>
                        <option value="bobina" <?= $product['tipo_gestion'] == 'bobina' ? 'selected' : '' ?>>Bobina</option>
                        <option value="bolsa" <?= $product['tipo_gestion'] == 'bolsa' ? 'selected' : '' ?>>Bolsa</option>
                        <option value="par" <?= $product['tipo_gestion'] == 'par' ? 'selected' : '' ?>>Par</option>
                        <option value="kit" <?= $product['tipo_gestion'] == 'kit' ? 'selected' : '' ?>>Kit</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control" name="description" id="description" rows="3"><?= htmlspecialchars($product['description']) ?></textarea>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Actualizar producto
                    </button>
                    <a href="list.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 