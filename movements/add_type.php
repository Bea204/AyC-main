<?php
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    
    if ($name === '') {
        echo json_encode(['error' => 'El nombre del tipo de movimiento es obligatorio.']);
        exit;
    }
    
    // Verificar si ya existe
    $stmt = $mysqli->prepare("SELECT movement_type_id FROM movement_types WHERE name = ?");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['error' => 'Este tipo de movimiento ya existe.']);
        exit;
    }
    
    // Insertar nuevo tipo de movimiento con is_entry por defecto
    $stmt = $mysqli->prepare("INSERT INTO movement_types (name, is_entry) VALUES (?, 1)");
    $stmt->bind_param('s', $name);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => 'Tipo de movimiento agregado correctamente.',
            'id' => $stmt->insert_id,
            'name' => $name
        ]);
    } else {
        echo json_encode(['error' => 'Error en la base de datos: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
} else {
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}
?> 