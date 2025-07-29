<?php
require_once '../connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sku'])) {
    $sku = trim($_POST['sku']);
    
    if (empty($sku)) {
        echo json_encode(['exists' => false]);
        exit;
    }
    
    $stmt = $mysqli->prepare("SELECT product_id, product_name FROM products WHERE sku = ?");
    $stmt->bind_param("s", $sku);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode([
            'exists' => true,
            'product_name' => $product['product_name']
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
    $stmt->close();
} else if (isset($_GET['stockinfo']) && $_GET['stockinfo'] == '1' && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $stmt = $mysqli->prepare("SELECT quantity, max_stock FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $stmt->bind_result($stock, $max_stock);
    if ($stmt->fetch()) {
        echo json_encode(['stock' => floatval($stock), 'max_stock' => floatval($max_stock)]);
    } else {
        echo json_encode(['stock' => null, 'max_stock' => null]);
    }
    $stmt->close();
    $mysqli->close();
    exit;
} else {
    echo json_encode(['error' => 'Solicitud inválida']);
}

$mysqli->close();
?> 