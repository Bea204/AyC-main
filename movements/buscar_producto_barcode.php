<?php
require_once '../connection.php';
header('Content-Type: application/json');
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
if ($barcode === '') {
    echo json_encode(['success' => false, 'error' => 'No barcode']);
    exit;
}
$stmt = $mysqli->prepare('SELECT product_id, product_name FROM products WHERE barcode = ? LIMIT 1');
$stmt->bind_param('s', $barcode);
$stmt->execute();
$stmt->bind_result($product_id, $product_name);
if ($stmt->fetch()) {
    echo json_encode(['success' => true, 'product_id' => $product_id, 'product_name' => $product_name]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close(); 