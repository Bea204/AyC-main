<?php
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name === '') {
        echo json_encode(['error' => 'El nombre del proveedor es obligatorio.']);
        exit;
    }
    // Validación básica de email si se proporciona
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'El correo electrónico no es válido.']);
        exit;
    }
    $stmt = $mysqli->prepare("INSERT INTO suppliers (name, contact_name, phone, email, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $contact_name, $phone, $email, $address);
    if ($stmt->execute()) {
        echo json_encode(['success' => 'Proveedor agregado correctamente.', 'id' => $stmt->insert_id, 'name' => $name]);
    } else {
        echo json_encode(['error' => 'Error en la base de datos: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
} else {
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
} 