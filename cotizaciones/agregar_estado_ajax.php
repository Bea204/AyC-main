<?php
require_once '../connection.php';
header('Content-Type: application/json');

$nombre = isset($_POST['nombre_estado']) ? trim($_POST['nombre_estado']) : '';
if ($nombre === '') {
    echo json_encode(['error' => 'El nombre es obligatorio']);
    exit;
}
// Verificar si ya existe
$stmt = $mysqli->prepare("SELECT est_cot_id FROM est_cotizacion WHERE nombre_estado = ?");
$stmt->bind_param('s', $nombre);
$stmt->execute();
$stmt->bind_result($id_existente);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'Ese estado ya existe']);
    $stmt->close();
    exit;
}
$stmt->close();
// Insertar nuevo estado
$stmt = $mysqli->prepare("INSERT INTO est_cotizacion (nombre_estado) VALUES (?)");
$stmt->bind_param('s', $nombre);
if ($stmt->execute()) {
    echo json_encode(['success' => 'Estado registrado', 'id' => $stmt->insert_id, 'nombre' => $nombre]);
} else {
    echo json_encode(['error' => 'Error al registrar el estado']);
}
$stmt->close(); 