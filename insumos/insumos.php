<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
error_log('Iniciando insumos.php');
error_log('REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('POST: ' . print_r($_POST, true));
error_log('REQUEST: ' . print_r($_REQUEST, true));
error_log('php://input: ' . file_get_contents('php://input'));
require_once '../auth/middleware.php';
require_once '../connection.php';

// Función para obtener categorías disponibles para insumos
function getCategoriasInsumos() {
    global $mysqli;
    $categorias = [];
    
    $query = "SELECT category_id, name FROM categories ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    return $categorias;
}

// Función para obtener proveedores disponibles
function getProveedoresInsumos() {
    global $mysqli;
    $proveedores = [];
    
    $query = "SELECT supplier_id, name FROM suppliers ORDER BY name";
    $result = $mysqli->query($query);
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }
    
    return $proveedores;
}

// Filtros
$categoria_filtro = isset($_GET['categoria']) ? $_GET['categoria'] : '';
$proveedor_filtro = isset($_GET['proveedor']) ? $_GET['proveedor'] : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

// Construir consulta base para insumos
$query = "SELECT i.*, c.name as categoria_nombre, s.name as proveedor 
          FROM insumos i
          LEFT JOIN categories c ON i.category_id = c.category_id
          LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id 
          WHERE i.is_active = 1";
$params = [];
$types = '';

if ($categoria_filtro) {
    $query .= " AND i.category_id = ?";
    $params[] = $categoria_filtro;
    $types .= 'i';
}
if ($proveedor_filtro) {
    $query .= " AND i.supplier_id = ?";
    $params[] = $proveedor_filtro;
    $types .= 'i';
}
if ($estado_filtro) {
    $query .= " AND i.estado = ?";
    $params[] = $estado_filtro;
    $types .= 's';
}
if ($busqueda) {
    $query .= " AND (i.nombre LIKE ? OR i.categoria LIKE ? OR s.name LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= 'sss';
}
$query .= " ORDER BY i.nombre ASC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$insumos = $stmt->get_result();

// Obtener categorías únicas
$categorias = $mysqli->query("SELECT category_id, name FROM categories ORDER BY name");
// Obtener proveedores únicos
$proveedores = $mysqli->query("SELECT supplier_id, name FROM suppliers ORDER BY name");

// Calcular estadísticas
$stats_query = "SELECT COUNT(*) as total_insumos,
    SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
    SUM(CASE WHEN estado = 'bajo_stock' THEN 1 ELSE 0 END) as bajo_stock,
    SUM(CASE WHEN estado = 'agotado' THEN 1 ELSE 0 END) as agotados,
    SUM(cantidad * precio_unitario) as valor_total,
    SUM(consumo_semanal) as consumo_semanal_total
    FROM insumos WHERE is_active = 1";
$stats = $mysqli->query($stats_query)->fetch_assoc();
$total_insumos = $stats['total_insumos'] ?? 0;
$disponibles = $stats['disponibles'] ?? 0;
$bajo_stock = $stats['bajo_stock'] ?? 0;
$agotados = $stats['agotados'] ?? 0;
$valor_total = $stats['valor_total'] ?? 0;
$consumo_semanal_total = $stats['consumo_semanal_total'] ?? 0;

