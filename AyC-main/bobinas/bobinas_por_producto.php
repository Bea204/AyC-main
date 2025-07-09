<?php
require_once '../connection.php';
header('Content-Type: application/json');
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) {
    echo json_encode([]);
    exit;
}
$res = $mysqli->query("SELECT bobina_id, identificador, metros_actuales FROM bobinas WHERE product_id = $product_id AND metros_actuales > 0 ORDER BY fecha_ingreso ASC");
$bobinas = [];
while ($b = $res->fetch_assoc()) {
    $bobinas[] = $b;
}
echo json_encode($bobinas); 