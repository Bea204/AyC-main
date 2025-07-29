<?php
require_once '../auth/middleware.php';
require_once '../connection.php';
require_once 'helpers.php'; // Asegúrate de tener esto arriba
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$cotizacion_id = intval($_POST['cotizacion_id'] ?? 0);
$devolver = $_POST['devolver'] ?? [];
$user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;

if (!$cotizacion_id || !$devolver || !is_array($devolver)) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

// La devolución ya no afecta el inventario ni registra movimientos
echo json_encode(['success' => true, 'message' => 'La devolución no afecta el inventario.']);