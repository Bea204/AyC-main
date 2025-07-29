<?php
require_once '../connection.php';
header('Content-Type: application/json');

// Recibir filtros
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$proveedor = isset($_GET['proveedor']) ? trim($_GET['proveedor']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// Construir consulta base
$query = "SELECT i.*, c.name as categoria_nombre, s.name as proveedor 
          FROM insumos i
          LEFT JOIN categories c ON i.category_id = c.category_id
          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
          WHERE i.is_active = 1";
$params = [];
$types = '';

if ($categoria) {
    $query .= " AND i.category_id = ?";
    $params[] = $categoria;
    $types .= 'i';
}
if ($proveedor) {
    $query .= " AND i.supplier_id = ?";
    $params[] = $proveedor;
    $types .= 'i';
}
if ($estado) {
    $query .= " AND i.estado = ?";
    $params[] = $estado;
    $types .= 's';
}
if ($busqueda) {
    $query .= " AND (i.nombre LIKE ? OR i.categoria LIKE ? OR s.name LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
$query .= " ORDER BY i.nombre ASC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$insumos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Devolver como JSON
echo json_encode([
    'success' => true,
    'data' => $insumos
]); 