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

    // Manejar cambios de stock según el cambio de estado
    if ($nuevo_estado_nombre === 'Aprobada' && $estado_anterior_nombre !== 'Aprobada') {
        // APROBAR: Descontar stock
        $productos->data_seek(0); // Resetear el puntero del resultado
        while ($producto = $productos->fetch_assoc()) {
            $nuevo_stock = $producto['stock_actual'] - $producto['cantidad'];
            
            if ($nuevo_stock < 0) {
                throw new Exception("Stock insuficiente para el producto: " . $producto['product_name'] . 
                                  " (Stock actual: " . $producto['stock_actual'] . 
                                  ", Cantidad requerida: " . $producto['cantidad'] . ")");
            }
            
            $stmt = $mysqli->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
            $stmt->bind_param('ii', $nuevo_stock, $producto['product_id']);
            $stmt->execute();
            $stmt->close();
            
            // Registrar movimiento de salida
            $stmt = $mysqli->prepare("
                INSERT INTO movements (product_id, movement_type_id, quantity, reference, notes, user_id) 
                VALUES (?, 2, ?, ?, ?, ?)
            ");
            $referencia = "Cotización aprobada: " . $cotizacion['numero_cotizacion'];
            $notas = "Aprobación de cotización - Cliente: " . $cotizacion['cliente_nombre_real'];
            $user_id = $_SESSION['user_id'] ?? null;
            $cantidad_negativa = -$producto['cantidad']; // Cantidad negativa para salida
            $stmt->bind_param('iissi', $producto['product_id'], $cantidad_negativa, $referencia, $notas, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $_SESSION['success'] = "Cotización aprobada exitosamente. Stock actualizado.";
        
    } elseif ($estado_anterior_nombre === 'Aprobada' && $nuevo_estado_nombre !== 'Aprobada') {
        // DESAPROBAR: Restaurar stock
        $productos->data_seek(0); // Resetear el puntero del resultado
        while ($producto = $productos->fetch_assoc()) {
            $nuevo_stock = $producto['stock_actual'] + $producto['cantidad'];
            
            $stmt = $mysqli->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
            $stmt->bind_param('ii', $nuevo_stock, $producto['product_id']);
            $stmt->execute();
            $stmt->close();
            
            // Registrar movimiento de entrada (restauración)
            $stmt = $mysqli->prepare("
                INSERT INTO movements (product_id, movement_type_id, quantity, reference, notes, user_id) 
                VALUES (?, 1, ?, ?, ?, ?)
            ");
            $referencia = "Cotización desaprobada: " . $cotizacion['numero_cotizacion'];
            $notas = "Desaprobación de cotización - Cliente: " . $cotizacion['cliente_nombre_real'];
            $user_id = $_SESSION['user_id'] ?? null;
            $stmt->bind_param('iissi', $producto['product_id'], $producto['cantidad'], $referencia, $notas, $user_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $_SESSION['success'] = "Cotización desaprobada. Stock restaurado.";
        
    } else {
        // Otros cambios de estado (sin afectar stock)
        $_SESSION['success'] = "Estado de cotización actualizado a: " . $nuevo_estado_nombre;
    }
    
    // Registrar en historial
    $stmt = $mysqli->prepare("
        INSERT INTO cotizaciones_historial (cotizacion_id, accion_id, comentario, user_id) 
        VALUES (?, ?, ?, ?)
    ");
    $comentario = "Estado cambiado de '$estado_anterior_nombre' a '$nuevo_estado_nombre'";
    $user_id = $_SESSION['user_id'] ?? null;
    $accion_id = 1; // Asumiendo que 1 es "Cambio de estado"
    $stmt->bind_param('iisi', $cotizacion_id, $accion_id, $comentario, $user_id);
    $stmt->execute();
    $stmt->close();
    
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