<?php
require_once '../auth/middleware.php';
require_once '../connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$cotizacion_id = intval($_POST['cotizacion_id'] ?? 0);
$nuevo_estado_id = intval($_POST['nuevo_estado_id'] ?? 0);
$estado_anterior_id = intval($_POST['estado_anterior_id'] ?? 0);

if (!$cotizacion_id || !$nuevo_estado_id) {
    $_SESSION['error'] = 'Datos incompletos para cambiar el estado.';
    header('Location: index.php');
    exit;
}

// Obtener información de la cotización
$stmt = $mysqli->prepare("
    SELECT c.*, ec.nombre_estado as estado_actual
    FROM cotizaciones c
    LEFT JOIN est_cotizacion ec ON c.estado_id = ec.est_cot_id
    WHERE c.cotizacion_id = ?
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$cotizacion = $stmt->get_result()->fetch_assoc();

if (!$cotizacion) {
    $_SESSION['error'] = 'Cotización no encontrada.';
    header('Location: index.php');
    exit;
}

// Obtener productos de la cotización
$stmt = $mysqli->prepare("
    SELECT cp.product_id, cp.cantidad, p.product_name, p.quantity as stock_actual
    FROM cotizaciones_productos cp
    LEFT JOIN products p ON cp.product_id = p.product_id
    WHERE cp.cotizacion_id = ?
");
$stmt->bind_param('i', $cotizacion_id);
$stmt->execute();
$productos = $stmt->get_result();

// Obtener nombres de estados
$stmt = $mysqli->prepare("SELECT est_cot_id, nombre_estado FROM est_cotizacion WHERE est_cot_id IN (?, ?)");
$stmt->bind_param('ii', $estado_anterior_id, $nuevo_estado_id);
$stmt->execute();
$estados_result = $stmt->get_result();
$estados = [];
while ($estado = $estados_result->fetch_assoc()) {
    $estados[$estado['est_cot_id']] = $estado['nombre_estado'];
}

$estado_anterior_nombre = $estados[$estado_anterior_id] ?? 'Desconocido';
$nuevo_estado_nombre = $estados[$nuevo_estado_id] ?? 'Desconocido';

// Iniciar transacción
$mysqli->begin_transaction();

try {
    // Actualizar estado de la cotización
    $stmt = $mysqli->prepare("UPDATE cotizaciones SET estado_id = ? WHERE cotizacion_id = ?");
    $stmt->bind_param('ii', $nuevo_estado_id, $cotizacion_id);
    $stmt->execute();
    $stmt->close();

    // Los cambios de estado ya no afectan el inventario
    $_SESSION['success'] = "Estado de cotización actualizado a: " . $nuevo_estado_nombre;
    
    // Registrar en historial
    require_once 'helpers.php';
    inicializarAccionesCotizacion($mysqli);
    
    $accion_nombre = '';
    switch ($nuevo_estado_nombre) {
        case 'Enviada':
            $accion_nombre = 'Enviada';
            break;
        case 'Aprobada':
            $accion_nombre = 'Aprobada';
            break;
        case 'Rechazada':
            $accion_nombre = 'Rechazada';
            break;
        default:
            $accion_nombre = 'Modificada';
    }
    
    registrarAccionCotizacion(
        $cotizacion_id,
        $accion_nombre,
        "Estado cambiado de '$estado_anterior_nombre' a '$nuevo_estado_nombre'",
        $_SESSION['user_id'] ?? null,
        $mysqli
    );
    
    // Confirmar transacción
    $mysqli->commit();
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $mysqli->rollback();
    $_SESSION['error'] = 'Error al cambiar estado: ' . $e->getMessage();
}

// Redirigir de vuelta
$redirect_url = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : "ver.php?id=$cotizacion_id";
header("Location: $redirect_url");
exit;
?> 