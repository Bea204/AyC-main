<?php
/**
 * Funciones auxiliares para el módulo de reportes
 * Contiene todas las consultas y funciones necesarias para generar reportes
 */

require_once '../connection.php';

/**
 * Obtener estadísticas generales del sistema
 */
function getSystemStats($mysqli) {
    $stats_query = "
        SELECT 
            COUNT(*) as total_products,
            SUM(quantity * price) as total_value,
            COUNT(CASE WHEN quantity <= 10 AND quantity > 0 THEN 1 END) as low_stock_products,
            COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN quantity > 10 THEN 1 END) as good_stock_products,
            COUNT(CASE WHEN tipo_gestion = 'bobina' THEN 1 END) as bobina_products,
            COUNT(CASE WHEN tipo_gestion = 'normal' THEN 1 END) as normal_products
        FROM products
        WHERE is_active = 1
    ";
    $result = $mysqli->query($stats_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Obtener estadísticas de cotizaciones
 */
function getQuotesStats($mysqli) {
    $quotes_stats_query = "
        SELECT 
            COUNT(*) as total_quotes,
            COUNT(DISTINCT cliente_id) as unique_clients,
            SUM(total) as total_sales,
            AVG(total) as avg_quote_value,
            COUNT(CASE WHEN estado_id = 1 THEN 1 END) as borradores,
            COUNT(CASE WHEN estado_id = 2 THEN 1 END) as enviadas,
            COUNT(CASE WHEN estado_id = 3 THEN 1 END) as aprobadas,
            COUNT(CASE WHEN estado_id = 4 THEN 1 END) as rechazadas,
            COUNT(CASE WHEN estado_id = 5 THEN 1 END) as convertidas
        FROM cotizaciones
    ";
    $result = $mysqli->query($quotes_stats_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Obtener movimientos por período
 */
function getMovementsByPeriod($mysqli, $fecha_inicio, $fecha_fin) {
    $movements_query = "
        SELECT 
            DATE(movement_date) as fecha,
            COUNT(CASE WHEN quantity > 0 THEN 1 END) as entradas,
            COUNT(CASE WHEN quantity < 0 THEN 1 END) as salidas,
            SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_entradas,
            SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_salidas
        FROM movements 
        WHERE DATE(movement_date) BETWEEN ? AND ?
        GROUP BY DATE(movement_date)
        ORDER BY fecha
    ";
    $stmt = $mysqli->prepare($movements_query);
    $stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener movimientos mensuales
 */
function getMonthlyMovements($mysqli, $months = 6) {
    $movements_query = "
        SELECT 
            DATE_FORMAT(movement_date, '%Y-%m') as month,
            COUNT(CASE WHEN quantity > 0 THEN 1 END) as entradas,
            COUNT(CASE WHEN quantity < 0 THEN 1 END) as salidas,
            SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as total_entradas,
            SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END) as total_salidas
        FROM movements 
        WHERE movement_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        GROUP BY DATE_FORMAT(movement_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($movements_query);
    $stmt->bind_param('ii', $months, $months);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener top categorías por valor
 */
function getTopCategories($mysqli, $limit = 5) {
    $categories_query = "
        SELECT 
            c.name as category_name,
            COUNT(p.product_id) as product_count,
            COALESCE(SUM(p.quantity * p.price), 0) as total_value,
            COALESCE(AVG(p.price), 0) as avg_price
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id AND p.is_active = 1
        GROUP BY c.category_id, c.name
        HAVING product_count > 0
        ORDER BY total_value DESC, c.name ASC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($categories_query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener top proveedores
 */
function getTopSuppliers($mysqli, $limit = 5) {
    $suppliers_query = "
        SELECT 
            s.name as supplier_name,
            COUNT(p.product_id) as product_count,
            COALESCE(SUM(p.quantity * p.price), 0) as total_value,
            COALESCE(AVG(p.price), 0) as avg_price
        FROM suppliers s
        LEFT JOIN products p ON s.supplier_id = p.supplier_id AND p.is_active = 1
        GROUP BY s.supplier_id, s.name
        HAVING product_count > 0
        ORDER BY total_value DESC, s.name ASC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($suppliers_query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener productos más vendidos
 */
function getTopProducts($mysqli, $limit = 10) {
    $top_products_query = "
        SELECT 
            p.product_name,
            p.sku,
            p.price,
            c.name as category_name,
            COALESCE(ABS(SUM(m.quantity)), 0) as total_movements,
            COALESCE(COUNT(m.movement_id), 0) as movement_count,
            COALESCE(SUM(CASE WHEN m.quantity < 0 THEN ABS(m.quantity) * p.price ELSE 0 END), 0) as total_sales_value
        FROM products p
        LEFT JOIN movements m ON p.product_id = m.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1
        GROUP BY p.product_id, p.product_name, p.sku, p.price, c.name
        ORDER BY total_movements DESC, p.product_name ASC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($top_products_query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener productos con stock bajo
 */
function getLowStockProducts($mysqli, $limit = 10) {
    $low_stock_query = "
        SELECT 
            p.product_name,
            p.sku,
            p.quantity,
            p.min_stock,
            p.price,
            c.name as category_name,
            s.name as supplier_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE p.quantity <= p.min_stock AND p.is_active = 1
        ORDER BY p.quantity ASC
        LIMIT ?
    ";
    $stmt = $mysqli->prepare($low_stock_query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener cotizaciones por período
 */
function getQuotesByPeriod($mysqli, $fecha_inicio, $fecha_fin) {
    $quotes_query = "
        SELECT 
            c.numero_cotizacion,
            cl.nombre as cliente_nombre,
            c.total,
            c.fecha_cotizacion,
            ec.nombre_estado as estado,
            u.first_name as usuario_nombre
        FROM cotizaciones c
        LEFT JOIN clientes cl ON c.cliente_id = cl.cliente_id
        LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE DATE(c.fecha_cotizacion) BETWEEN ? AND ?
        ORDER BY c.fecha_cotizacion DESC
    ";
    $stmt = $mysqli->prepare($quotes_query);
    $stmt->bind_param('ss', $fecha_inicio, $fecha_fin);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Obtener estadísticas de bobinas
 */
function getBobinasStats($mysqli) {
    $bobinas_query = "
        SELECT 
            COUNT(*) as total_bobinas,
            SUM(metros_actuales) as metros_totales,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as bobinas_activas,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as bobinas_inactivas,
            AVG(metros_actuales) as promedio_metros
        FROM bobinas
    ";
    $result = $mysqli->query($bobinas_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Obtener estadísticas de insumos
 */
function getInsumosStats($mysqli) {
    $insumos_query = "
        SELECT 
            COUNT(*) as total_insumos,
            COUNT(CASE WHEN estado = 'disponible' THEN 1 END) as disponibles,
            COUNT(CASE WHEN estado = 'bajo_stock' THEN 1 END) as bajo_stock,
            COUNT(CASE WHEN estado = 'agotado' THEN 1 END) as agotados,
            SUM(cantidad * precio_unitario) as valor_total
        FROM insumos
        WHERE is_active = 1
    ";
    $result = $mysqli->query($insumos_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Obtener estadísticas de equipos
 */
function getEquiposStats($mysqli) {
    $equipos_query = "
        SELECT 
            COUNT(*) as total_equipos,
            COUNT(CASE WHEN estado = 'activo' THEN 1 END) as activos,
            COUNT(CASE WHEN estado = 'inactivo' THEN 1 END) as inactivos,
            COUNT(CASE WHEN estado = 'en_reparacion' THEN 1 END) as en_reparacion
        FROM equipos
    ";
    $result = $mysqli->query($equipos_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Obtener estadísticas de usuarios
 */
function getUsersStats($mysqli) {
    $users_query = "
        SELECT 
            COUNT(*) as total_usuarios,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as usuarios_activos,
            COUNT(CASE WHEN is_active = 0 THEN 1 END) as usuarios_inactivos,
            COUNT(CASE WHEN last_login IS NOT NULL THEN 1 END) as usuarios_conectados
        FROM users
    ";
    $result = $mysqli->query($users_query);
    return $result ? $result->fetch_assoc() : [];
}

/**
 * Formatear número para mostrar
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Formatear moneda
 */
function formatCurrency($amount) {
    return '$' . formatNumber($amount, 2);
}

/**
 * Obtener porcentaje
 */
function getPercentage($value, $total) {
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 1);
}

/**
 * Generar datos para gráficas
 */
function generateChartData($data, $labelField, $valueField) {
    $labels = [];
    $values = [];
    
    if ($data && $data->num_rows > 0) {
        while ($row = $data->fetch_assoc()) {
            $labels[] = $row[$labelField];
            $values[] = $row[$valueField];
        }
    }
    
    return ['labels' => $labels, 'values' => $values];
}

/**
 * Obtener colores para gráficas
 */
function getChartColors($count) {
    $colors = [
        '#121866', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
        '#6f42c1', '#fd7e14', '#e83e8c', '#20c997', '#6c757d'
    ];
    
    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $result[] = $colors[$i % count($colors)];
    }
    
    return $result;
}

/**
 * Validar fechas para reportes
 */
function validateReportDates($fecha_inicio, $fecha_fin) {
    if (empty($fecha_inicio) || empty($fecha_fin)) {
        return false;
    }
    
    $inicio = strtotime($fecha_inicio);
    $fin = strtotime($fecha_fin);
    
    if (!$inicio || !$fin) {
        return false;
    }
    
    if ($inicio > $fin) {
        return false;
    }
    
    return true;
}

/**
 * Obtener período de reporte
 */
function getReportPeriod($type = 'month') {
    switch ($type) {
        case 'week':
            return [
                'inicio' => date('Y-m-d', strtotime('monday this week')),
                'fin' => date('Y-m-d', strtotime('sunday this week'))
            ];
        case 'month':
            return [
                'inicio' => date('Y-m-01'),
                'fin' => date('Y-m-t')
            ];
        case 'quarter':
            $quarter = ceil(date('n') / 3);
            $start_month = ($quarter - 1) * 3 + 1;
            return [
                'inicio' => date('Y-' . str_pad($start_month, 2, '0', STR_PAD_LEFT) . '-01'),
                'fin' => date('Y-m-t', strtotime(date('Y-' . str_pad($start_month + 2, 2, '0', STR_PAD_LEFT) . '-01')))
            ];
        case 'year':
            return [
                'inicio' => date('Y-01-01'),
                'fin' => date('Y-12-31')
            ];
        default:
            return [
                'inicio' => date('Y-m-01'),
                'fin' => date('Y-m-d')
            ];
    }
}
?> 