<?php
// filepath: c:\xampp\htdocs\AyC-main\cotizaciones\ajax_add_insumo.php
require_once '../auth/middleware.php';
require_once '../connection.php';

$nombre = trim($_POST['nombre'] ?? '');
$categoria_id = intval($_POST['categoria_id'] ?? 0);
$supplier_id = intval($_POST['supplier_id'] ?? 0);
$unidad = trim($_POST['unidad'] ?? 'Pieza');
$precio = floatval($_POST['precio_unitario'] ?? 0);
$costo = floatval($_POST['costo'] ?? 0);
$cantidad = floatval($_POST['cantidad'] ?? 0);
$minimo = floatval($_POST['minimo'] ?? 10);
$ubicacion = trim($_POST['ubicacion'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');

// Validaciones básicas
if (!$nombre || $categoria_id <= 0 || $supplier_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios: nombre, categoría y proveedor son requeridos']);
    exit;
}

// Verificar si ya existe insumo con ese nombre
$stmt = $mysqli->prepare("SELECT insumo_id FROM insumos WHERE nombre = ? LIMIT 1");
$stmt->bind_param('s', $nombre);
$stmt->execute();
$stmt->bind_result($existing_id);
if ($stmt->fetch()) {
    $stmt->close();
    echo json_encode([
        'success' => false,
        'message' => 'Ya existe un insumo con ese nombre.'
    ]);
    exit;
}
$stmt->close();

// Verificar si la categoría existe
$stmt_cat = $mysqli->prepare("SELECT name FROM categories WHERE category_id = ?");
$stmt_cat->bind_param('i', $categoria_id);
$stmt_cat->execute();
$categoria_result = $stmt_cat->get_result();
if ($categoria_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Categoría no válida']);
    exit;
}
$categoria_data = $categoria_result->fetch_assoc();
$categoria_nombre = $categoria_data['name'];
$stmt_cat->close();

// Verificar si el proveedor existe
$stmt_prov = $mysqli->prepare("SELECT name FROM suppliers WHERE supplier_id = ?");
$stmt_prov->bind_param('i', $supplier_id);
$stmt_prov->execute();
$proveedor_result = $stmt_prov->get_result();
if ($proveedor_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Proveedor no válido']);
    exit;
}
$proveedor_data = $proveedor_result->fetch_assoc();
$proveedor_nombre = $proveedor_data['name'];
$stmt_prov->close();

// Manejar imagen si se sube
$imagen_path = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $img_tmp = $_FILES['imagen']['tmp_name'];
    $img_name = basename($_FILES['imagen']['name']);
    $img_ext = strtolower(pathinfo($img_name, PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (in_array($img_ext, $allowed)) {
        $dir = '../uploads/insumos/';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $new_name = uniqid('insumo_') . '.' . $img_ext;
        $dest = $dir . $new_name;
        if (move_uploaded_file($img_tmp, $dest)) {
            $imagen_path = 'uploads/insumos/' . $new_name;
        }
    }
}

// Determinar estado basado en cantidad y mínimo
$estado = 'disponible';
if ($cantidad <= 0) {
    $estado = 'agotado';
} elseif ($minimo > 0 && $cantidad <= $minimo) {
    $estado = 'bajo_stock';
}

// Insertar insumo con la estructura correcta
$stmt = $mysqli->prepare("INSERT INTO insumos (nombre, category_id, supplier_id, categoria, unidad, imagen, cantidad, minimo, precio_unitario, ubicacion, estado, consumo_semanal, ultima_actualizacion, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0.0, NOW(), 1)");
$stmt->bind_param('siisssddsss', $nombre, $categoria_id, $supplier_id, $categoria_nombre, $unidad, $imagen_path, $cantidad, $minimo, $precio, $ubicacion, $estado);
$stmt->execute();
$insumo_id = $stmt->insert_id;
$stmt->close();

// Ya tenemos el nombre del proveedor de la validación anterior

echo json_encode([
    'success' => true,
    'message' => 'Insumo agregado exitosamente',
    'insumo' => [
        'insumo_id' => $insumo_id,
        'nombre' => $nombre,
        'categoria' => $categoria_nombre,
        'unidad' => $unidad,
        'proveedor' => $proveedor_nombre,
        'cantidad' => $cantidad,
        'precio' => $precio,
        'minimo' => $minimo,
        'ubicacion' => $ubicacion,
        'estado' => $estado,
        'imagen' => $imagen_path
    ]
]);