<?php
/**
 * Funciones helper para el sistema de cotizaciones
 */

/**
 * Registra una acción en el historial de cotizaciones
 * 
 * @param int $cotizacion_id ID de la cotización
 * @param string $nombre_accion Nombre de la acción (Creada, Enviada, Aprobada, etc.)
 * @param string $comentario Comentario descriptivo de la acción
 * @param int|null $user_id ID del usuario que realizó la acción
 * @param mysqli $mysqli Conexión a la base de datos
 * @return bool True si se registró correctamente, False en caso contrario
 */
function registrarAccionCotizacion($cotizacion_id, $nombre_accion, $comentario, $user_id = null, $mysqli = null) {
    global $mysqli;
    
    if (!$mysqli) {
        require_once '../connection.php';
    }
    
    try {
        // Obtener el ID de la acción
        $stmt = $mysqli->prepare("SELECT accion_id FROM cotizaciones_acciones WHERE nombre_accion = ?");
        $stmt->bind_param('s', $nombre_accion);
        $stmt->execute();
        $result = $stmt->get_result();
        $accion = $result->fetch_assoc();
        
        if (!$accion) {
            // Si la acción no existe, la creamos
            $stmt = $mysqli->prepare("INSERT INTO cotizaciones_acciones (nombre_accion) VALUES (?)");
            $stmt->bind_param('s', $nombre_accion);
            $stmt->execute();
            $accion_id = $stmt->insert_id;
            $stmt->close();
        } else {
            $accion_id = $accion['accion_id'];
        }
        
        // Registrar en el historial
        $stmt = $mysqli->prepare("
            INSERT INTO cotizaciones_historial (cotizacion_id, accion_id, comentario, user_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('iisi', $cotizacion_id, $accion_id, $comentario, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error al registrar acción de cotización: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el historial de una cotización
 * 
 * @param int $cotizacion_id ID de la cotización
 * @param mysqli $mysqli Conexión a la base de datos
 * @return mysqli_result|false Resultado de la consulta o false en caso de error
 */
function obtenerHistorialCotizacion($cotizacion_id, $mysqli = null) {
    global $mysqli;
    
    if (!$mysqli) {
        require_once '../connection.php';
    }
    
    try {
        $stmt = $mysqli->prepare("
            SELECT h.*, a.nombre_accion, u.username as usuario_nombre
            FROM cotizaciones_historial h
            LEFT JOIN cotizaciones_acciones a ON h.accion_id = a.accion_id
            LEFT JOIN users u ON h.user_id = u.user_id
            WHERE h.cotizacion_id = ?
            ORDER BY h.fecha_accion DESC
        ");
        $stmt->bind_param('i', $cotizacion_id);
        $stmt->execute();
        return $stmt->get_result();
        
    } catch (Exception $e) {
        error_log("Error al obtener historial de cotización: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene estadísticas del historial de una cotización
 * 
 * @param int $cotizacion_id ID de la cotización
 * @param mysqli $mysqli Conexión a la base de datos
 * @return array|false Array con estadísticas o false en caso de error
 */
function obtenerStatsHistorialCotizacion($cotizacion_id, $mysqli = null) {
    global $mysqli;
    
    if (!$mysqli) {
        require_once '../connection.php';
    }
    
    try {
        $stmt = $mysqli->prepare("
            SELECT 
                COUNT(*) as total_acciones,
                MIN(fecha_accion) as primera_accion,
                MAX(fecha_accion) as ultima_accion,
                COUNT(DISTINCT user_id) as usuarios_involucrados
            FROM cotizaciones_historial 
            WHERE cotizacion_id = ?
        ");
        $stmt->bind_param('i', $cotizacion_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("Error al obtener estadísticas del historial: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si las acciones de cotización están inicializadas
 * 
 * @param mysqli $mysqli Conexión a la base de datos
 * @return bool True si están inicializadas, False en caso contrario
 */
function verificarAccionesCotizacion($mysqli = null) {
    global $mysqli;
    
    if (!$mysqli) {
        require_once '../connection.php';
    }
    
    try {
        $result = $mysqli->query("SELECT COUNT(*) as total FROM cotizaciones_acciones");
        $row = $result->fetch_assoc();
        return $row['total'] > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Inicializa las acciones básicas de cotización si no existen
 * 
 * @param mysqli $mysqli Conexión a la base de datos
 * @return bool True si se inicializaron correctamente, False en caso contrario
 */
function inicializarAccionesCotizacion($mysqli = null) {
    global $mysqli;
    
    if (!$mysqli) {
        require_once '../connection.php';
    }
    
    if (verificarAccionesCotizacion($mysqli)) {
        return true; // Ya están inicializadas
    }
    
    try {
        $acciones_basicas = [
            'Creada',
            'Enviada',
            'Aprobada',
            'Rechazada',
            'Convertida',
            'Modificada'
        ];
        
        $stmt = $mysqli->prepare("INSERT INTO cotizaciones_acciones (nombre_accion) VALUES (?)");
        
        foreach ($acciones_basicas as $accion) {
            $stmt->bind_param('s', $accion);
            $stmt->execute();
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al inicializar acciones de cotización: " . $e->getMessage());
        return false;
    }
}
?> 