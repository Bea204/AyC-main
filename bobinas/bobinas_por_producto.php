<?php
require_once '../connection.php';
header('Content-Type: application/json');
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$solo_disponibles = isset($_GET['solo_disponibles']) ? intval($_GET['solo_disponibles']) : 0;
if ($product_id <= 0) {
    echo json_encode([]);
    exit;
}
$query = "SELECT bobina_id, identificador, metros_actuales FROM bobinas WHERE product_id = $product_id";
if ($solo_disponibles) {
    $query .= " AND metros_actuales > 0";
}
$query .= " ORDER BY created_at ASC";
$res = $mysqli->query($query);
$bobinas = [];
while ($b = $res->fetch_assoc()) {
    $bobinas[] = $b;
}
echo json_encode($bobinas); 