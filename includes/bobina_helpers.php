<?php
/**
 * Funciones helper para gestión de bobinas
 */

/**
 * Actualiza el stock de un producto tipo bobina basado en la suma de metros de bobinas activas
 * @param mysqli $mysqli Conexión a la base de datos
 * @param int $product_id ID del producto
 */
function actualizarStockBobina($mysqli, $product_id) {
    $stmt = $mysqli->prepare("UPDATE products SET quantity = (SELECT COALESCE(SUM(metros_actuales), 0) FROM bobinas WHERE product_id = ? AND is_active = 1) WHERE product_id = ?");
    $stmt->bind_param("ii", $product_id, $product_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Obtiene el stock total de un producto tipo bobina
 * @param mysqli $mysqli Conexión a la base de datos
 * @param int $product_id ID del producto
 * @return float Stock total en metros
 */
function obtenerStockBobina($mysqli, $product_id) {
    $stmt = $mysqli->prepare("SELECT COALESCE(SUM(metros_actuales), 0) as stock_total FROM bobinas WHERE product_id = ? AND is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return floatval($row['stock_total']);
}

/**
 * Verifica si hay suficientes metros disponibles en las bobinas
 * @param mysqli $mysqli Conexión a la base de datos
 * @param int $product_id ID del producto
 * @param float $metros_requeridos Metros que se necesitan
 * @return bool True si hay suficientes metros disponibles
 */
function verificarStockBobina($mysqli, $product_id, $metros_requeridos) {
    $stock_disponible = obtenerStockBobina($mysqli, $product_id);
    return $stock_disponible >= $metros_requeridos;
}
?> 