// --- Endpoint para alta real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'agregar_insumo') {
    ob_clean();
    header('Content-Type: application/json');
    try {
        $nombre = trim($_POST['nombre'] ?? '');
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $proveedor_id = intval($_POST['proveedor_id'] ?? 0);
        $cantidad = floatval($_POST['cantidad'] ?? 0);
        $minimo = floatval($_POST['minimo'] ?? 0);
        $unidad = trim($_POST['unidad'] ?? 'pieza');
        $precio_unitario = floatval($_POST['precio_unitario'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
        $imagen_ruta = null;

        // Procesar imagen si se envía
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $imgTmp = $_FILES['imagen']['tmp_name'];
            $imgName = basename($_FILES['imagen']['name']);
            $imgExt = strtolower(pathinfo($imgName, PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($imgExt, $permitidas)) {
                error_log('Tipo de imagen no permitido: ' . $imgExt);
                echo json_encode(['success'=>false,'message'=>'Tipo de imagen no permitido. Usa JPG, PNG, GIF o WEBP.']);
                exit;
            }
            if ($_FILES['imagen']['size'] > 2*1024*1024) { // 2MB
                error_log('Imagen demasiado grande: ' . $_FILES['imagen']['size']);
                echo json_encode(['success'=>false,'message'=>'La imagen es demasiado grande (máx 2MB).']);
                exit;
            }
            $nombreArchivo = 'insumo_' . time() . '_' . rand(1000,9999) . '.' . $imgExt;
            $destino = __DIR__ . '/../uploads/insumos/' . $nombreArchivo;
            if (!move_uploaded_file($imgTmp, $destino)) {
                error_log('No se pudo mover la imagen a: ' . $destino);
                echo json_encode(['success'=>false,'message'=>'No se pudo guardar la imagen.']);
                exit;
            }
            $imagen_ruta = 'uploads/insumos/' . $nombreArchivo;
        }

        if (!$nombre || $categoria_id <= 0 || $proveedor_id <= 0 || $cantidad < 0 || $minimo < 0 || $precio_unitario < 0) {
            error_log('Datos incompletos o inválidos en alta de insumo');
            echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
            exit;
        }
        
        // Obtener información de categoría y proveedor
        $stmt = $mysqli->prepare("SELECT c.name as categoria_nombre, s.name as proveedor_nombre 
                                  FROM categories c, suppliers s 
                                  WHERE c.category_id = ? AND s.supplier_id = ?");
        if (!$stmt) {
            error_log('Error en prepare SELECT categoria/proveedor: ' . $mysqli->error);
            echo json_encode(['success'=>false,'message'=>'Error interno al buscar categoría/proveedor.']);
            exit;
        }
        $stmt->bind_param('ii', $categoria_id, $proveedor_id);
        $stmt->execute();
        $info = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$info) {
            error_log('No se encontró categoría o proveedor para IDs: ' . $categoria_id . ', ' . $proveedor_id);
            echo json_encode(['success'=>false,'message'=>'Categoría o proveedor no encontrado.']);
            exit;
        }
        
        // Determinar estado inicial
        $estado = 'disponible';
        if ($cantidad <= $minimo) {
            $estado = 'bajo_stock';
        }
        if ($cantidad == 0) {
            $estado = 'agotado';
        }
        
        // Insertar insumo como entidad independiente
        $categoria_nombre = $info['categoria_nombre'];
        $ubicacion = '';
        $consumo_semanal = 0.0;
        $is_active = 1;
        $sql = "INSERT INTO insumos (nombre, category_id, supplier_id, categoria, unidad, imagen, cantidad, minimo, precio_unitario, ubicacion, estado, consumo_semanal, ultima_actualizacion, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log('Error en prepare INSERT insumo: ' . $mysqli->error);
            echo json_encode(['success'=>false,'message'=>'Error interno al guardar el insumo.']);
            exit;
        }
        $stmt->bind_param('siisssddsssdi', $nombre, $categoria_id, $proveedor_id, $categoria_nombre, $unidad, $imagen_ruta, $cantidad, $minimo, $precio_unitario, $ubicacion, $estado, $consumo_semanal, $is_active);

        if ($stmt->execute()) {
            echo json_encode(['success'=>true,'message'=>'Insumo registrado correctamente.']);
        } else {
            error_log('Error al ejecutar INSERT insumo: ' . $stmt->error);
            echo json_encode(['success'=>false,'message'=>'Error al registrar el insumo: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    } catch (Exception $e) {
        error_log('Excepción en alta de insumo: ' . $e->getMessage());
        echo json_encode(['success'=>false,'message'=>'Error inesperado: ' . $e->getMessage()]);
        exit;
    }
}

// --- Endpoint para edición real de insumos (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_insumo') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $minimo = floatval($_POST['minimo'] ?? 0);
    $unidad = trim($_POST['unidad'] ?? 'pieza');
    
    if (!$insumo_id || $minimo < 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    
    // Obtener insumo actual
    $stmt = $mysqli->prepare("SELECT * FROM insumos WHERE insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    
    // Determinar estado basado en la cantidad actual
    $estado = 'disponible';
    if ($insumo['cantidad'] <= $minimo) {
        $estado = 'bajo_stock';
    }
    if ($insumo['cantidad'] == 0) {
        $estado = 'agotado';
    }
    
    // Actualizar insumo (solo minimo, unidad y estado)
    $stmt = $mysqli->prepare("UPDATE insumos SET minimo = ?, unidad = ?, estado = ?, ultima_actualizacion = NOW() WHERE insumo_id = ?");
    $stmt->bind_param('dssi', $minimo, $unidad, $estado, $insumo_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success'=>true,'message'=>'Insumo actualizado correctamente.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al actualizar el insumo: ' . $stmt->error]);
    }
    $stmt->close();
    exit;
}

// --- Endpoint para registrar movimiento de insumo (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_movimiento') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);
    $tipo_movimiento = $_POST['tipo_movimiento'] ?? '';
    $cantidad = floatval($_POST['cantidad'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1; // Default a 1 si no hay usuario
    
    if (!$insumo_id || !in_array($tipo_movimiento, ['entrada', 'salida']) || $cantidad <= 0) {
        echo json_encode(['success'=>false,'message'=>'Datos incompletos o inválidos.']);
        exit;
    }
    
    // Obtener datos del insumo
    $stmt = $mysqli->prepare("SELECT i.*, c.name as categoria_nombre, s.name as proveedor_nombre
                              FROM insumos i 
                              LEFT JOIN categories c ON i.category_id = c.category_id
                              LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
                              WHERE i.insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);
    $stmt->execute();
    $insumo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$insumo) {
        echo json_encode(['success'=>false,'message'=>'Insumo no encontrado.']);
        exit;
    }
    
    // Debug: Mostrar información del insumo
    error_log("Insumo ID: " . $insumo_id);
    error_log("Tipo movimiento: " . $tipo_movimiento);
    error_log("Cantidad: " . $cantidad);
    error_log("Stock actual insumo: " . $insumo['cantidad']);
    
    // Verificar stock según el tipo de movimiento
    if ($tipo_movimiento === 'entrada') {
        // Para entradas, no hay restricciones - se puede agregar stock libremente
        // Solo verificar que la cantidad sea positiva
        if ($cantidad <= 0) {
            echo json_encode(['success'=>false,'message'=>'La cantidad de entrada debe ser mayor a 0.']);
            exit;
        }
    } else {
        // Para salidas, verificar stock del insumo
        if ($insumo['cantidad'] < $cantidad) {
            echo json_encode(['success'=>false,'message'=>'No hay suficiente stock en el insumo. Stock actual: ' . $insumo['cantidad'] . ' ' . $insumo['unidad']]);
            exit;
        }
    }
    
    // Calcular nueva cantidad del insumo
    $nueva_cantidad = $insumo['cantidad'];
    if ($tipo_movimiento === 'entrada') {
        $nueva_cantidad += $cantidad;
    } else {
        $nueva_cantidad -= $cantidad;
    }
    
    // Determinar nuevo estado del insumo
    $estado = 'disponible';
    if ($nueva_cantidad <= $insumo['minimo']) {
        $estado = 'bajo_stock';
    }
    if ($nueva_cantidad == 0) {
        $estado = 'agotado';
    }
    
    // Usar transacción para asegurar consistencia
    $mysqli->begin_transaction();
    
    try {
        // Registrar movimiento
        $piezas_movidas = isset($_POST['piezas_movidas']) && !empty($_POST['piezas_movidas']) ? floatval($_POST['piezas_movidas']) : null;
        $stmt = $mysqli->prepare("INSERT INTO insumos_movements (insumo_id, user_id, tipo_movimiento, motivo, cantidad, piezas_movidas, fecha_movimiento) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('iissdd', $insumo_id, $user_id, $tipo_movimiento, $motivo, $cantidad, $piezas_movidas);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al registrar el movimiento: ' . $stmt->error);
        }
        $stmt->close();
        
        // Actualizar stock del insumo (SIN descontar del inventario)
        $stmt2 = $mysqli->prepare("UPDATE insumos SET cantidad = ?, estado = ?, ultima_actualizacion = NOW() WHERE insumo_id = ?");
        $stmt2->bind_param('dsi', $nueva_cantidad, $estado, $insumo_id);
        
        if (!$stmt2->execute()) {
            throw new Exception('Error al actualizar el insumo: ' . $stmt2->error);
        }
        $stmt2->close();
        
        $mysqli->commit();
        echo json_encode(['success'=>true,'message'=>'Movimiento registrado correctamente. Nuevo stock del insumo: ' . $nueva_cantidad . ' ' . $insumo['unidad']]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Error en movimiento de insumo: " . $e->getMessage());
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    
    exit;
}

// --- Endpoint para eliminar insumo (lógico) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_insumo') {
    header('Content-Type: application/json');
    $insumo_id = intval($_POST['insumo_id'] ?? 0);

    if (!$insumo_id) {
        echo json_encode(['success' => false, 'message' => 'ID de insumo no válido.']);
        exit;
    }

    // Doble validación en el servidor para el stock
    $check_stmt = $mysqli->prepare("SELECT cantidad FROM insumos WHERE insumo_id = ?");
    $check_stmt->bind_param('i', $insumo_id);
    $check_stmt->execute();
    $insumo_stock = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if (!$insumo_stock) {
        echo json_encode(['success' => false, 'message' => 'Insumo no encontrado.']);
        exit;
    }

    if (floatval($insumo_stock['cantidad']) > 0) {
        echo json_encode(['success' => false, 'message' => 'Acción no permitida: El insumo todavía tiene stock.']);
        exit;
    }

    $stmt = $mysqli->prepare("UPDATE insumos SET is_active = 0 WHERE insumo_id = ?");
    $stmt->bind_param('i', $insumo_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Insumo eliminado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el insumo.']);
        }
    } else {
        error_log("Error al eliminar insumo: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Error al procesar la solicitud.']);
    }
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Insumos y Materiales | Gestor de inventarios</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f4f6fb;
        }
        .main-content {
            margin-top: 40px;
            margin-left: 250px;
            padding: 24px;
            width: calc(100vw - 250px);
            box-sizing: border-box;
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 70px !important;
            width: calc(100vw - 70px) !important;
            transition: margin-left 0.25s cubic-bezier(.4,2,.6,1), width 0.25s;
        }
        .insumos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
        }
        .insumos-title {
            font-size: 2.2rem;
            color: #121866;
            font-weight: 800;
            margin: 0;
        }
        .insumos-stats {
            display: flex;
            gap: 24px;
        }
        .stat-item {
            text-align: center;
            padding: 16px 20px;
            background: linear-gradient(135deg, #121866, #232a7c);
            color: #fff;
            border-radius: 12px;
            min-width: 120px;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 700;
            display: block;
        }
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .filters-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 2px 12px rgba(18,24,102,0.07);
        }
        .insumos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }
        .insumo-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 24px rgba(18,24,102,0.10);
            border: 1.5px solid #e3e6f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .insumo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 32px rgba(18,24,102,0.15);
        }
        .insumo-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #121866, #232a7c);
        }
        .insumo-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .insumo-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #121866;
            margin: 0;
            line-height: 1.3;
        }
        .insumo-category {
            display: inline-block;
            padding: 4px 12px;
            background: #e3f2fd;
            color: #1565c0;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .stock-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-disponible {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-bajo_stock {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-agotado {
            background: #ffebee;
            color: #c62828;
        }
        .insumo-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .detail-item {
            text-align: center;
            padding: 12px 8px;
            background: #f7f9fc;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }
        .detail-number {
            font-size: 1.4rem;
            font-weight: 700;
            color: #121866;
            display: block;
            margin-bottom: 4px;
        }
        .detail-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        .insumo-actions {
            display: flex;
            gap: 8px;
            justify-content: stretch;
        }
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 40px;
            max-width: 100%;
        }
        .btn-edit {
            background: #e3f2fd;
            color: #1565c0;
        }
        .btn-edit:hover {
            background: #1565c0;
            color: #fff;
        }
        .btn-movement {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .btn-movement:hover {
            background: #7b1fa2;
            color: #fff;
        }
        .btn-report {
            background: #fff3e0;
            color: #f57c00;
        }
        .btn-report:hover {
            background: #f57c00;
            color: #fff;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .empty-state i {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }
        .alert-section {
            margin-bottom: 24px;
        }
        .alert-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            border-left: 4px solid #e53935;
            box-shadow: 0 2px 8px rgba(18,24,102,0.07);
        }
        .alert-card.warning {
            border-left-color: #ffc107;
        }
        .alert-card.info {
            border-left-color: #00bcd4;
        }
        .producto-origen {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
            margin-bottom: 8px;
        }
        .consumo-semanal {
            display: inline-block;
            padding: 2px 8px;
            background: #e8f5e8;
            color: #2e7d32;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .main-content { 
                width: calc(100vw - 70px); 
                margin-left: 70px; 
                padding: 16px; 
            }
            .insumos-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            .insumos-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .insumos-grid {
                grid-template-columns: 1fr;
            }
        }
        .btn-delete {
            background: #ffeaea !important;
            color: #c62828 !important;
            border: none;
            transition: background 0.2s, color 0.2s;
        }
        .btn-delete:hover {
            background: #c62828 !important;
            color: #fff !important;
        }
        .insumo-actions {
            display: flex;
            gap: 8px;
            justify-content: stretch;
        }
        .btn-action {
            flex: 1;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 40px;
            max-width: 100%;
        }
        .btn-delete {
            background: #ffeaea !important;
            color: #c62828 !important;
            border: none;
            transition: background 0.2s, color 0.2s;
        }
        .btn-delete:hover {
            background: #c62828 !important;
            color: #fff !important;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="insumos-header">
            <div>
                <h1 class="insumos-title">
                    <i class="bi bi-box2"></i> Insumos y Materiales
                </h1>
                <p class="text-muted mb-0">Materiales de trabajo independientes para gestión de stock</p>
            </div>
            <div class="insumos-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= $total_insumos ?></span>
                    <span class="stat-label">Insumos</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $disponibles ?></span>
                    <span class="stat-label">Disponibles</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $bajo_stock ?></span>
                    <span class="stat-label">Bajo Stock</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $agotados ?></span>
                    <span class="stat-label">Agotados</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">$<?= number_format($valor_total, 0) ?></span>
                    <span class="stat-label">Valor Total</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number"><?= $consumo_semanal_total ?></span>
                    <span class="stat-label">Consumo Sem.</span>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div class="alert-section">
            <?php 
            $agotados_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'agotado' AND i.is_active = 1 ORDER BY i.nombre ASC");
            $bajo_stock_insumos = $mysqli->query("SELECT i.*, i.categoria as categoria, s.name as proveedor FROM insumos i
                LEFT JOIN products p ON i.product_id = p.product_id
                LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id WHERE i.estado = 'bajo_stock' AND i.is_active = 1 ORDER BY i.nombre ASC");
            ?>
            
            <?php if ($agotados_insumos->num_rows > 0): ?>
                <div class="alert-card">
                    <h6><i class="bi bi-exclamation-triangle"></i> Insumos Agotados</h6>
                    <p class="mb-0">Los siguientes insumos están agotados y requieren reabastecimiento:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $agotados_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= htmlspecialchars($insumo['proveedor'] ?? 'Agotado') ?></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($bajo_stock_insumos->num_rows > 0): ?>
                <div class="alert-card warning">
                    <h6><i class="bi bi-exclamation-circle"></i> Stock Bajo</h6>
                    <p class="mb-0">Los siguientes insumos tienen stock bajo:</p>
                    <ul class="mb-0 mt-2">
                        <?php while ($insumo = $bajo_stock_insumos->fetch_assoc()): ?>
                            <li><strong><?= htmlspecialchars($insumo['nombre']) ?></strong> - <?= $insumo['cantidad'] ?> <?= $insumo['unidad'] ?> (mínimo: <?= $insumo['minimo'] ?>)</li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="filters-section">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="busqueda" class="form-label">Buscar insumo</label>
                    <input type="text" class="form-control" id="busqueda" name="busqueda" 
                           value="<?= htmlspecialchars($busqueda) ?>" 
                           placeholder="Nombre, categoría, proveedor...">
                </div>
                <div class="col-md-2">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category_id']) ?>" 
                                    <?= $categoria_filtro == $cat['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="proveedor" class="form-label">Proveedor</label>
                    <select class="form-select" id="proveedor" name="proveedor">
                        <option value="">Todos</option>
                        <?php while ($prov = $proveedores->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($prov['supplier_id']) ?>" 
                                    <?= $proveedor_filtro == $prov['supplier_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($prov['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="disponible" <?= $estado_filtro === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="bajo_stock" <?= $estado_filtro === 'bajo_stock' ? 'selected' : '' ?>>Bajo Stock</option>
                        <option value="agotado" <?= $estado_filtro === 'agotado' ? 'selected' : '' ?>>Agotado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filtrar
                        </button>
                        <a href="insumos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Limpiar
                        </a>
                        <button type="button" class="btn btn-success" onclick="agregarInsumo()">
                            <i class="bi bi-plus-circle"></i> Agregar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($insumos->num_rows === 0): ?>
            <div class="empty-state">
                <i class="bi bi-box2"></i>
                <h5>No se encontraron insumos</h5>
                <p>Intenta ajustar los filtros de búsqueda o agrega nuevos insumos.</p>
                <button type="button" class="btn btn-primary" onclick="agregarInsumo()">
                    <i class="bi bi-plus-circle"></i> Agregar Insumo
                </button>
            </div>
        <?php else: ?>
            <?php 
            // Guardar los datos de insumos para JavaScript antes del bucle
            $insumos_data = $insumos->fetch_all(MYSQLI_ASSOC);
            ?>
            <div class="insumos-grid">
                <?php foreach ($insumos_data as $insumo): ?>
                    <div class="insumo-card">
                        <div class="insumo-header">
                            <div>
                                <?php if (!empty($insumo['imagen'])): ?>
                                    <div style="text-align:center;">
                                        <img src="../<?= htmlspecialchars($insumo['imagen']) ?>" alt="Imagen insumo" style="max-width:80px;max-height:80px;border-radius:8px;margin-bottom:8px;object-fit:contain;">
                                    </div>
                                <?php else: ?>
                                    <div style="text-align:center;">
                                        <i class="bi bi-image" style="font-size:2.5rem;color:#ccc;margin-bottom:8px;"></i>
                                    </div>
                                <?php endif; ?>
                                <h5 class="insumo-title"><?= htmlspecialchars($insumo['nombre']) ?></h5>
                                <div class="insumo-category"><?= htmlspecialchars($insumo['categoria']) ?></div>
                                <div class="producto-origen">
                                    <i class="bi bi-tag"></i> Categoría: <?= htmlspecialchars($insumo['categoria_nombre'] ?? 'Sin categoría') ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="stock-status">
                            <span class="status-badge status-<?= $insumo['estado'] ?>">
                                <?php
                                switch($insumo['estado']) {
                                    case 'disponible': echo '<i class="bi bi-check-circle"></i> Disponible'; break;
                                    case 'bajo_stock': echo '<i class="bi bi-exclamation-triangle"></i> Bajo Stock'; break;
                                    case 'agotado': echo '<i class="bi bi-x-circle"></i> Agotado'; break;
                                }
                                ?>
                            </span>
                            <span class="consumo-semanal">
                                <i class="bi bi-calendar-week"></i> <?= $insumo['consumo_semanal'] ?> <?= $insumo['unidad'] ?>/sem
                            </span>
                        </div>
                        
                        <div class="insumo-details">
                            <div class="detail-item">
                                <?php
                                $stock_bolsas = (float)$insumo['cantidad'];
                                $unidad = $insumo['unidad'];
                                $equivalencia = 1;
                                if (preg_match('/\((\d+) piezas\)/', $unidad, $m)) {
                                    $equivalencia = (int)$m[1];
                                }

                                if ($equivalencia > 1) {
                                    $stock_piezas = floor($stock_bolsas * $equivalencia);
                                    $stock_bolsas_redondeado = ceil($stock_bolsas);
                                    echo "<span class='detail-number' style='font-size: 1.5rem;'>{$stock_bolsas_redondeado} bolsas</span>";
                                    echo "<small class='text-muted d-block' style='font-size: 0.85em;'>Cada bolsa = {$equivalencia} piezas</small>";
                                    echo "<small class='text-muted d-block' style='font-size: 0.75rem;'>{$stock_piezas} piezas aprox.</small>";
                                } else {
                                    echo "<span class='detail-number'>".number_format($stock_bolsas, 2)."</span>
                                          <span class='detail-label'>Stock ({$unidad})</span>";
                                }
                                ?>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number"><?= number_format($insumo['minimo'], 2) ?></span>
                                <span class="detail-label">Mínimo</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Precio Unit.</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-number">$<?= number_format($insumo['cantidad'] * $insumo['precio_unitario'], 2) ?></span>
                                <span class="detail-label">Valor Total</span>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <strong>Proveedor:</strong> <?= htmlspecialchars($insumo['proveedor']) ?> | 
                                <strong>Ubicación:</strong> <?= htmlspecialchars($insumo['ubicacion']) ?> | 
                                <strong>Última actualización:</strong> <?= date('d/m/Y', strtotime($insumo['ultima_actualizacion'])) ?>
                            </small>
                        </div>
                        
                        <div class="insumo-actions">
                            <button class="btn-action btn-edit" onclick="editarInsumo(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-pencil"></i> Editar
                            </button>
                            <button class="btn-action btn-movement" onclick="registrarMovimiento(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-arrow-left-right"></i> Movimiento
                            </button>
                            <button class="btn-action btn-report" onclick="verReporteSemanal(<?= $insumo['insumo_id'] ?>)">
                                <i class="bi bi-graph-up"></i> Reporte Sem.
                            </button>
                            <button class="btn-action btn-delete" title="Eliminar" onclick="eliminarInsumo(<?= $insumo['insumo_id'] ?>, <?= $insumo['cantidad'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        window.insumosData = <?= json_encode($insumos_data ?? []) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        
        function agregarInsumo() {
            // Obtener categorías y proveedores desde PHP
            const categorias = <?= json_encode(getCategoriasInsumos()) ?>;
            const proveedores = <?= json_encode(getProveedoresInsumos()) ?>;
            
            // Verificar que hay datos disponibles
            if (!categorias || categorias.length === 0) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'No hay categorías disponibles', 
                    text: 'Primero debes crear algunas categorías en el sistema.' 
                });
                return;
            }
            
            if (!proveedores || proveedores.length === 0) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'No hay proveedores disponibles', 
                    text: 'Primero debes crear algunos proveedores en el sistema.' 
                });
                return;
            }
            
            // Generar opciones de categorías y proveedores
            let opcionesCategorias = '<option value="">-- Selecciona una categoría --</option>';
            categorias.forEach(cat => {
                opcionesCategorias += `<option value="${cat.category_id}">${cat.name}</option>`;
            });
            
            let opcionesProveedores = '<option value="">-- Selecciona un proveedor --</option>';
            proveedores.forEach(prov => {
                opcionesProveedores += `<option value="${prov.supplier_id}">${prov.name}</option>`;
            });
            
            // Mostrar modal simplificado
            const modal = `
                <div class="modal fade" id="modalAgregarInsumo" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Crear Nuevo Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="formAgregarInsumo">
                                    <div class="mb-3">
                                        <label class="form-label">Nombre del insumo *</label>
                                        <input type="text" class="form-control" id="nombreInsumo" required placeholder="Ej: Cable UTP Cat6">
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Categoría *</label>
                                                <select class="form-select" id="categoriaInsumo" required>
                                                    ${opcionesCategorias}
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Proveedor *</label>
                                                <select class="form-select" id="proveedorInsumo" required>
                                                    ${opcionesProveedores}
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Unidad de medida</label>
                                                <select class="form-select" id="unidadInsumo">
                                                    <option value="pieza">Pieza</option>
                                                    <option value="bolsa">Bolsa</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Precio unitario</label>
                                                <input type="number" class="form-control" id="precioInsumo" min="0" step="0.01" placeholder="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Costo</label>
                                                <input type="number" class="form-control" id="costoInsumo" min="0" step="0.01" placeholder="0.00">
                                                <small class="text-muted">Costo de compra por unidad</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Cantidad inicial</label>
                                                <input type="number" class="form-control" id="cantidadInsumo" min="0" step="0.01" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Stock mínimo</label>
                                                <input type="number" class="form-control" id="minimoInsumo" min="0" step="0.01" placeholder="10">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Imagen del insumo</label>
                                        <input type="file" class="form-control" id="imagenInsumo" accept="image/*">
                                    </div>
                                    
                                    <div class="mb-3" id="divEquivalencia" style="display: none;">
                                        <label class="form-label">Equivalencia (ej: piezas por bolsa)</label>
                                        <input type="number" class="form-control" id="equivalenciaInsumo" min="1" step="1" placeholder="Ej: 1000">
                                        <small class="text-muted">¿Cuántas piezas contiene una bolsa? (Opcional, solo para ayuda en movimientos)</small>
                                    </div>
                                    
                                    <div class="mb-3" id="divUnidadPersonalizada" style="display: none;">
                                        <label class="form-label">Especificar unidad personalizada</label>
                                        <input type="text" class="form-control" id="unidadPersonalizada" placeholder="Ej: bolsa de 1000 piezas, caja de 50 unidades">
                                    </div>
                                    
                                    <div class="alert alert-info" id="infoUnidadInsumo">
                                        <i class="bi bi-info-circle"></i>
                                        <strong>Información:</strong> Los insumos son materiales independientes para gestión de stock.<br>
                                        <span id="ejemploUnidadInsumo">Ejemplo: Si tienes 100 conectores, selecciona "Pieza" como unidad y registra 100 piezas.</span>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarAgregarInsumo()">
                                    <i class="bi bi-check-circle"></i> Crear Insumo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Agregar modal al DOM
            document.body.insertAdjacentHTML('beforeend', modal);
            
            // Mostrar modal
            const modalElement = document.getElementById('modalAgregarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            
            // Limpiar modal al cerrar
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
            
            // Manejar unidad personalizada
            document.getElementById('unidadInsumo').addEventListener('change', function() {
                const divEquivalencia = document.getElementById('divEquivalencia');
                const ejemploUnidad = document.getElementById('ejemploUnidadInsumo');
                if (this.value === 'bolsa') {
                    divEquivalencia.style.display = 'block';
                    ejemploUnidad.innerHTML = 'Ejemplo: Si tienes una bolsa de 1000 conectores, selecciona "Bolsa" como unidad, registra 1 bolsa y especifica la equivalencia.';
                } else {
                    divEquivalencia.style.display = 'none';
                    document.getElementById('equivalenciaInsumo').value = '';
                    ejemploUnidad.innerHTML = 'Ejemplo: Si tienes 100 conectores, selecciona "Pieza" como unidad y registra 100 piezas.';
                }
            });
        }
        
        function confirmarAgregarInsumo() {
            // Obtener valores del formulario
            const nombre = document.getElementById('nombreInsumo').value.trim();
            const categoria = document.getElementById('categoriaInsumo').value;
            const proveedor = document.getElementById('proveedorInsumo').value;
            const cantidad = parseFloat(document.getElementById('cantidadInsumo').value) || 0;
            const minimo = parseFloat(document.getElementById('minimoInsumo').value) || 0;
            let unidad = document.getElementById('unidadInsumo').value;
            const precio = parseFloat(document.getElementById('precioInsumo').value) || 0;
            const imagenFile = document.getElementById('imagenInsumo').files[0];
            const equivalencia = document.getElementById('equivalenciaInsumo').value;
            
            // Manejar unidad personalizada
            if (unidad === 'otro') {
                const unidadPersonalizada = document.getElementById('unidadPersonalizada').value.trim();
                if (!unidadPersonalizada) {
                    Swal.fire({ icon: 'warning', title: 'Unidad requerida', text: 'Por favor especifica la unidad personalizada.' });
                    document.getElementById('unidadPersonalizada').focus();
                    return;
                }
                unidad = unidadPersonalizada;
            }

            // Manejar equivalencia
            if (equivalencia && parseInt(equivalencia) > 0) {
                unidad = `${unidad} (${equivalencia} piezas)`;
            }
            
            // Validaciones (igual que antes)
            if (!nombre) { Swal.fire({ icon: 'warning', title: 'Nombre requerido', text: 'Por favor ingresa el nombre del insumo.' }); document.getElementById('nombreInsumo').focus(); return; }
            if (!categoria) { Swal.fire({ icon: 'warning', title: 'Categoría requerida', text: 'Por favor selecciona una categoría.' }); document.getElementById('categoriaInsumo').focus(); return; }
            if (!proveedor) { Swal.fire({ icon: 'warning', title: 'Proveedor requerido', text: 'Por favor selecciona un proveedor.' }); document.getElementById('proveedorInsumo').focus(); return; }
            if (minimo < 0) { Swal.fire({ icon: 'warning', title: 'Stock mínimo inválido', text: 'El stock mínimo debe ser un valor positivo.' }); document.getElementById('minimoInsumo').focus(); return; }
            if (precio < 0) { Swal.fire({ icon: 'warning', title: 'Precio inválido', text: 'El precio unitario debe ser un valor positivo.' }); document.getElementById('precioInsumo').focus(); return; }
            
            Swal.fire({ title: 'Creando insumo...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            // Usar FormData para enviar archivos
            const formData = new FormData();
            formData.append('action', 'agregar_insumo');
            formData.append('nombre', nombre);
            formData.append('categoria_id', categoria);
            formData.append('proveedor_id', proveedor);
            formData.append('cantidad', cantidad);
            formData.append('minimo', minimo);
            formData.append('unidad', unidad);
            formData.append('precio_unitario', precio);
            if (imagenFile) {
                formData.append('imagen', imagenFile);
            }
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo creado exitosamente!', text: data.message, timer: 2000, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalAgregarInsumo'));
                    if (modal) modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error al crear insumo', text: data.message || 'No se pudo crear el insumo. Verifica los datos e intenta de nuevo.' });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({ icon: 'error', title: 'Error de conexión', text: 'No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.' });
            });
        }
        
        function editarInsumo(id) {
            // Buscar insumo real en la lista global
            const insumos = window.insumosData || [];
            const insumo = insumos.find(i => i.insumo_id == id);
            if (!insumo) {
                Swal.fire({ icon: 'error', title: 'Insumo no encontrado', text: 'No se pudo cargar la información del insumo.' });
                return;
            }
            // En la función editarInsumo, antes de mostrar el modal:
            // Buscar si el insumo tiene movimientos
            let tieneMovimientos = false;
            if (insumo.movimientos && insumo.movimientos > 0) {
                tieneMovimientos = true;
            }
            // Modal de edición real
            const modal = `
                <div class="modal fade" id="modalEditarInsumo" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar Insumo</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Nombre</label>
                                    <input type="text" class="form-control" value="${insumo.nombre}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad (Solo visual)</label>
                                    <input type="number" class="form-control" value="${insumo.cantidad}" readonly style="background-color: #f8f9fa;">
                                    <small class="text-muted">La cantidad solo se modifica a través de movimientos</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock mínimo</label>
                                    <input type="number" class="form-control" id="editMinimo" value="${insumo.minimo}" min="0">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Unidad</label>
                                    <input type="text" class="form-control" id="editUnidad" value="${insumo.unidad}" readonly style="background:#f8f9fa;cursor:not-allowed;">
                                </div>
                                ${tieneMovimientos ? `<div class='alert alert-warning mt-2'><i class='bi bi-exclamation-triangle'></i> No se puede cambiar la unidad de medida porque este insumo ya tiene movimientos registrados.</div>` : ''}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="guardarEdicionInsumo(${id})">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalElement = document.getElementById('modalEditarInsumo');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function guardarEdicionInsumo(id) {
            const minimo = document.getElementById('editMinimo').value;
            const unidad = document.getElementById('editUnidad').value;
            if (minimo === '' || unidad === '') {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor completa todos los campos obligatorios.' });
                return;
            }
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'editar_insumo',
                    insumo_id: id,
                    minimo: minimo,
                    unidad: unidad
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Insumo actualizado correctamente!', text: 'Los cambios han sido guardados en la base de datos.', timer: 1800, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarInsumo'));
                    modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el insumo. Intenta de nuevo.' });
            });
        }
        
        function registrarMovimiento(id) {
            // Buscar insumo en la lista global
            const insumos = window.insumosData || [];
            const insumo = insumos.find(i => i.insumo_id == id);
            if (!insumo) {
                Swal.fire({ icon: 'error', title: 'Insumo no encontrado', text: 'No se pudo cargar la información del insumo.' });
                return;
            }
            
            // Buscar la equivalencia en la unidad del insumo (ej: Bolsa (1000 piezas))
            let equivalencia = 1;
            const match = /\((\d+) piezas\)/.exec(insumo.unidad);
            if (match) equivalencia = parseInt(match[1]);

            let ayudaEquivalencia = '';
            if (equivalencia > 1) {
                ayudaEquivalencia = `<div class='alert alert-info mt-2'>
                    <b>Equivalencia:</b> 1 ${insumo.unidad.split('(')[0].trim()} = ${equivalencia} piezas.<br>
                    Puedes registrar la salida en piezas y el sistema calculará la fracción de bolsa/caja a descontar.
                    <div class='input-group mt-2'>
                        <span class='input-group-text'>Piezas a sacar</span>
                        <input type='number' min='1' class='form-control' id='piezasMovimiento' placeholder='Ej: 25' oninput='calcularFraccionUnidad()'>
                        <button class='btn btn-outline-primary' type='button' onclick='calcularFraccionUnidad()'>Calcular</button>
                    </div>
                    <div id='resultadoFraccion' class='mt-2'></div>
                </div>`;
            }

            // Dentro de la función registrarMovimiento, antes de generar el modal:
            let equivalenciaInversaHtml = '';
            if (equivalencia > 1) {
                equivalenciaInversaHtml = `<div style='font-size:1em;color:#333;margin-bottom:8px;'><b>Equivalente:</b> 1 ${insumo.unidad.split('(')[0].trim()} = ${equivalencia} piezas</div>`;
            }

            // Modal para registrar movimiento
            const modal = `
                <div class="modal fade" id="modalRegistrarMovimiento" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-arrow-left-right"></i> Registrar Movimiento - ${insumo.nombre}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Stock actual del insumo:</strong> ${insumo.cantidad} ${insumo.unidad}
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tipo de movimiento</label>
                                    <select class="form-select" id="tipoMovimiento" onchange="actualizarInfoMovimiento()" data-equivalencia="${equivalencia}">
                                        <option value="entrada">Entrada (Desde inventario)</option>
                                        <option value="salida">Salida (Uso/Consumo)</option>
                                    </select>
                                </div>
                                <div id="infoStock" class="alert alert-warning" style="display:none;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <span id="infoStockText"></span>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cantidad</label>
                                    <input type="number" class="form-control" id="cantidadMovimiento" min="0.0001" step="0.0001" placeholder="Ej: 0.0250">
                                    <input type="hidden" id="piezasMovidasInput">
                                </div>
                                ${ayudaEquivalencia}
                                <div class="mb-3">
                                    <label class="form-label">Motivo</label>
                                    <textarea class="form-control" id="motivoMovimiento" rows="3" placeholder="Ej: Uso en proyecto, Reabastecimiento, etc."></textarea>
                                </div>
                                ${equivalenciaInversaHtml}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-primary" onclick="confirmarMovimiento(${id})">
                                    <i class="bi bi-check-circle"></i> Registrar Movimiento
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modal);
            const modalElement = document.getElementById('modalRegistrarMovimiento');
            const bootstrapModal = new bootstrap.Modal(modalElement);
            bootstrapModal.show();
            modalElement.addEventListener('hidden.bs.modal', function() {
                modalElement.remove();
            });
        }
        
        function actualizarInfoMovimiento() {
            const tipo = document.getElementById('tipoMovimiento').value;
            const infoDiv = document.getElementById('infoStock');
            const infoText = document.getElementById('infoStockText');
            
            if (tipo === 'entrada') {
                infoText.innerHTML = '<strong>Nota:</strong> Las entradas agregan stock al insumo de forma independiente.';
                infoDiv.style.display = 'block';
            } else {
                infoText.innerHTML = '<strong>Nota:</strong> Las salidas registran el consumo o uso del insumo.';
                infoDiv.style.display = 'block';
            }
        }
        
        function confirmarMovimiento(id) {
            const tipo = document.getElementById('tipoMovimiento').value;
            const cantidad = parseFloat(document.getElementById('cantidadMovimiento').value);
            const motivo = document.getElementById('motivoMovimiento').value;
            const piezas_movidas = document.getElementById('piezasMovidasInput').value;
            
            if (!cantidad || cantidad <= 0) {
                Swal.fire({ icon: 'warning', title: 'Cantidad inválida', text: 'Por favor ingresa una cantidad válida.' });
                return;
            }
            
            if (!motivo.trim()) {
                Swal.fire({ icon: 'warning', title: 'Motivo requerido', text: 'Por favor ingresa un motivo para el movimiento.' });
                return;
            }
            
            // Enviar datos al backend
            const bodyParams = new URLSearchParams({
                action: 'registrar_movimiento',
                insumo_id: id,
                tipo_movimiento: tipo,
                cantidad: cantidad,
                motivo: motivo
            });

            if (piezas_movidas) {
                bodyParams.append('piezas_movidas', piezas_movidas);
            }

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: bodyParams
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: '¡Movimiento registrado!', text: data.message, timer: 1800, showConfirmButton: false });
                    const modal = bootstrap.Modal.getInstance(document.getElementById('modalRegistrarMovimiento'));
                    modal.hide();
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el movimiento. Intenta de nuevo.' });
            });
        }

        function calcularFraccionUnidad() {
            const piezasInput = document.getElementById('piezasMovimiento');
            const piezas = parseFloat(piezasInput.value);
            const equivalencia = parseInt(document.getElementById('tipoMovimiento').getAttribute('data-equivalencia'));
            const resultadoDiv = document.getElementById('resultadoFraccion');
            const cantidadInput = document.getElementById('cantidadMovimiento');
            const piezasMovidasInput = document.getElementById('piezasMovidasInput');

            if (!piezas || piezas <= 0 || !equivalencia || equivalencia <= 0) {
                resultadoDiv.innerHTML = '<span class="text-danger">Ingresa una cantidad válida de piezas.</span>';
                cantidadInput.value = '';
                piezasMovidasInput.value = '';
                return;
            }
            const fraccion = piezas / equivalencia;
            cantidadInput.value = fraccion.toFixed(4);
            piezasMovidasInput.value = piezas;
            resultadoDiv.innerHTML = `Eso equivale a <b>${fraccion.toFixed(4)}</b> unidades (${equivalencia} piezas por unidad).`;
        }
        
        function verReporteSemanal(id) {
            console.log('Enviando reporte para insumo_id:', id);
            // Mostrar loading
            Swal.fire({
                title: 'Cargando reporte...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            // Obtener datos del reporte desde el backend
            fetch(`/inventory-management-system-main/insumos/reporte_ajax.php?action=obtener_reporte&insumo_id=${id}`)
                .then(r => r.json())
                .then(data => {
                    Swal.close();
                    if (!data.success) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        return;
                    }
                    const reporte = data.data;
                    const insumo = reporte.insumo;
                    const estadisticas = reporte.estadisticas;
                    const movimientos = reporte.movimientos;
                    // Generar tabla de movimientos
                    let tablaMovimientos = '';
                    if (movimientos.length > 0) {
                        movimientos.forEach(mov => {
                            const fecha = new Date(mov.fecha_movimiento).toLocaleDateString('es-ES');
                            const tipo = mov.tipo_movimiento === 'entrada' ? 'Entrada' : 'Salida';
                            const clase = mov.tipo_movimiento === 'entrada' ? 'success' : 'danger';
                            const motivo = mov.motivo || 'Sin motivo especificado';
                            let cantidadMostrada = '';
                            let equivalencia = 1;
                            const match = /\((\d+) piezas\)/.exec(insumo.unidad);
                            if (match) equivalencia = parseInt(match[1]);
                            if (mov.piezas_movidas && parseFloat(mov.piezas_movidas) > 0) {
                                cantidadMostrada = `<b>${parseInt(mov.piezas_movidas)} piezas</b>`;
                                if (equivalencia > 1) {
                                    const fraccion = (parseFloat(mov.piezas_movidas) / equivalencia).toFixed(4);
                                    cantidadMostrada += `<br><small class='text-muted'>(${fraccion} bolsas)</small>`;
                                }
                            } else if (equivalencia > 1) {
                                const bolsas = parseFloat(mov.cantidad);
                                cantidadMostrada = `<b>${bolsas % 1 === 0 ? bolsas : bolsas.toFixed(4)} bolsas</b>`;
                                if (equivalencia > 1) {
                                    const piezas = Math.round(bolsas * equivalencia);
                                    cantidadMostrada += `<br><small class='text-muted'>(${piezas} piezas)</small>`;
                                }
                            } else {
                                cantidadMostrada = `<b>${parseFloat(mov.cantidad)} ${insumo.unidad}</b>`;
                            }
                            tablaMovimientos += `
                                <tr>
                                    <td>${fecha}</td>
                                    <td><span class="badge bg-${clase}">${tipo}</span></td>
                                    <td>${cantidadMostrada}</td>
                                    <td>${motivo}</td>
                                    <td>${mov.usuario || 'Sistema'}</td>
                                </tr>
                            `;
                        });
                    } else {
                        tablaMovimientos = '<tr><td colspan="5" class="text-center">No hay movimientos registrados</td></tr>';
                    }
                    // Generar reporte dinámico
                    const modal = `
                        <div class="modal fade" id="modalReporteSemanal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="bi bi-graph-up"></i> Reporte de Movimientos - ${insumo.nombre}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h3 class="text-primary">${estadisticas.stock_actual}</h3>
                                                        <p class="text-muted">Stock Actual</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h3 class="text-success">${estadisticas.total_entradas}</h3>
                                                        <p class="text-muted">Total Entradas</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h3 class="text-danger">${estadisticas.total_salidas}</h3>
                                                        <p class="text-muted">Total Salidas</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card text-center">
                                                    <div class="card-body">
                                                        <h3 class="text-warning">${estadisticas.consumo_semanal.toFixed(1)}</h3>
                                                        <p class="text-muted">Consumo Sem.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-4">
                                            <h6>Historial de Movimientos (Últimas 4 semanas)</h6>
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead>
                                                        <tr>
                                                            <th>Fecha</th>
                                                            <th>Tipo</th>
                                                            <th>Cantidad</th>
                                                            <th>Motivo</th>
                                                            <th>Usuario</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyMovimientos">
                                                        ${tablaMovimientos}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle"></i>
                                                <strong>Información del insumo:</strong><br>
                                                <strong>Categoría:</strong> ${insumo.categoria_nombre || 'N/A'}<br>
                                                <strong>Proveedor:</strong> ${insumo.proveedor_nombre || 'N/A'}<br>
                                                <strong>Stock mínimo:</strong> ${insumo.minimo} ${insumo.unidad}<br>
                                                <strong>Precio unitario:</strong> $${insumo.precio_unitario}/${insumo.unidad}<br>
                                                <strong>Estado:</strong> <span class="badge bg-${insumo.estado === 'disponible' ? 'success' : insumo.estado === 'bajo_stock' ? 'warning' : 'danger'}">${insumo.estado}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        <button type="button" class="btn btn-primary" id="btnExportarPDF">
                                            <i class="bi bi-file-earmark-pdf"></i> Exportar PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    // Agregar modal al DOM
                    document.body.insertAdjacentHTML('beforeend', modal);
                    // Dentro de la función verReporteSemanal, después de agregar el modal al DOM y antes de mostrar el modal:
                    // Asignar el onclick al botón Exportar PDF (abre nueva pestaña con el endpoint HTML)
                    if (document.getElementById('btnExportarPDF')) {
                        document.getElementById('btnExportarPDF').onclick = function() {
                            window.open(`/inventory-management-system-main/insumos/reporte_html.php?insumo_id=${insumo.insumo_id}`, '_blank');
                        };
                    }
                    // Mostrar modal
                    const modalElement = document.getElementById('modalReporteSemanal');
                    const bootstrapModal = new bootstrap.Modal(modalElement);
                    bootstrapModal.show();
                    // Limpiar modal al cerrar
                    modalElement.addEventListener('hidden.bs.modal', function() {
                        modalElement.remove();
                    });
                })
                .catch(() => {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el reporte. Intenta de nuevo.' });
                });
        }

        function eliminarInsumo(id, cantidad) {
            console.log('Intentando eliminar insumo ID:', id, 'con cantidad:', cantidad);
            if (parseFloat(cantidad) > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Acción no permitida',
                    text: 'No es posible eliminar un insumo con stock (' + cantidad + '). Para poder eliminarlo, el stock debe ser cero.'
                });
                return;
            }

            Swal.fire({
                title: '¿Está seguro de que desea eliminar este insumo?',
                text: "Esta acción marcará el insumo como inactivo y no podrá ser utilizado en nuevos movimientos. La eliminación es definitiva.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'eliminar_insumo',
                            insumo_id: id
                        })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message, timer: 1500, showConfirmButton: false });
                            setTimeout(function() { window.location.reload(); }, 1000);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    })
                    .catch(function() {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el insumo. Intenta de nuevo.' });
                    });
                }
            });
        }
    </script>
</body>
</html> 