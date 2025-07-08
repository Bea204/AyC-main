<?php
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['error' => 'El nombre es obligatorio.']);
        exit;
    }
    $stmt = $mysqli->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param('s', $name);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Categoría agregada correctamente.', 'id' => $stmt->insert_id, 'name' => $name]);
    } else {
        echo json_encode(['error' => 'Error en la base de datos: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
} else {
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
} 