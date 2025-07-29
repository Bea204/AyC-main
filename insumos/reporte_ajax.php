<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once '../connection.php';

$action = $_GET['action'] ?? null;
$insumo_id = $_GET['insumo_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'obtener_reporte') {
    $insumo_id = intval($insumo_id);
    if (!$insumo_id) {
        echo json_encode(['success'=>false,'message'=>'ID de insumo requerido.']);
        exit;
    }
    $stmt = $mysqli->prepare("SELECT i.*, c.name as categoria_nombre, s.name as proveedor_nombre FROM insumos i 
                              LEFT JOIN categories c ON i.category_id = c.category_id
                              LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                              WHERE i.insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    $stmt = $mysqli->prepare("SELECT m.insumo_movement_id, m.tipo_movimiento, m.cantidad, m.piezas_movidas, m.motivo, m.fecha_movimiento, u.username as usuario 
                              FROM insumos_movements m 
                              LEFT JOIN users u ON m.user_id = u.user_id 
                              WHERE m.insumo_id = ? 
                              ORDER BY m.fecha_movimiento DESC");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $movimientos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $total_entradas = 0;
    $total_salidas = 0;
    $consumo_semanal = 0;
    foreach ($movimientos as $mov) {
        if ($mov['tipo_movimiento'] === 'entrada') {
            $total_entradas += $mov['cantidad'];
        } else {
            $total_salidas += $mov['cantidad'];
        }
    }
    if (count($movimientos) > 0) {
        $consumo_semanal = $total_salidas / 4;
    }
    $reporte = [
        'insumo' => $insumo,
        'movimientos' => $movimientos,
        'estadisticas' => [
            'total_entradas' => $total_entradas,
            'total_salidas' => $total_salidas,
            'consumo_semanal' => $consumo_semanal,
            'stock_actual' => $insumo['cantidad'],
            'stock_minimo' => $insumo['minimo']
        ]
    ];
    echo json_encode(['success'=>true,'data'=>$reporte]);
    exit;
}
echo json_encode(['success'=>false,'message'=>'Petición inválida.']);
exit; 