<?php
// filepath: c:\xampp\htdocs\inventory-management-system-main\cotizaciones\ajax_add_producto.php
require_once '../auth/middleware.php';
require_once '../connection.php';

$nombre = trim($_POST['nombre'] ?? '');
$sku = trim($_POST['sku'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$costo = floatval($_POST['costo'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
$categoria = $_POST['categoria'] ?? null;
$proveedor = $_POST['proveedor'] ?? null;
$description = trim($_POST['descripcion'] ?? ''); // Cambia a $description

if (!$nombre || !$precio || $cantidad === null || $cantidad < 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios o cantidad inválida']);
    exit;
}

// Verificar si ya existe producto con ese nombre o SKU
$stmt = $mysqli->prepare("SELECT product_id FROM products WHERE product_name = ? OR sku = ? LIMIT 1");
$stmt->bind_param('ss', $nombre, $sku);
$stmt->execute();
$stmt->bind_result($existing_id);
if ($stmt->fetch()) {
    $stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Ya existe un producto con ese nombre o SKU.'
    ]);
    exit;
}
$stmt->close();

// Generar SKU si no se proporcionó
if (!$sku) {
    $sku = strtoupper(substr($nombre, 0, 3)) . '-' . rand(1000,9999);
}

$image_path = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $img_tmp = $_FILES['imagen']['tmp_name'];
    $img_name = basename($_FILES['imagen']['name']);
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

$stmt = $mysqli->prepare("INSERT INTO products (product_name, sku, price, cost_price, quantity, category_id, supplier_id, description, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssdiiisss', $nombre, $sku, $precio, $costo, $cantidad, $categoria, $proveedor, $description, $image_path);
$stmt->execute();
$product_id = $stmt->insert_id;
$stmt->close();

echo json_encode([
    'success' => true,
    'producto' => [
        'product_id' => $product_id,
        'nombre' => $nombre,
        'sku' => $sku,
        'categoria' => $categoria,
        'proveedor' => $proveedor,
        'stock' => $cantidad,
        'imagen' => $image_path
    ]
]);